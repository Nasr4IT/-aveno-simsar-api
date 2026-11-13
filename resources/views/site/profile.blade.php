@extends('site.layout')
@section('title', 'حسابي')
@section('content')
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <div class="card" style="margin-bottom:16px">
            <div class="seller">
                <div class="avatar">{{ mb_substr($user->name, 0, 1) }}</div>
                <div>
                    <h1 style="margin:0">{{ $user->name }}</h1>
                    <div>@include('site.partials.rating-stars', ['average' => $user->rating_average, 'count' => $user->rating_count])</div>
                    <div class="muted small">عضو منذ {{ $user->created_at->locale('ar')->diffForHumans() }} · <a href="{{ route('site.users.show', $user) }}">صفحتي العامة</a></div>
                </div>
            </div>
            <div class="row" style="margin-top:14px">
                <a class="btn btn-outline btn-sm" href="{{ route('site.my.ads') }}">إعلاناتي</a>
                <a class="btn btn-outline btn-sm" href="{{ route('site.favorites') }}">المفضلة</a>
                <a class="btn btn-outline btn-sm" href="{{ route('site.messages') }}">الرسائل</a>
                <a class="btn btn-outline btn-sm" href="{{ route('site.notifications') }}">الإشعارات</a>
            </div>
        </div>

        <form class="card" method="POST" action="{{ route('site.profile.update') }}" style="margin-bottom:16px">
            @csrf
            <h2>بياناتي</h2>
            <label for="name">الاسم</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100">
            <label for="phone">رقم الهاتف (لا يمكن تغييره)</label>
            <input type="tel" id="phone" value="{{ $user->phone }}" disabled>
            <label for="email">البريد الإلكتروني (اختياري)</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}">
            @include('site.partials.location-select', ['governorates' => $governorates, 'selectedGovernorate' => old('governorate_id', $user->governorate_id), 'selectedCity' => old('city_id', $user->city_id)])
            <button class="btn btn-primary" type="submit" style="margin-top:16px">حفظ</button>
        </form>

        <div class="card">
            <h2>التقييمات التي وصلتني</h2>
            @forelse ($ratings as $rating)
                <div class="rating-row">
                    <div class="row"><strong>{{ $rating->raterUser->name }}</strong> @include('site.partials.rating-stars', ['average' => $rating->score]) <span class="muted small">{{ $rating->created_at->locale('ar')->diffForHumans() }}</span></div>
                    @if ($rating->comment)<p style="margin:4px 0 0">{{ $rating->comment }}</p>@endif
                </div>
            @empty
                <p class="muted">لا توجد تقييمات بعد.</p>
            @endforelse
            @include('site.partials.pagination', ['paginator' => $ratings])
        </div>
    </div>
@endsection
