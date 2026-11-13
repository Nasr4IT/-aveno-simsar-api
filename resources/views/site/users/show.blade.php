@extends('site.layout')
@section('title', $user->name)
@section('content')
    <div class="detail" style="margin-top:20px">
        <div>
            <div class="card" style="margin-bottom:16px">
                <div class="seller">
                    <div class="avatar">{{ mb_substr($user->name, 0, 1) }}</div>
                    <div>
                        <h1 style="margin:0">{{ $user->name }}</h1>
                        <div>@include('site.partials.rating-stars', ['average' => $user->rating_average, 'count' => $user->rating_count])</div>
                        <div class="muted small">{{ __('عضو منذ :time', ['time' => $user->created_at->diffForHumans()]) }}</div>
                    </div>
                </div>
            </div>

            <div class="section-head"><h2>{{ __('إعلانات :name', ['name' => $user->name]) }}</h2></div>
            @if ($ads->isEmpty())
                <div class="card empty">{{ __('لا توجد إعلانات منشورة حاليًا.') }}</div>
            @else
                <div class="ad-grid">
                    @foreach ($ads as $ad)
                        @include('site.partials.ad-card', ['ad' => $ad])
                    @endforeach
                </div>
                @include('site.partials.pagination', ['paginator' => $ads])
            @endif
        </div>

        <aside>
            @auth
                @if ($user->id !== auth()->id())
                    <form class="card" method="POST" action="{{ route('site.users.rate', $user) }}" style="margin-bottom:16px">
                        @csrf
                        <h3 style="margin-top:0">{{ __('قيّم :name', ['name' => $user->name]) }}</h3>
                        <label for="score">{{ __('التقييم') }}</label>
                        <select id="score" name="score" required>
                            @for ($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" @selected(old('score') == $i)>{{ trans_choice('{1} نجمة واحدة|[2,*] :count نجوم', $i, ['count' => $i]) }}</option>
                            @endfor
                        </select>
                        <label for="comment">{{ __('تعليق (اختياري)') }}</label>
                        <textarea id="comment" name="comment" rows="2" maxlength="1000">{{ old('comment') }}</textarea>
                        <button class="btn btn-primary btn-block" type="submit" style="margin-top:10px">{{ __('إرسال التقييم') }}</button>
                        <div style="margin-top:10px">
                            @include('site.partials.report-form', ['action' => route('site.users.report', $user), 'label' => __('الإبلاغ عن هذا المستخدم')])
                        </div>
                    </form>
                @endif
            @else
                <div class="card" style="margin-bottom:16px">
                    <a class="btn btn-primary btn-block" href="{{ route('site.login') }}">{{ __('سجّل الدخول للتقييم أو المراسلة') }}</a>
                </div>
            @endauth

            <div class="card">
                <h3 style="margin-top:0">{{ __('التقييمات') }}</h3>
                @forelse ($ratings as $rating)
                    <div class="rating-row">
                        <div class="row"><strong>{{ $rating->raterUser->name }}</strong> @include('site.partials.rating-stars', ['average' => $rating->score])</div>
                        @if ($rating->comment)<p style="margin:4px 0 0" class="small">{{ $rating->comment }}</p>@endif
                    </div>
                @empty
                    <p class="muted small">{{ __('لا توجد تقييمات بعد.') }}</p>
                @endforelse
                @include('site.partials.pagination', ['paginator' => $ratings])
            </div>
        </aside>
    </div>
@endsection
