@extends('site.layout')
@section('title', __('محادثة مع :name', ['name' => $other->name]))
@section('content')
    @php $cover = $conversation->ad->images->firstWhere('is_cover', true) ?? $conversation->ad->images->first(); @endphp
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <a class="convo card" style="padding:12px 16px; margin-bottom:12px" href="{{ route('site.ads.show', $conversation->ad) }}">
            @if ($cover)<img src="{{ $cover->url }}" alt="">@endif
            <div class="who">
                <strong>{{ $other->name }}</strong>
                <span class="muted small">{{ __('بخصوص: :title', ['title' => $conversation->ad->title]) }}</span>
            </div>
            <span class="muted">@include('site.partials.icon', ['name' => 'arrow', 'size' => 18, 'class' => 'flip-rtl'])</span>
        </a>

        <div class="card">
            <div class="thread" id="thread" data-poll="{{ route('site.messages.poll', $conversation) }}">
                @forelse ($conversation->messages as $message)
                    <div class="bubble {{ $message->sender_id === auth()->id() ? 'mine' : '' }}" data-id="{{ $message->id }}">
                        {{ $message->body }}
                        <span class="t">{{ $message->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="muted small" style="text-align:center" id="thread-empty">{{ __('ابدأ المحادثة — رسالتك الأولى تصل للطرف الآخر كإشعار.') }}</p>
                @endforelse
            </div>

            @if ($other->trashed())
                <p class="muted small" style="text-align:center; margin:12px 0 0">{{ __('هذا المستخدم حذف حسابه، لا يمكن إرسال رسائل جديدة.') }}</p>
            @else
                <form class="compose" method="POST" action="{{ route('site.messages.send', $conversation) }}">
                    @csrf
                    <input type="text" name="body" maxlength="2000" required autocomplete="off" placeholder="{{ __('اكتب رسالتك...') }}" autofocus>
                    <button class="btn btn-primary" type="submit">{{ __('إرسال') }}</button>
                </form>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var thread = document.getElementById('thread');
                thread.style.scrollBehavior = 'auto';
                thread.scrollTop = thread.scrollHeight;
                thread.style.scrollBehavior = '';
                function lastId() {
                    var bubbles = thread.querySelectorAll('.bubble');
                    return bubbles.length ? bubbles[bubbles.length - 1].dataset.id : 0;
                }
                // Same polling approach as the app: ask for anything newer than
                // the last message we have, every 5 seconds.
                setInterval(function () {
                    fetch(thread.dataset.poll + '?after=' + lastId(), { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : { messages: [] }; })
                        .then(function (data) {
                            if (!data.messages.length) return;
                            var empty = document.getElementById('thread-empty'); if (empty) empty.remove();
                            data.messages.forEach(function (m) {
                                var div = document.createElement('div');
                                div.className = 'bubble new' + (m.mine ? ' mine' : ''); div.dataset.id = m.id;
                                div.textContent = m.body;
                                var t = document.createElement('span'); t.className = 't'; t.textContent = m.time;
                                div.appendChild(t); thread.appendChild(div);
                            });
                            thread.scrollTop = thread.scrollHeight;
                        }).catch(function () {});
                }, 5000);
            })();
        </script>
    @endpush
@endsection
