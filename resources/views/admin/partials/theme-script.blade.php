<script>
    (function () {
        var root = document.documentElement;

        // Dark mode toggle — remembered per browser, shared with the website.
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('semsar-theme', next); } catch (e) {}
            });
        });

        // Dashboard numbers count up from zero.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        document.querySelectorAll('[data-count]').forEach(function (el) {
            var target = parseInt(el.dataset.count, 10) || 0, start = null;
            if (target < 2) return;
            el.textContent = '0';
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / 900, 1), eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased).toLocaleString('en-US');
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    })();
</script>
