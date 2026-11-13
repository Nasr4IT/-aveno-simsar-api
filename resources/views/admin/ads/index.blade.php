@extends('admin.layout')
@section('title', __('مراجعة الإعلانات'))
@section('content')
    @php
        $statusLabels = ['pending' => __('بانتظار المراجعة'), 'approved' => __('منشور'), 'rejected' => __('مرفوض'), 'sold' => __('مُباع'), 'expired' => __('منتهي')];
    @endphp
    <div class="tabs">
        @foreach (['pending' => __('بانتظار المراجعة'), 'approved' => __('منشورة'), 'rejected' => __('مرفوضة'), 'sold' => __('مباعة'), '' => __('الكل')] as $value => $label)
            <a href="{{ route('admin.ads.index', $value ? ['status' => $value] : []) }}" class="{{ $status === $value ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr><th>{{ __('الإعلان') }}</th><th>{{ __('البائع') }}</th><th>{{ __('الفئة') }}</th><th>{{ __('السعر') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('تاريخ النشر') }}</th><th>{{ __('إجراء') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($ads as $ad)
                    <tr>
                        <td>
                            @if ($ad->images->first())
                                <img class="thumb" src="{{ $ad->images->first()->url }}" alt="">
                            @endif
                            {{ $ad->title }}
                        </td>
                        <td>{{ $ad->user->name }}<br><span class="muted">{{ $ad->user->phone }}</span></td>
                        <td>{{ $ad->category?->localized('name') ?? '—' }}</td>
                        <td>{{ $ad->price !== null ? number_format($ad->price, 0).' '.$ad->currency : '—' }}</td>
                        <td><span class="badge badge-{{ $ad->status }}">{{ $statusLabels[$ad->status] ?? $ad->status }}</span></td>
                        <td>{{ $ad->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if ($ad->status === 'pending')
                                <form class="inline" method="POST" action="{{ route('admin.ads.approve', $ad) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">{{ __('موافقة') }}</button>
                                </form>
                                <details class="reject-box">
                                    <summary>{{ __('رفض') }}</summary>
                                    <div class="card">
                                        <form method="POST" action="{{ route('admin.ads.reject', $ad) }}">
                                            @csrf
                                            <label>{{ __('سبب الرفض') }}</label>
                                            <textarea name="reason" rows="2" required></textarea>
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
                    <tr><td colspan="7" class="muted">{{ __('لا توجد إعلانات.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('site.partials.pagination', ['paginator' => $ads])
@endsection
