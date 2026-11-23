{{--
    IBM Plex Sans Arabic — one family designed for Arabic AND Latin, so mixed
    titles ("كيا سبورتاج — Kia Sportage") look consistent. Self-hosted from
    public/fonts (licence: public/fonts/ibm-plex-sans-arabic/OFL.txt) rather
    than Google Fonts, which is often blocked or slow in Syria. The browser
    downloads only the subsets (unicode-range) and weights a page uses.
    Include inside <head>, before the page's <style>; use var(--font).
--}}
<link rel="preload" href="{{ asset('fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic-arabic-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
    @foreach ([400, 500, 600, 700] as $weight)
        @font-face {
            font-family: "IBM Plex Sans Arabic"; font-style: normal; font-weight: {{ $weight }}; font-display: swap;
            src: url("{{ asset("fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic-arabic-{$weight}-normal.woff2") }}") format("woff2");
            unicode-range: U+0600-06FF, U+0750-077F, U+0870-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FEFC;
        }
        @font-face {
            font-family: "IBM Plex Sans Arabic"; font-style: normal; font-weight: {{ $weight }}; font-display: swap;
            src: url("{{ asset("fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic-latin-{$weight}-normal.woff2") }}") format("woff2");
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
    @endforeach
    :root { --font: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, Arial, sans-serif; }
    /* Form controls don't inherit the page font by default. */
    button, input, select, textarea { font-family: inherit; }
</style>
