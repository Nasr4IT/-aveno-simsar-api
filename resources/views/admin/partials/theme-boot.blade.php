{{-- Applies the saved (or device) theme before first paint. Same storage key as the public website, so the choice is shared. --}}
<script>
    (function () {
        var root = document.documentElement, theme = null;
        try { theme = localStorage.getItem('semsar-theme'); } catch (e) {}
        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        root.setAttribute('data-theme', theme);
    })();
</script>
