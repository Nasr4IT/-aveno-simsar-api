<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Conversation;
use App\Notifications\NewMessageReceived;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

// Mirrors Api\ChatController: a conversation is (ad, buyer, seller), both
// participants must have accounts. Polling-based like the API (no
// websockets) — the thread page fetches poll() every few seconds.
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $conversations = Conversation::where('buyer_id', $me)
            ->orWhere('seller_id', $me)
            ->with(['ad.images', 'buyer', 'seller'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $me)->whereNull('read_at')])
            ->latest('last_message_at')
            ->paginate(20);

        return view('site.messages.index', compact('conversations'));
    }

    public function start(Request $request, Ad $ad)
    {
        abort_unless($ad->status === 'approved', 404);

        if ($ad->user_id === $request->user()->id) {
            return back()->withErrors(['message' => __('لا يمكنك مراسلة نفسك.')]);
        }

        $conversation = Conversation::firstOrCreate([
            'ad_id' => $ad->id,
            'buyer_id' => $request->user()->id,
            'seller_id' => $ad->user_id,
        ]);

        return redirect()->route('site.messages.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->load(['ad.images', 'buyer', 'seller', 'messages.sender']);
        $this->markRead($request, $conversation);

        $other = $conversation->buyer_id === $request->user()->id ? $conversation->seller : $conversation->buyer;

        return view('site.messages.show', compact('conversation', 'other'));
    }

    public function send(Request $request, Conversation $conversation, PushNotificationService $push)
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

        return redirect()->route('site.messages.show', $conversation);
    }

    // GET /messages/{conversation}/poll?after=<message id> — messages newer
    // than the last one the page has, as JSON for the thread's poll script.
    public function poll(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);

        $me = $request->user()->id;
        $messages = $conversation->messages()
            ->with('sender')
            ->where('id', '>', (int) $request->query('after', 0))
            ->get();

        $this->markRead($request, $conversation);

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'mine' => $m->sender_id === $me,
                'sender_name' => $m->sender->name,
                'body' => $m->body,
                'time' => $m->created_at->diffForHumans(),
            ]),
        ]);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(in_array($request->user()->id, [$conversation->buyer_id, $conversation->seller_id], true), 403);
    }

    private function markRead(Request $request, Conversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
