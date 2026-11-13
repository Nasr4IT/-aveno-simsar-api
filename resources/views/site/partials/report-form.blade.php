{{-- Collapsible "report this" form. Expects $action (POST url) and $label. --}}
<details>
    <summary class="muted small" style="cursor:pointer">@include('site.partials.icon', ['name' => 'flag', 'size' => 13]) {{ $label }}</summary>
    <form method="POST" action="{{ $action }}" style="margin-top:8px">
        @csrf
        <label for="reason">السبب</label>
        <select id="reason" name="reason" required>
            <option value="spam">إعلان مكرر / سبام</option>
            <option value="inappropriate">محتوى غير لائق</option>
            <option value="scam">احتيال</option>
            <option value="other">سبب آخر</option>
        </select>
        <label for="details">تفاصيل (اختياري)</label>
        <textarea id="details" name="details" rows="2" maxlength="1000"></textarea>
        <button class="btn btn-muted btn-sm" type="submit" style="margin-top:8px">إرسال البلاغ</button>
    </form>
</details>
