@extends('admin.layout')
@section('title', 'المستخدمون')
@section('content')
    <div class="card">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex; gap:8px;">
            <input type="text" name="q" value="{{ $q }}" placeholder="بحث بالاسم أو رقم الهاتف">
            <button class="btn btn-primary" type="submit">بحث</button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr><th>الاسم</th><th>الهاتف</th><th>البريد</th><th>التقييم</th><th>الحالة</th><th>إجراء</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->email ?? '—' }}</td>
                        <td>{{ $user->rating_average ?? 0 }} ({{ $user->rating_count ?? 0 }})</td>
                        <td>
                            @if ($user->is_banned)
                                <span class="badge badge-rejected">محظور</span>
                            @else
                                <span class="badge badge-approved">نشط</span>
                            @endif
                            @if ($user->hasRole('admin'))
                                <span class="badge badge-sold">مسؤول</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->hasRole('admin'))
                                <span style="color:#9ca3af">—</span>
                            @elseif ($user->is_banned)
                                <form class="inline" method="POST" action="{{ route('admin.users.unban', $user) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">إلغاء الحظر</button>
                                </form>
                            @else
                                <form class="inline" method="POST" action="{{ route('admin.users.ban', $user) }}">
                                    @csrf
                                    <button class="btn btn-danger" type="submit">حظر</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:#9ca3af">لا يوجد مستخدمون.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $users->links() }}</div>
@endsection
