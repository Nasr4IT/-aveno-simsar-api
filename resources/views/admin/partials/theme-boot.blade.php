{{-- Applies the saved (or device) theme before first paint. Same storage key as the public website, so the choice is shared.
     Also tells the browser the page's colour scheme: with "light dark" the browser paints its blank canvas between page
     loads from the *device* setting — white on a light-mode computer even when Simsar is in dark mode (the white flash). --}}
<script>
    (function () {
        var root = document.documentElement, theme = null;
        try { theme = localStorage.getItem('semsar-theme'); } catch (e) {}
        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        root.setAttribute('data-theme', theme);
        var meta = document.querySelector('meta[name="color-scheme"]');
        if (meta) meta.setAttribute('content', theme);
    })();
</script>
