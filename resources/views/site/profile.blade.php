@extends('site.layout')
@section('title', __('حسابي'))
@section('content')
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <div class="card" style="margin-bottom:16px">
            <div class="seller">
                @include('site.partials.avatar', ['user' => $user, 'size' => 64])
                <div>
                    <h1 style="margin:0">{{ $user->name }}</h1>
                    <div>@include('site.partials.rating-stars', ['average' => $user->rating_average, 'count' => $user->rating_count])</div>
                    <div class="muted small">{{ __('عضو منذ :time', ['time' => $user->created_at->diffForHumans()]) }} · <a href="{{ route('site.users.show', $user) }}">{{ __('صفحتي العامة') }}</a></div>
                </div>
            </div>
            <div class="row" style="margin-top:14px">
                <a class="btn btn-outline btn-sm" href="{{ route('site.my.ads') }}">{{ __('إعلاناتي') }}</a>
                <a class="btn btn-outline btn-sm" href="{{ route('site.favorites') }}">{{ __('المفضلة') }}</a>
                <a class="btn btn-outline btn-sm" href="{{ route('site.messages') }}">{{ __('الرسائل') }}</a>
                <a class="btn btn-outline btn-sm" href="{{ route('site.notifications') }}">{{ __('الإشعارات') }}</a>
            </div>
        </div>

        <form class="card" method="POST" action="{{ route('site.profile.update') }}" enctype="multipart/form-data" style="margin-bottom:16px">
            @csrf
            <h2>{{ __('بياناتي') }}</h2>
            <label for="avatar">{{ __('الصورة الشخصية') }}</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
            @if ($user->avatar_path)
                <label class="inline"><input type="checkbox" name="remove_avatar" value="1"> {{ __('إزالة الصورة الحالية') }}</label>
            @endif
            <label for="name">{{ __('الاسم') }}</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100">
            <label for="phone">{{ __('رقم الهاتف (لا يمكن تغييره)') }}</label>
            <input type="tel" id="phone" value="{{ $user->phone }}" disabled>
            <label for="email">{{ __('البريد الإلكتروني (اختياري)') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}">
            @include('site.partials.location-select', ['governorates' => $governorates, 'selectedGovernorate' => old('governorate_id', $user->governorate_id), 'selectedCity' => old('city_id', $user->city_id)])
            <button class="btn btn-primary" type="submit" style="margin-top:16px">{{ __('حفظ') }}</button>
        </form>

        <form class="card" method="POST" action="{{ route('site.profile.password') }}" style="margin-bottom:16px">
            @csrf
            <h2>{{ __('تغيير كلمة المرور') }}</h2>
            @if ($errors->password->any())
                <div class="errors">@foreach ($errors->password->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            <label for="current_password">{{ __('كلمة المرور الحالية') }}</label>
            <input type="password" id="current_password" name="current_password" required>
            <div class="form-grid">
                <div>
                    <label for="new_password">{{ __('كلمة المرور الجديدة') }}</label>
                    <input type="password" id="new_password" name="password" required minlength="8">
                </div>
                <div>
                    <label for="new_password_confirmation">{{ __('تأكيد كلمة المرور') }}</label>
                    <input type="password" id="new_password_confirmation" name="password_confirmation" required minlength="8">
                </div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:16px">{{ __('تغيير كلمة المرور') }}</button>
        </form>

        <div class="card" style="margin-bottom:16px">
            <h2>{{ __('التقييمات التي وصلتني') }}</h2>
            @forelse ($ratings as $rating)
                <div class="rating-row">
                    <div class="row"><strong>{{ $rating->raterUser->name }}</strong> @include('site.partials.rating-stars', ['average' => $rating->score]) <span class="muted small">{{ $rating->created_at->diffForHumans() }}</span></div>
                    @if ($rating->comment)<p style="margin:4px 0 0">{{ $rating->comment }}</p>@endif
                </div>
            @empty
                <p class="muted">{{ __('لا توجد تقييمات بعد.') }}</p>
            @endforelse
            @include('site.partials.pagination', ['paginator' => $ratings])
        </div>

        <div class="card" style="border-color:var(--danger-border)">
            <details @if ($errors->deleteAccount->any()) open @endif>
                <summary style="cursor:pointer; font-weight:700; color:var(--danger)">{{ __('حذف الحساب') }}</summary>
                <p class="muted small">{{ __('سيُحذف حسابك وإعلاناتك ومفضلتك وتقييماتك نهائيًا. تبقى المحادثات لدى الطرف الآخر باسم "مستخدم محذوف". لا يمكن التراجع عن ذلك.') }}</p>
                @if ($errors->deleteAccount->any())
                    <div class="errors">{{ $errors->deleteAccount->first() }}</div>
                @endif
                <form method="POST" action="{{ route('site.profile.destroy') }}" onsubmit="return confirm({{ Js::from(__('حذف حسابك نهائيًا؟ لا يمكن التراجع.')) }});">
                    @csrf
                    <label for="delete_password">{{ __('أدخل كلمة المرور للتأكيد') }}</label>
                    <input type="password" id="delete_password" name="password" required>
                    <button class="btn btn-danger" type="submit" style="margin-top:12px">{{ __('حذف حسابي نهائيًا') }}</button>
                </form>
            </details>
        </div>
    </div>
@endsection
