@extends('site.layout')
@section('title', 'الرسائل')
@section('content')
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <h1>الرسائل</h1>
        @if ($conversations->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'chat', 'size' => 40])
                لا توجد محادثات بعد — راسل بائعًا من صفحة أي إعلان.
            </div>
        @else
            <div class="card" style="padding:4px 20px">
                @foreach ($conversations as $conversation)
                    @php
                        $other = $conversation->buyer_id === auth()->id() ? $conversation->seller : $conversation->buyer;
                        $cover = $conversation->ad->images->firstWhere('is_cover', true) ?? $conversation->ad->images->first();
                    @endphp
                    <a class="convo" href="{{ route('site.messages.show', $conversation) }}">
                        @if ($cover)<img src="{{ $cover->url }}" alt="">@endif
                        <div class="who">
                            <strong>{{ $other->name }}</strong>
                            <span class="muted small">{{ $conversation->ad->title }}</span>
                        </div>
                        @if ($conversation->unread_count)
                            <span class="unread">{{ $conversation->unread_count }}</span>
                        @endif
                        <span class="muted small">{{ $conversation->last_message_at?->locale('ar')->diffForHumans(short: true) }}</span>
                    </a>
                @endforeach
            </div>
            @include('site.partials.pagination', ['paginator' => $conversations])
        @endif
    </div>
@endsection
