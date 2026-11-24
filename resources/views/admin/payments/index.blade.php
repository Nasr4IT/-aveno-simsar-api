@extends('admin.layout')
@section('title', __('المدفوعات'))
@section('content')
    @php
        $statusLabels = ['pending' => __('بانتظار المراجعة'), 'paid' => __('مؤكَّد'), 'rejected' => __('مرفوض')];
        $badge = ['pending' => 'pending', 'paid' => 'approved', 'rejected' => 'rejected'];
    @endphp

    <div class="card" style="padding:14px 18px">
        <span class="muted">{{ __('قارن كل إيصال مع حساب شام كاش قبل التأكيد. عند التأكيد يصبح الإعلان مميّزًا لمدة الباقة ويُبلَّغ البائع.') }}</span>
        <a href="{{ route('admin.settings.edit') }}" style="margin-inline-start:8px">{{ __('بيانات حساب شام كاش') }}</a>
    </div>

    <div class="tabs">
        @foreach (['pending' => __('بانتظار المراجعة'), 'paid' => __('مؤكَّدة'), 'rejected' => __('مرفوضة'), '' => __('الكل')] as $value => $label)
            <a href="{{ route('admin.payments.index', ['status' => $value]) }}" class="{{ $status === $value ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr><th>{{ __('الإيصال') }}</th><th>{{ __('البائع') }}</th><th>{{ __('الإعلان') }}</th><th>{{ __('الباقة') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('رقم العملية') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('إجراء') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>
                            @if ($payment->receipt_path)
                                <a href="{{ route('admin.payments.receipt', $payment) }}" target="_blank" rel="noopener" title="{{ __('فتح الإيصال بالحجم الكامل') }}">
                                    <img class="thumb" src="{{ route('admin.payments.receipt', $payment) }}" alt="{{ __('الإيصال') }}">
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $payment->user->name }}<br><span class="muted" dir="ltr">{{ $payment->user->phone }}</span></td>
                        <td>
                            @if ($payment->ad)
                                <a href="{{ route('site.ads.show', $payment->ad) }}" target="_blank" rel="noopener">{{ $payment->ad->title }}</a>
                                @if ($payment->ad->isFeaturedNow())
                                    <div class="muted" style="font-size:12px">{{ __('مميّز حتى :date', ['date' => $payment->ad->featured_until->format('Y-m-d')]) }}</div>
                                @endif
                            @endif
                        </td>
                        <td>{{ $payment->adPackage?->localized('name') ?? $payment->adPackage?->name_ar }}</td>
                        <td dir="ltr" style="text-align:start"><strong>{{ number_format($payment->amount, 0) }} {{ $payment->currency }}</strong></td>
                        <td dir="ltr" style="text-align:start">{{ $payment->provider_reference ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $badge[$payment->status] ?? 'expired' }}">{{ $statusLabels[$payment->status] ?? $payment->status }}</span>
                            <div class="muted" style="font-size:12px">{{ $payment->created_at->format('Y-m-d H:i') }}</div>
                            @if ($payment->note)
                                <div style="font-size:12px; margin-top:4px">{{ __('ملاحظة البائع: :note', ['note' => $payment->note]) }}</div>
                            @endif
                            @if ($payment->status === 'rejected' && $payment->rejection_reason)
                                <div style="font-size:12px; color:var(--danger-text)">{{ __('سبب الرفض: :reason', ['reason' => $payment->rejection_reason]) }}</div>
                            @endif
                            @if ($payment->reviewer)
                                <div class="muted" style="font-size:12px">{{ __('راجعه: :name', ['name' => $payment->reviewer->name]) }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($payment->status === 'pending')
                                <form class="inline" method="POST" action="{{ route('admin.payments.approve', $payment) }}" onsubmit="return confirm({{ Js::from(__('تأكيد أن المبلغ وصل إلى حساب شام كاش؟ سيُميَّز الإعلان فورًا.')) }});">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">{{ __('تأكيد الدفع') }}</button>
                                </form>
                                <details class="reject-box">
                                    <summary>{{ __('رفض') }}</summary>
                                    <div class="card">
                                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}">
                                            @csrf
                                            <label>{{ __('سبب الرفض (يصل للبائع)') }}</label>
                                            <textarea name="reason" rows="2" required placeholder="{{ __('مثال: لم يصل المبلغ، أو الإيصال غير واضح') }}"></textarea>
                                            <button class="btn btn-danger" style="margin-top:8px" type="submit">{{ __('تأكيد الرفض') }}</button>
                                        </form>
                                    </div>
                                </details>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">{{ __('لا توجد طلبات.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('site.partials.pagination', ['paginator' => $payments])
@endsection
