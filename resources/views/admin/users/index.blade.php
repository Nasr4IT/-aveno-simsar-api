@extends('admin.layout')
@section('title', __('المستخدمون'))
@section('content')
    <div class="card">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display:flex; gap:8px;">
            <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('بحث بالاسم أو رقم الهاتف') }}">
            <button class="btn btn-primary" type="submit">{{ __('بحث') }}</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr><th>{{ __('الاسم') }}</th><th>{{ __('الهاتف') }}</th><th>{{ __('البريد') }}</th><th>{{ __('التقييم') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('إجراء') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td dir="ltr" style="text-align:start">{{ $user->phone }}</td>
                        <td>{{ $user->email ?? '—' }}</td>
                        <td>{{ $user->rating_average ?? 0 }} ({{ $user->rating_count ?? 0 }})</td>
                        <td>
                            @if ($user->is_banned)
                                <span class="badge badge-rejected">{{ __('محظور') }}</span>
                            @else
                                <span class="badge badge-approved">{{ __('نشط') }}</span>
                            @endif
                            @if ($user->hasRole('admin'))
                                <span class="badge badge-sold">{{ __('مسؤول') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->hasRole('admin'))
                                <span class="muted">—</span>
                            @elseif ($user->is_banned)
                                <form class="inline" method="POST" action="{{ route('admin.users.unban', $user) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">{{ __('إلغاء الحظر') }}</button>
                                </form>
                            @else
                                <form class="inline" method="POST" action="{{ route('admin.users.ban', $user) }}">
                                    @csrf
                                    <button class="btn btn-danger" type="submit">{{ __('حظر') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">{{ __('لا يوجد مستخدمون.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('site.partials.pagination', ['paginator' => $users])
@endsection
