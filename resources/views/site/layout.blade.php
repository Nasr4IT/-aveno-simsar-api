<!doctype html>
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $otherLocale = $isRtl ? 'en' : 'ar';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('الرئيسية')) — Semsar</title>
    <link rel="icon" href="{{ asset('images/admin/favicon.png') }}">
    <meta name="color-scheme" content="light dark">
    <script>
        // Apply the saved (or system) theme before first paint, so dark-mode
        // visitors never see a white flash. 'js' turns on the reveal animations.
        (function () {
            var root = document.documentElement, theme = null;
            try { theme = localStorage.getItem('semsar-theme'); } catch (e) {}
            if (theme !== 'light' && theme !== 'dark') {
                theme = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            root.setAttribute('data-theme', theme);
            root.classList.add('js');
        })();
    </script>
    <style>
        /* ================= theme tokens ================= */
        :root {
            --brand-navy: #032C3A; --cyan: #1FF2FA; --teal: #0CA7B5; --teal-hover: #098893;
            --navy: #032C3A; --teal-dark: #098893; --teal-soft: #E3FAFB;
            --bg: #F3F8FA; --card: #fff; --border: #E3ECEF; --text: #032C3A; --muted: #7E97A0;
            --input-bg: #fff; --soft-bg: #E7EFF1; --soft-bg-hover: #d9e5e8; --overlay: rgba(255,255,255,.92);
            --danger: #dc2626; --danger-soft: #fee2e2; --danger-text: #991b1b; --danger-border: #fecaca;
            --success-soft: #dcfce7; --success-text: #166534; --success-border: #bbf7d0;
            --warn-soft: #fef3c7; --warn-text: #92400e; --indigo-soft: #e0e7ff; --indigo-text: #3730a3;
            --gray-soft: #f3f4f6; --gray-text: #4b5563;
            --radius: 16px; --radius-sm: 10px;
            --shadow: 0 2px 14px rgba(3,44,58,.07); --shadow-lg: 0 14px 34px rgba(3,44,58,.14);
            --ease: cubic-bezier(.2,.7,.2,1);
        }
        :root[data-theme="dark"] {
            --navy: #E8F4F7; --teal-dark: #2CC5D2; --teal-soft: rgba(12,167,181,.16);
            --bg: #061920; --card: #0B2530; --border: #17394A; --text: #DCEBEF; --muted: #86A3AD;
            --input-bg: #08202A; --soft-bg: #133443; --soft-bg-hover: #1a4152; --overlay: rgba(6,25,32,.85);
            --danger-soft: rgba(220,38,38,.16); --danger-text: #fca5a5; --danger-border: rgba(248,113,113,.3);
            --success-soft: rgba(22,163,74,.16); --success-text: #86efac; --success-border: rgba(134,239,172,.3);
            --warn-soft: rgba(217,119,6,.18); --warn-text: #fcd34d; --indigo-soft: rgba(99,102,241,.2); --indigo-text: #a5b4fc;
            --gray-soft: rgba(148,163,184,.15); --gray-text: #cbd5e1;
            --shadow: 0 2px 14px rgba(0,0,0,.35); --shadow-lg: 0 14px 34px rgba(0,0,0,.5);
        }

        /* ================= base ================= */
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: var(--bg); color: var(--text); line-height: 1.55; transition: background-color .35s, color .35s; }
        a { color: var(--teal-dark); text-decoration: none; }
        img { max-width: 100%; display: block; }
        h1, h2, h3 { color: var(--navy); margin: 0 0 8px; line-height: 1.3; }
        h1 { font-size: 24px; font-weight: 800; } h2 { font-size: 18px; font-weight: 800; } h3 { font-size: 15px; font-weight: 700; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 16px; }
        .ico { flex-shrink: 0; vertical-align: middle; }
        [dir="rtl"] .flip-rtl { transform: scaleX(-1); }

        /* ================= animations ================= */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
        @keyframes pop { 0% { transform: scale(.5); opacity: 0; } 70% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(1); } }
        @keyframes shimmer { from { background-position: 150% 0; } to { background-position: -50% 0; } }
        @keyframes drift { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(18px, -14px) scale(1.08); } }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes pulseRing { 0% { box-shadow: 0 0 0 0 rgba(12,167,181,.55); } 80%, 100% { box-shadow: 0 0 0 12px rgba(12,167,181,0); } }
        @keyframes spinIn { from { transform: rotate(-90deg) scale(.6); opacity: 0; } to { transform: none; opacity: 1; } }

        main { animation: fadeIn .4s ease both; }
        /* Scroll reveal: JS tags cards/tiles with .reveal and adds .in when they
           enter the viewport. Without JS (no .js class) nothing is hidden. */
        .js .reveal { opacity: 0; transform: translateY(18px); transition: opacity .55s var(--ease), transform .55s var(--ease); transition-delay: var(--d, 0ms); }
        .js .reveal.in { opacity: 1; transform: none; }

        /* ================= top bar ================= */
        .topbar { background: var(--card); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 20; transition: box-shadow .25s, background-color .35s; }
        .topbar.scrolled { box-shadow: var(--shadow-lg); }
        .topbar .container { display: flex; align-items: center; gap: 16px; height: 64px; }
        .brand { display: flex; align-items: center; gap: 8px; color: var(--navy); font-weight: 800; font-size: 19px; }
        .brand img { width: 34px; height: 34px; transition: transform .5s var(--ease); }
        .brand:hover img { transform: rotate(-12deg) scale(1.08); }
        .topbar form.search { flex: 1; max-width: 520px; position: relative; }
        .topbar form.search input { width: 100%; padding: 10px 16px; padding-inline-start: 40px; border: 1px solid var(--border); border-radius: 999px; font-size: 14px; font-family: inherit; background: var(--bg); color: var(--text); transition: border-color .2s, box-shadow .2s, background-color .2s; }
        .topbar form.search .ico { position: absolute; inset-inline-start: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); }
        .topbar nav { display: flex; align-items: center; gap: 4px; margin-inline-start: auto; }
        .topbar nav a { display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 999px; font-size: 14px; color: var(--navy); font-weight: 600; transition: background-color .2s, color .2s; }
        .topbar nav a:hover { background: var(--bg); }
        .topbar nav a.active { color: var(--teal-dark); background: var(--teal-soft); }
        .topbar nav a.btn-primary { color: #fff; }
        .topbar nav .badge-count { background: var(--teal); color: #fff; border-radius: 999px; font-size: 11px; padding: 1px 7px; font-weight: 700; animation: pulseRing 2.2s infinite; }
        .topbar form.inline { display: inline; }
        .tools { display: flex; align-items: center; gap: 6px; }
        .tool { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 38px; min-width: 38px; padding: 0 10px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); color: var(--navy); font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; transition: background-color .2s, border-color .2s, transform .15s; }
        .tool:hover { border-color: var(--teal); background: var(--teal-soft); }
        .tool:active { transform: scale(.94); }
        .theme-toggle .sun, :root[data-theme="dark"] .theme-toggle .moon { display: none; }
        :root[data-theme="dark"] .theme-toggle .sun { display: inline; }
        .theme-toggle .ico { animation: spinIn .45s var(--ease); }

        /* ================= buttons ================= */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; border-radius: 999px; padding: 10px 20px; font-size: 14px; cursor: pointer; font-weight: 700; font-family: inherit; line-height: 1.2; transition: background-color .2s, color .2s, border-color .2s, box-shadow .25s, transform .15s; }
        .btn:active { transform: scale(.97); }
        .btn-primary { background: var(--teal); color: #fff; }
        .btn-primary:hover { background: var(--teal-hover); color: #fff; box-shadow: 0 6px 18px rgba(12,167,181,.35); }
        .btn-outline { background: var(--card); color: var(--teal-dark); border-color: var(--teal); }
        .btn-outline:hover { background: var(--teal-soft); }
        .btn-muted { background: var(--soft-bg); color: var(--navy); }
        .btn-muted:hover { background: var(--soft-bg-hover); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { box-shadow: 0 6px 16px rgba(220,38,38,.3); }
        .btn-sm { padding: 7px 14px; font-size: 13px; }
        .btn-block { width: 100%; }
        .btn[disabled] { opacity: .6; cursor: not-allowed; }
        .btn .ico.pop { animation: pop .5s var(--ease); }

        /* ================= cards & layout ================= */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); transition: background-color .35s, border-color .35s; }
        .section { margin: 28px 0; }
        .section-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
        .section-head a { font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .section-head a .ico { transition: transform .2s; }
        .section-head a:hover .ico { transform: translateX(3px); }
        [dir="rtl"] .section-head a:hover .ico { transform: scaleX(-1) translateX(3px); }
        .muted { color: var(--muted); }
        .small { font-size: 12.5px; }
        .row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .empty { text-align: center; color: var(--muted); padding: 40px 16px; }
        .empty .ico { color: var(--border); margin: 0 auto 10px; display: block; animation: pop .6s var(--ease) both; }

        /* ================= hero ================= */
        .hero { position: relative; overflow: hidden; margin-top: 20px; padding: 34px 28px 30px; border-radius: 22px; color: #fff;
            background: linear-gradient(120deg, #032C3A, #0c4257, #098893, #0CA7B5, #0c4257); background-size: 300% 300%; animation: gradientShift 16s ease infinite; box-shadow: var(--shadow-lg); }
        .hero::before, .hero::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; }
        .hero::before { width: 260px; height: 260px; inset-inline-end: -60px; top: -90px; background: radial-gradient(circle, rgba(31,242,250,.35), transparent 70%); animation: drift 9s ease-in-out infinite; }
        .hero::after { width: 200px; height: 200px; inset-inline-start: 30%; bottom: -120px; background: radial-gradient(circle, rgba(255,255,255,.18), transparent 70%); animation: drift 12s ease-in-out infinite reverse; }
        .hero > * { position: relative; z-index: 1; }
        .hero h1 { font-size: 28px; color: #fff; animation: fadeUp .6s var(--ease) both; }
        .hero p { color: rgba(255,255,255,.82); margin: 0; animation: fadeUp .6s .1s var(--ease) both; }
        .hero form { position: relative; margin-top: 18px; max-width: 640px; animation: fadeUp .6s .2s var(--ease) both; }
        .hero form input { width: 100%; padding: 15px 16px; padding-inline-start: 48px; border: 1px solid transparent; border-radius: 999px; font-size: 15px; font-family: inherit; background: var(--card); color: var(--text); box-shadow: 0 10px 30px rgba(0,0,0,.18); transition: box-shadow .25s, transform .25s; }
        .hero form input:focus { transform: translateY(-1px); box-shadow: 0 14px 34px rgba(0,0,0,.25), 0 0 0 3px rgba(31,242,250,.45); }
        .hero form .ico { position: absolute; inset-inline-start: 18px; top: 50%; transform: translateY(-50%); color: var(--muted); z-index: 1; }
        .hero .wave { display: inline-block; animation: pop .8s .3s var(--ease) both; }

        /* ================= banners ================= */
        .banners { display: flex; gap: 12px; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 6px; }
        .banners a, .banners div { flex: 0 0 85%; max-width: 560px; scroll-snap-align: start; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
        .banners img { width: 100%; aspect-ratio: 21/9; object-fit: cover; transition: transform .6s var(--ease); }
        .banners a:hover img { transform: scale(1.04); }

        /* ================= category tiles ================= */
        .cat-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        .cat-tile { display: flex; align-items: center; gap: 12px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px; box-shadow: var(--shadow); color: var(--navy); transition: border-color .2s, box-shadow .25s, transform .25s var(--ease), background-color .35s; }
        a.cat-tile:hover { border-color: var(--teal); transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .cat-tile .icon { width: 42px; height: 42px; border-radius: 12px; background: var(--teal-soft); color: var(--teal-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background-color .25s, color .25s, transform .35s var(--ease); }
        a.cat-tile:hover .icon { background: var(--teal); color: #fff; transform: rotate(-8deg) scale(1.08); }
        .cat-tile strong { display: block; font-size: 14.5px; }
        .cat-tile span { color: var(--muted); font-size: 12.5px; }

        /* ================= ad cards ================= */
        .ad-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; }
        .rail { display: flex; gap: 14px; overflow-x: auto; padding: 4px 2px 10px; scroll-snap-type: x proximity; }
        .rail .ad-card { flex: 0 0 230px; scroll-snap-align: start; }
        .ad-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); display: flex; flex-direction: column; color: var(--text); position: relative; transition: transform .3s var(--ease), box-shadow .3s, border-color .3s, background-color .35s; }
        .ad-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--teal); }
        .ad-card .img { aspect-ratio: 4/3; overflow: hidden; position: relative;
            background: linear-gradient(90deg, var(--bg) 30%, var(--soft-bg) 50%, var(--bg) 70%); background-size: 220% 100%; animation: shimmer 1.4s linear infinite; }
        .ad-card .img.loaded { animation: none; background: var(--bg); }
        .ad-card .img img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s var(--ease), opacity .45s ease; }
        .js .ad-card .img img { opacity: 0; }
        .js .ad-card .img.loaded img { opacity: 1; }
        .ad-card:hover .img img { transform: scale(1.07); }
        .ad-card .img .noimg { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--border); }
        .ad-card .pill { position: absolute; top: 10px; inset-inline-start: 10px; background: var(--teal); color: #fff; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; box-shadow: 0 4px 10px rgba(0,0,0,.15); }
        .ad-card .pill.sold { background: #3730a3; }
        .ad-card .body { padding: 10px 12px 12px; display: flex; flex-direction: column; gap: 3px; }
        .ad-card .title { font-weight: 700; font-size: 14px; color: var(--navy); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em; }
        .ad-card .price { color: var(--teal-dark); font-weight: 800; font-size: 15px; }
        .ad-card .meta { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 12px; gap: 6px; }
        .ad-card .meta span { display: inline-flex; align-items: center; gap: 4px; min-width: 0; }

        /* ================= badges & chips ================= */
        .badge { display: inline-block; padding: 3px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
        .badge-pending { background: var(--warn-soft); color: var(--warn-text); }
        .badge-approved { background: var(--success-soft); color: var(--success-text); }
        .badge-rejected { background: var(--danger-soft); color: var(--danger-text); }
        .badge-sold { background: var(--indigo-soft); color: var(--indigo-text); }
        .badge-expired { background: var(--gray-soft); color: var(--gray-text); }
        .chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 999px; font-size: 13px; color: var(--navy); background: var(--card); border: 1px solid var(--border); white-space: nowrap; transition: background-color .2s, color .2s, border-color .2s, transform .15s; }
        .chip:hover { border-color: var(--teal); transform: translateY(-1px); }
        .chip.active { background: var(--teal); color: #fff; border-color: var(--teal); }
        .chips { display: flex; gap: 8px; overflow-x: auto; padding: 2px 2px 6px; }

        /* ================= forms ================= */
        label { font-size: 12.5px; color: var(--muted); display: block; margin-bottom: 4px; margin-top: 12px; font-weight: 600; }
        label.inline { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; }
        input[type=text], input[type=password], input[type=number], input[type=email], input[type=tel], input[type=file], textarea, select {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit; background: var(--input-bg); color: var(--text); transition: border-color .2s, box-shadow .2s, background-color .35s;
        }
        input:disabled { opacity: .65; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(12,167,181,.18); }
        input[type=checkbox], input[type=radio] { width: auto; accent-color: var(--teal); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        .field-error { color: var(--danger); font-size: 12.5px; margin-top: 4px; }
        .auth-box { max-width: 420px; margin: 36px auto; }
        .auth-box .logo { display: flex; justify-content: center; margin-bottom: 10px; }
        .auth-box .logo img { width: 60px; height: 60px; animation: pop .7s var(--ease) both; }
        .auth-box h1 { text-align: center; }
        .auth-box p.sub { text-align: center; color: var(--muted); margin: 0 0 14px; font-size: 13.5px; }
        .step { width: 30px; height: 30px; border-radius: 50%; background: var(--teal); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0; }
        .previews img { width: 72px; height: 72px; object-fit: cover; border-radius: 10px; animation: pop .4s var(--ease) both; }

        /* ================= flash ================= */
        .flash { background: var(--success-soft); color: var(--success-text); border: 1px solid var(--success-border); border-radius: var(--radius-sm); padding: 11px 16px; margin: 16px 0; font-size: 13.5px; animation: slideDown .45s var(--ease) both; }
        .errors { background: var(--danger-soft); color: var(--danger-text); border: 1px solid var(--danger-border); border-radius: var(--radius-sm); padding: 11px 16px; margin: 16px 0; font-size: 13.5px; animation: slideDown .45s var(--ease) both; }

        /* ================= explore ================= */
        .explore { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }
        .explore aside { position: sticky; top: 80px; }
        .filters summary { cursor: pointer; font-weight: 700; color: var(--navy); list-style: none; display: flex; align-items: center; gap: 8px; }
        .filters summary::-webkit-details-marker { display: none; }
        .results-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .results-head select { width: auto; padding: 8px 12px; border-radius: 999px; }

        /* ================= detail ================= */
        .detail { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
        .detail aside { position: sticky; top: 80px; }
        .gallery .main { aspect-ratio: 4/3; border-radius: var(--radius); overflow: hidden; background: var(--card); box-shadow: var(--shadow); }
        .gallery .main img { width: 100%; height: 100%; object-fit: cover; transition: opacity .25s ease; }
        .gallery .main img.swap { opacity: 0; }
        .gallery .thumbs { display: flex; gap: 8px; margin-top: 10px; overflow-x: auto; padding: 2px; }
        .gallery .thumbs img { width: 76px; height: 60px; object-fit: cover; border-radius: 10px; border: 2px solid transparent; cursor: pointer; opacity: .7; transition: opacity .2s, border-color .2s, transform .2s; }
        .gallery .thumbs img:hover { opacity: 1; transform: translateY(-2px); }
        .gallery .thumbs img.active { border-color: var(--teal); opacity: 1; }
        .price-big { color: var(--teal-dark); font-size: 26px; font-weight: 800; }
        .kv { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; font-size: 14px; }
        .kv dt { color: var(--muted); } .kv dd { margin: 0; font-weight: 600; }
        .seller { display: flex; align-items: center; gap: 12px; color: var(--text); }
        .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--teal), var(--brand-navy)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; flex-shrink: 0; }
        .stars { color: #f59e0b; display: inline-flex; gap: 1px; }
        .stars .ico.off { color: var(--border); }
        .actions { display: grid; gap: 10px; margin-top: 14px; }

        /* ================= messages & notifications ================= */
        .convo { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); color: var(--text); transition: background-color .2s, padding .2s; }
        .convo:last-child { border-bottom: none; }
        a.convo:hover { background: var(--teal-soft); margin: 0 -20px; padding: 12px 20px; }
        .convo img { width: 56px; height: 44px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .convo .who { flex: 1; min-width: 0; }
        .convo .who strong { display: block; }
        .convo .unread { background: var(--teal); color: #fff; border-radius: 999px; font-size: 11px; padding: 2px 8px; font-weight: 700; animation: pulseRing 2.2s infinite; }
        .thread { display: flex; flex-direction: column; gap: 8px; max-height: 60vh; overflow-y: auto; padding: 4px; scroll-behavior: smooth; }
        .bubble { max-width: 78%; padding: 10px 14px; border-radius: 16px; background: var(--bg); border: 1px solid var(--border); align-self: flex-start; font-size: 14px; animation: fadeUp .3s var(--ease) both; }
        .bubble.mine { background: var(--teal); color: #fff; border-color: var(--teal); align-self: flex-end; }
        .bubble.new { animation: pop .4s var(--ease) both; }
        .bubble .t { display: block; font-size: 11px; opacity: .7; margin-top: 4px; }
        .compose { display: flex; gap: 8px; margin-top: 12px; }
        .compose input { flex: 1; border-radius: 999px; }
        .notif { display: flex; gap: 12px; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .notif:last-child { border-bottom: none; }
        .notif.unread { background: var(--teal-soft); margin: 0 -20px; padding: 12px 20px; }
        .notif .dot { width: 36px; height: 36px; border-radius: 50%; background: var(--card); border: 1px solid var(--border); color: var(--teal-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notif.unread .dot { animation: pop .5s var(--ease) both; }
        .notif form { margin: 0; }
        .notif button { background: none; border: none; padding: 0; font: inherit; color: var(--text); cursor: pointer; text-align: start; }
        .rating-row { padding: 10px 0; border-bottom: 1px solid var(--border); }
        .rating-row:last-child { border-bottom: none; }

        /* ================= tables, pagination ================= */
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: start; padding: 11px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 700; font-size: 12px; }
        tbody tr { transition: background-color .2s; }
        tbody tr:hover { background: var(--bg); }
        .table-wrap { overflow-x: auto; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin: 24px 0; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); font-size: 13.5px; color: var(--navy); transition: border-color .2s, transform .15s; }
        .pagination a:hover { border-color: var(--teal); transform: translateY(-1px); }
        .pagination span.current { background: var(--teal); color: #fff; border-color: var(--teal); }
        .pagination span.disabled { color: var(--muted); }

        /* ================= footer ================= */
        .site-footer { margin-top: 48px; border-top: 1px solid var(--border); background: var(--card); transition: background-color .35s; }
        .site-footer .container { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding-top: 26px; padding-bottom: 26px; }
        .foot-brand { display: flex; align-items: center; gap: 10px; color: var(--navy); font-weight: 800; }
        .foot-brand img { width: 30px; height: 30px; }
        .foot-brand span { color: var(--muted); font-weight: 500; font-size: 13px; }
        .foot-links { display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: 13.5px; }
        .foot-links a { color: var(--muted); transition: color .2s; }
        .foot-links a:hover { color: var(--teal-dark); }
        .powered { width: 100%; text-align: center; padding-top: 16px; border-top: 1px dashed var(--border); color: var(--muted); font-size: 13px; letter-spacing: .3px; }
        .powered strong { font-weight: 800; background: linear-gradient(90deg, var(--teal), var(--cyan), var(--teal-hover), var(--teal)); background-size: 300% 100%; -webkit-background-clip: text; background-clip: text; color: transparent; animation: gradientShift 6s linear infinite; }
        .powered .copy { display: block; font-size: 11.5px; margin-top: 4px; letter-spacing: 0; }

        /* ================= bottom nav (mobile) ================= */
        .bottom-nav { display: none; }

        @media (max-width: 860px) {
            .explore, .detail { grid-template-columns: 1fr; }
            .explore aside, .detail aside { position: static; }
            .topbar nav .label { display: none; }
            .topbar nav a.hide-mobile { display: none; }
        }
        @media (max-width: 640px) {
            body { padding-bottom: 72px; }
            .topbar .container { gap: 10px; }
            .topbar form.search { display: none; }
            .topbar nav { display: none; }
            .tools { margin-inline-start: auto; }
            .tool .label { display: none; }
            .form-grid { grid-template-columns: 1fr; }
            .hero { padding: 26px 18px 22px; }
            .hero h1 { font-size: 22px; }
            .site-footer .container { justify-content: center; text-align: center; }
            .bottom-nav { display: flex; position: fixed; bottom: 0; inset-inline: 0; background: var(--card); border-top: 1px solid var(--border); z-index: 30; justify-content: space-around; padding: 6px 4px 8px; }
            .bottom-nav a { display: flex; flex-direction: column; align-items: center; gap: 2px; font-size: 11px; color: var(--muted); font-weight: 600; min-width: 56px; transition: color .2s; }
            .bottom-nav a .ico { transition: transform .25s var(--ease); }
            .bottom-nav a.active { color: var(--teal-dark); }
            .bottom-nav a.active .ico { transform: translateY(-2px) scale(1.1); }
            .bottom-nav a.sell .ico { width: 44px; height: 44px; border-radius: 50%; background: var(--teal); color: #fff; padding: 10px; margin-top: -18px; box-shadow: 0 4px 12px rgba(12,167,181,.4); animation: pulseRing 2.6s infinite; }
        }

        /* Honour the OS "reduce motion" setting: no movement, content shown at once. */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; scroll-behavior: auto !important; }
            .js .reveal { opacity: 1; transform: none; }
        }
    </style>
    @stack('head')
</head>
<body>
@php
    $navUser = auth()->user();
    $unread = $navUser ? $navUser->unreadNotifications()->count() : 0;
@endphp
<header class="topbar" id="topbar">
    <div class="container">
        <a class="brand" href="{{ route('site.home') }}">
            <img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"> Semsar
        </a>
        <form class="search" method="GET" action="{{ route('site.explore') }}">
            @include('site.partials.icon', ['name' => 'search', 'size' => 18])
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('عمّ تبحث؟') }}" aria-label="{{ __('بحث') }}">
        </form>
        <nav>
            <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'home', 'size' => 18]) <span class="label">{{ __('الرئيسية') }}</span></a>
            <a href="{{ route('site.explore') }}" class="{{ request()->routeIs('site.explore') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'search', 'size' => 18]) <span class="label">{{ __('استكشف') }}</span></a>
            <a href="{{ route('site.sell') }}" class="{{ request()->routeIs('site.sell*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'plus', 'size' => 18]) <span class="label">{{ __('بيع') }}</span></a>
            @if ($navUser)
                <a href="{{ route('site.messages') }}" class="{{ request()->routeIs('site.messages*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'chat', 'size' => 18]) <span class="label">{{ __('الرسائل') }}</span></a>
                <a href="{{ route('site.notifications') }}" class="{{ request()->routeIs('site.notifications*') ? 'active' : '' }}" aria-label="{{ __('الإشعارات') }}">@include('site.partials.icon', ['name' => 'bell', 'size' => 18]) @if ($unread) <span class="badge-count">{{ $unread }}</span> @endif</a>
                <a href="{{ route('site.profile') }}" class="{{ request()->routeIs('site.my.*') || request()->routeIs('site.profile*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'user', 'size' => 18]) <span class="label">{{ $navUser->name }}</span></a>
                <form class="inline" method="POST" action="{{ route('site.logout') }}">
                    @csrf
                    <button class="btn btn-muted btn-sm" type="submit">{{ __('خروج') }}</button>
                </form>
            @else
                <a href="{{ route('site.login') }}" class="btn btn-primary btn-sm">{{ __('تسجيل الدخول') }}</a>
            @endif
        </nav>
        <div class="tools">
            <a class="tool" href="{{ route('site.locale', $otherLocale) }}" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}" title="{{ $isRtl ? 'Switch to English' : 'التبديل إلى العربية' }}">
                @include('site.partials.icon', ['name' => 'globe', 'size' => 17]) <span class="label">{{ $isRtl ? 'English' : 'العربية' }}</span>
            </a>
            <button class="tool theme-toggle" type="button" id="theme-toggle" aria-label="{{ __('تبديل الوضع الليلي') }}" title="{{ __('تبديل الوضع الليلي') }}">
                <span class="moon">@include('site.partials.icon', ['name' => 'moon', 'size' => 17])</span>
                <span class="sun">@include('site.partials.icon', ['name' => 'sun', 'size' => 17])</span>
            </button>
        </div>
    </div>
</header>

<main class="container">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! View::hasSection('hide-errors'))
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <div class="foot-brand">
            <img src="{{ asset('images/admin/semsar-logo.png') }}" alt="" aria-hidden="true">
            Semsar <span>— {{ __('سوق الإعلانات المبوّبة') }}</span>
        </div>
        <nav class="foot-links" aria-label="{{ __('روابط') }}">
            <a href="{{ route('site.home') }}">{{ __('الرئيسية') }}</a>
            <a href="{{ route('site.explore') }}">{{ __('استكشف') }}</a>
            <a href="{{ route('site.sell') }}">{{ __('بيع غرضك') }}</a>
            <a href="{{ route('site.locale', $otherLocale) }}" lang="{{ $otherLocale }}">{{ $isRtl ? 'English' : 'العربية' }}</a>
        </nav>
        <div class="powered" dir="ltr">
            Powered by <strong>AvenoCode</strong>
            <span class="copy">© {{ date('Y') }} Semsar</span>
        </div>
    </div>
</footer>

<nav class="bottom-nav">
    <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'home', 'size' => 22]) {{ __('الرئيسية') }}</a>
    <a href="{{ route('site.explore') }}" class="{{ request()->routeIs('site.explore') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'search', 'size' => 22]) {{ __('استكشف') }}</a>
    <a href="{{ route('site.sell') }}" class="sell">@include('site.partials.icon', ['name' => 'plus', 'size' => 22]) {{ __('بيع') }}</a>
    <a href="{{ route('site.messages') }}" class="{{ request()->routeIs('site.messages*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'chat', 'size' => 22]) {{ __('الرسائل') }}</a>
    <a href="{{ $navUser ? route('site.profile') : route('site.login') }}" class="{{ request()->routeIs('site.my.*') || request()->routeIs('site.profile*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'user', 'size' => 22]) {{ __('حسابي') }}</a>
