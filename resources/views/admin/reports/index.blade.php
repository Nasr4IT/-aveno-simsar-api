@extends('admin.layout')
@section('title', __('البلاغات'))
@section('content')
    @php
        $reasonLabels = ['spam' => __('إعلان مكرر / سبام'), 'inappropriate' => __('محتوى غير لائق'), 'scam' => __('احتيال'), 'other' => __('سبب آخر')];
        $statusLabels = ['pending' => __('بانتظار المعالجة'), 'resolved' => __('تمت معالجتها'), 'dismissed' => __('تم تجاهلها')];
    @endphp
    <div class="tabs">
        @foreach (['pending' => __('بانتظار المعالجة'), 'resolved' => __('تمت معالجتها'), 'dismissed' => __('تم تجاهلها'), '' => __('الكل')] as $value => $label)
            <a href="{{ route('admin.reports.index', $value ? ['status' => $value] : []) }}" class="{{ $status === $value ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card table-wrap">
        <table>
            <thead><tr><th>{{ __('المُبلِّغ') }}</th><th>{{ __('الهدف') }}</th><th>{{ __('السبب') }}</th><th>{{ __('التفاصيل') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('إجراء') }}</th></tr></thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>{{ $report->reporter?->name ?? '—' }}</td>
                        <td>
                            @if ($report->reportable_type === \App\Models\Ad::class)
                                {{ __('إعلان: :name', ['name' => $report->reportable->title ?? __('محذوف')]) }}
                            @else
                                {{ __('مستخدم: :name', ['name' => $report->reportable->name ?? __('محذوف')]) }}
                            @endif
                        </td>
                        <td>{{ $reasonLabels[$report->reason] ?? $report->reason }}</td>
                        <td style="max-width:220px">{{ $report->details ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ ['pending' => 'pending', 'resolved' => 'approved', 'dismissed' => 'expired'][$report->status] }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
                        </td>
                        <td>
                            @if ($report->status === 'pending')
                                <form class="inline" method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">{{ __('تمت المعالجة') }}</button>
                                </form>
                                <form class="inline" method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                    @csrf
                                    <button class="btn btn-muted" type="submit">{{ __('تجاهل') }}</button>
                                </form>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">{{ __('لا توجد بلاغات.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('site.partials.pagination', ['paginator' => $reports])
@endsection
