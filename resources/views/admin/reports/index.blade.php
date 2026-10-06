@extends('admin.layout')
@section('title', 'البلاغات')
@section('content')
    <div class="tabs">
        @foreach (['pending' => 'بانتظار المعالجة', 'resolved' => 'تمت معالجتها', 'dismissed' => 'تم تجاهلها', '' => 'الكل'] as $value => $label)
            <a href="{{ route('admin.reports.index', $value ? ['status' => $value] : []) }}" class="{{ $status === $value ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card">
        <table>
            <thead><tr><th>المُبلِّغ</th><th>الهدف</th><th>السبب</th><th>التفاصيل</th><th>الحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>{{ $report->reporter->name }}</td>
                        <td>
                            @if ($report->reportable_type === \App\Models\Ad::class)
                                إعلان: {{ $report->reportable->title ?? 'محذوف' }}
                            @else
                                مستخدم: {{ $report->reportable->name ?? 'محذوف' }}
                            @endif
                        </td>
                        <td>{{ $report->reason }}</td>
                        <td style="max-width:220px">{{ $report->details ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ ['pending' => 'pending', 'resolved' => 'approved', 'dismissed' => 'expired'][$report->status] }}">{{ $report->status }}</span>
                        </td>
                        <td>
                            @if ($report->status === 'pending')
                                <form class="inline" method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">تمت المعالجة</button>
                                </form>
                                <form class="inline" method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                    @csrf
                                    <button class="btn btn-muted" type="submit">تجاهل</button>
                                </form>
                            @else
                                <span style="color:#9ca3af">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#9ca3af">لا توجد بلاغات.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $reports->links() }}</div>
@endsection
