{{-- A user's avatar photo, or their initial when they haven't uploaded one. Expects $user; optional $size (px). --}}
@php $size = $size ?? 48; @endphp
<div class="avatar" style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * .38) }}px">
    @if ($user->avatar_path)
        <img src="{{ \App\Support\MediaUrl::for($user->avatar_path) }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
    @else
        {{ mb_substr($user->name, 0, 1) }}
    @endif
</div>