</nav>

<script>
    (function () {
        var root = document.documentElement;

        // Dark mode toggle — remembered per browser.
        document.getElementById('theme-toggle').addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('semsar-theme', next); } catch (e) {}
        });

        // Top bar gains a shadow once the page scrolls.
        var bar = document.getElementById('topbar');
        var onScroll = function () { bar.classList.toggle('scrolled', window.scrollY > 4); };
        window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

        // Listing photos fade in over a shimmer placeholder once loaded.
        document.querySelectorAll('.ad-card .img').forEach(function (box) {
            var img = box.querySelector('img');
            var done = function () { box.classList.add('loaded'); };
            if (!img || img.complete) { done(); } else { img.addEventListener('load', done); img.addEventListener('error', done); }
        });

        // Scroll reveal: cards and tiles rise in, staggered within each row.
        var targets = document.querySelectorAll('main .ad-card, main .cat-tile, main > .card, main .section > .card, main .section-head, main .detail .card, main .explore aside');
        if (!('IntersectionObserver' in window)) return;
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                el.classList.add('in');
                io.unobserve(el);
                // Drop the reveal classes afterwards so hover transitions run without delay.
                setTimeout(function () { el.classList.remove('reveal', 'in'); el.style.removeProperty('--d'); }, 900);
            });
        }, { rootMargin: '0px 0px -40px 0px' });
        targets.forEach(function (el) {
            var siblings = el.parentElement ? Array.prototype.indexOf.call(el.parentElement.children, el) : 0;
            el.style.setProperty('--d', (Math.min(siblings, 7) * 60) + 'ms');
            el.classList.add('reveal');
            io.observe(el);
        });
    })();
</script>
@stack('scripts')
</body>
</html>
