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
        $conversations = Conversation::where('buyer_id', $request->user()->id)
            ->orWhere('seller_id', $request->user()->id)
            ->with(['ad.images', 'buyer', 'seller'])
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

        $conversation = Conversation::firstOrCreate([
            'ad_id' => $ad->id,
            'buyer_id' => $request->user()->id,
            'seller_id' => $ad->user_id,
        ]);

        return new ConversationResource($conversation);
    }

    // GET /api/conversations/{conversation}/messages
    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        return MessageResource::collection($conversation->messages()->with('sender')->paginate(50));
    }

    // POST /api/conversations/{conversation}/messages
    public function sendMessage(Request $request, Conversation $conversation, PushNotificationService $push)
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        $recipient = $conversation->buyer_id === $request->user()->id ? $conversation->seller : $conversation->buyer;
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
