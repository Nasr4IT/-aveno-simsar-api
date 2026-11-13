@extends('site.layout')
@section('title', 'الإشعارات')
@section('content')
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <div class="section-head">
            <h1>الإشعارات</h1>
            @if ($notifications->isNotEmpty())
                <form method="POST" action="{{ route('site.notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-muted btn-sm" type="submit">تحديد الكل كمقروء</button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'bell', 'size' => 40])
                لا توجد إشعارات بعد.
            </div>
        @else
            <div class="card">
                @foreach ($notifications as $notification)
                    @php
                        $icon = match ($notification->data['type'] ?? null) {
                            'new_message' => 'chat', 'new_rating' => 'star', 'ad_approved' => 'check', 'ad_rejected' => 'x', default => 'bell',
                        };
                    @endphp
                    <div class="notif {{ $notification->read_at ? '' : 'unread' }}">
                        <div class="dot">@include('site.partials.icon', ['name' => $icon, 'size' => 18])</div>
                        <form method="POST" action="{{ route('site.notifications.read', $notification->id) }}" style="flex:1">
                            @csrf
                            <button type="submit">
                                {{ $notification->data['message'] ?? '' }}
                                <span class="muted small" style="display:block">{{ $notification->created_at->locale('ar')->diffForHumans() }}</span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            @include('site.partials.pagination', ['paginator' => $notifications])
        @endif
    </div>
@endsection
