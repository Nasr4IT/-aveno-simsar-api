<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Ad;
use App\Models\Conversation;
use App\Notifications\NewMessageReceived;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

/**
 * See docs/API_CONTRACT.md § Chat ("تواصل مباشر بين المستخدمين حول إعلان معيّن،
 * ويتطلب أن يمتلك المستخدم حسابًا"). Polling-based for the MVP; swap to
 * broadcasting (Reverb/Pusher) later without changing this contract.
 */
class ChatController extends Controller
{
    // GET /api/conversations — the current user's conversations (as buyer or seller).
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $conversations = Conversation::where(fn ($q) => $q->where('buyer_id', $me)->orWhere('seller_id', $me))
            ->with(['ad.images', 'buyer', 'seller', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $me)->whereNull('read_at')])
            ->latest('last_message_at')
            ->paginate(20);

        return ConversationResource::collection($conversations);
    }

    // POST /api/ads/{ad}/conversations — start (or fetch) the conversation about this ad.
    public function start(Request $request, Ad $ad)
    {
        // Matches AdController@show / FavoriteController@store: an ad that
        // isn't approved yet is invisible to everyone but its owner, so it
        // can't be messaged about either.
        abort_unless($ad->status === 'approved', 404);

        abort_if($ad->user_id === $request->user()->id, 422, 'لا يمكنك مراسلة نفسك');

        $key = ['ad_id' => $ad->id, 'buyer_id' => $request->user()->id, 'seller_id' => $ad->user_id];

        // The seller turned chat off for this ad: no new conversations (an
        // existing one still opens).
        abort_if(! $ad->allow_chat && ! Conversation::where($key)->exists(), 422, __('البائع أوقف المراسلة على هذا الإعلان.'));

        $conversation = Conversation::firstOrCreate($key);

        return new ConversationResource($conversation);
    }

    // GET /api/conversations/{conversation}/messages
    // Three ways to read a thread:
    //   (default)       oldest first, 50 per page — unchanged;
    //   ?order=desc     newest first, 50 per page — page 1 is the latest;
    //   ?after_id=<id>  only messages newer than <id>, oldest first,
    //                   unpaginated (max 200) — for polling every few seconds.
    // Reading does not mark anything read; call POST .../read for that.
    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $params = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
            'order' => ['nullable', 'in:asc,desc'],
        ]);

        $query = $conversation->messages()->with('sender');

        if (isset($params['after_id'])) {
            return MessageResource::collection($query->where('id', '>', $params['after_id'])->limit(200)->get());
        }

        if (($params['order'] ?? 'asc') === 'desc') {
            $query->reorder()->orderByDesc('created_at')->orderByDesc('id');
        }

        return MessageResource::collection($query->paginate(50)->withQueryString());
    }

    // POST /api/conversations/{conversation}/read — marks the other person's
    // messages in this conversation as read.
    public function markRead(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $marked = $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['data' => ['marked' => $marked]]);
    }

    // POST /api/conversations/{conversation}/messages
    public function sendMessage(Request $request, Conversation $conversation, PushNotificationService $push)
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $recipient = $conversation->otherParticipant($request->user()->id);
        abort_if($recipient->trashed(), 422, __('هذا المستخدم حذف حسابه، لا يمكن إرسال رسائل جديدة.'));

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        $recipient->notify(new NewMessageReceived($message));
        $push->sendToUser($recipient, "رسالة جديدة من {$request->user()->name}", $data['body'], ['type' => 'new_message', 'conversation_id' => $conversation->id]);

        return new MessageResource($message);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        $userId = $request->user()->id;
        abort_unless(in_array($userId, [$conversation->buyer_id, $conversation->seller_id]), 403);
    }
}
