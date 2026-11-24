@extends('site.layout')
@section('title', __('تمييز الإعلان'))
@section('content')
    @php
        $statusLabels = ['pending' => __('بانتظار المراجعة'), 'paid' => __('مؤكَّد'), 'rejected' => __('مرفوض')];
        $badge = ['pending' => 'pending', 'paid' => 'approved', 'rejected' => 'rejected'];
        $pending = $history->firstWhere('status', 'pending');
    @endphp
    <div class="section" style="margin-top:20px; max-width:760px; margin-inline:auto">
        <h1>{{ __('تمييز الإعلان') }}</h1>
        <p class="muted" style="margin-top:0">
            <a href="{{ route('site.ads.show', $ad) }}">{{ $ad->title }}</a>
            @if ($ad->isFeaturedNow())
                · <span class="badge badge-approved">{{ __('مميّز حتى :date', ['date' => $ad->featured_until->format('Y-m-d')]) }}</span>
            @endif
        </p>

        @if ($ad->status !== 'approved')
            <div class="card empty">{{ __('يمكن تمييز الإعلانات المنشورة فقط.') }}</div>
        @elseif ($pending)
            <div class="card">
                <h2 style="margin-top:0">{{ __('طلبك قيد المراجعة') }}</h2>
                <p class="muted" style="margin:0">{{ __('أرسلت إيصال :package في :date. ستراجعه الإدارة وتبلغك بإشعار.', ['package' => $pending->adPackage->localized('name'), 'date' => $pending->created_at->format('Y-m-d')]) }}</p>
            </div>
        @else
            <div class="card" style="margin-bottom:16px">
                <h2 class="row" style="margin-top:0"><span class="step">1</span> {{ __('اختر الباقة') }}</h2>
                <p class="muted" style="margin-top:0">{{ __('الإعلان المميّز يظهر أولًا في القوائم ونتائج البحث، مع شارة "مميّز".') }}</p>
                @if ($ad->isFeaturedNow())
                    <p class="small" style="margin-top:0">{{ __('إعلانك مميّز حاليًا؛ أي باقة جديدة تُضاف إلى المدة المتبقية.') }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('site.my.ads.feature.store', $ad) }}" enctype="multipart/form-data">
                @csrf
                <div class="cat-tiles" style="margin-bottom:16px">
                    @foreach ($packages as $package)
                        <label class="cat-tile" style="cursor:pointer; margin:0">
                            <input type="radio" name="ad_package_id" value="{{ $package->id }}" @checked(old('ad_package_id', $loop->first ? $package->id : null) == $package->id) required>
                            <div>
                                <strong>{{ $package->localized('name') }}</strong>
                                <span dir="ltr">{{ number_format($package->price, 0) }} {{ $package->currency }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="card" style="margin-bottom:16px">
                    <h2 class="row" style="margin-top:0"><span class="step">2</span> {{ __('ادفع عبر شام كاش') }}</h2>
                    @if ($shamcash['account_number'])
                        <dl class="kv">
                            @if ($shamcash['account_name'])
                                <dt>{{ __('اسم الحساب') }}</dt><dd>{{ $shamcash['account_name'] }}</dd>
                            @endif
                            <dt>{{ __('رقم الحساب') }}</dt><dd dir="ltr" style="text-align:start"><strong>{{ $shamcash['account_number'] }}</strong></dd>
                        </dl>
                        @if ($shamcash['instructions'])
                            <p class="small" style="margin-bottom:0; white-space:pre-line">{{ $shamcash['instructions'] }}</p>
                        @endif
                    @else
                        <p class="muted" style="margin:0">{{ __('بيانات حساب الدفع غير متوفرة حاليًا، يرجى المحاولة لاحقًا.') }}</p>
                    @endif
                </div>

                <div class="card" style="margin-bottom:16px">
                    <h2 class="row" style="margin-top:0"><span class="step">3</span> {{ __('أرسل صورة الإيصال') }}</h2>
                    <label for="receipt">{{ __('صورة إيصال التحويل من تطبيق شام كاش') }}</label>
                    <input type="file" id="receipt" name="receipt" accept="image/*" required>
                    <label for="transaction_reference">{{ __('رقم العملية (اختياري)') }}</label>
                    <input type="text" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" maxlength="100" dir="ltr">
                    <label for="note">{{ __('ملاحظة للإدارة (اختياري)') }}</label>
                    <textarea id="note" name="note" rows="2" maxlength="500">{{ old('note') }}</textarea>
                </div>

                <button class="btn btn-primary btn-block" type="submit" style="padding:14px" @disabled(! $shamcash['account_number'])>{{ __('إرسال الطلب') }}</button>
            </form>
        @endif

        @if ($history->isNotEmpty())
            <div class="card" style="margin-top:16px">
                <h3 style="margin-top:0">{{ __('طلباتك السابقة') }}</h3>
                @foreach ($history as $payment)
                    <div class="rating-row">
                        <div class="row" style="justify-content:space-between">
                            <span>{{ $payment->adPackage->localized('name') }} · <span dir="ltr">{{ number_format($payment->amount, 0) }} {{ $payment->currency }}</span></span>
                            <span class="badge badge-{{ $badge[$payment->status] ?? 'expired' }}">{{ $statusLabels[$payment->status] ?? $payment->status }}</span>
                        </div>
                        <div class="muted small">
                            {{ $payment->created_at->format('Y-m-d') }}
                            · <a href="{{ route('site.my.payments.receipt', $payment) }}" target="_blank" rel="noopener">{{ __('عرض الإيصال') }}</a>
                        </div>
                        @if ($payment->status === 'rejected' && $payment->rejection_reason)
                            <div class="small" style="color:var(--danger)">{{ __('سبب الرفض: :reason', ['reason' => $payment->rejection_reason]) }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
