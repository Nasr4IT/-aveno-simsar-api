@extends('site.layout')
@section('title', __('الإشعارات'))
@section('content')
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <div class="section-head">
            <h1>{{ __('الإشعارات') }}</h1>
            @if ($notifications->isNotEmpty())
                <form method="POST" action="{{ route('site.notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-muted btn-sm" type="submit">{{ __('تحديد الكل كمقروء') }}</button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'bell', 'size' => 40])
                {{ __('لا توجد إشعارات بعد.') }}
            </div>
        @else
            <div class="card">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $icon = match ($data['type'] ?? null) {
                            'new_message' => 'chat', 'new_rating' => 'star', 'ad_approved' => 'check', 'ad_rejected' => 'x', default => 'bell',
                        };
                        // The stored 'message' is an Arabic sentence (it is what the
                        // app shows). Rebuild it from the structured fields so it reads
                        // naturally in the visitor's language; fall back to the stored text.
                        $text = match ($data['type'] ?? null) {
                            'ad_approved' => __('تمت الموافقة على إعلانك ":title" وهو الآن منشور.', ['title' => $data['title'] ?? '']),
                            'ad_rejected' => __('تم رفض إعلانك ":title": :reason', ['title' => $data['title'] ?? '', 'reason' => $data['reason'] ?? '']),
                            'new_message' => __('رسالة جديدة من :name', ['name' => $data['sender_name'] ?? '']),
                            'new_rating' => __('قيّمك :name بـ :score نجوم', ['name' => $data['rater_name'] ?? '', 'score' => $data['score'] ?? '']),
                            default => $data['message'] ?? '',
                        };
                    @endphp
                    <div class="notif {{ $notification->read_at ? '' : 'unread' }}">
                        <div class="dot">@include('site.partials.icon', ['name' => $icon, 'size' => 18])</div>
                        <form method="POST" action="{{ route('site.notifications.read', $notification->id) }}" style="flex:1">
                            @csrf
                            <button type="submit">
                                {{ $text }}
                                <span class="muted small" style="display:block">{{ $notification->created_at->diffForHumans() }}</span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            @include('site.partials.pagination', ['paginator' => $notifications])
        @endif
    </div>
@endsection
