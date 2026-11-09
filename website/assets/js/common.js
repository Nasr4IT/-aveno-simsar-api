/* Shared helpers: API, language, theme, header/footer, listing cards. */
(function () {
  const CFG = window.SIMSAR_CONFIG;
  const BRAND = CFG.SITE_NAME || "Simsar"; // always English
  const LANG = document.documentElement.lang === "en" ? "en" : "ar";
  const DICT = window.SIMSAR_I18N[LANG];
  const base = CFG.API_BASE_URL.replace(/\/+$/, "");
  const num = new Intl.NumberFormat("en-US", { maximumFractionDigits: 2 });

  // ---------- Language ----------
  function t(key, vars) {
    let s = DICT[key] ?? window.SIMSAR_I18N.en[key] ?? key;
    if (vars) Object.entries(vars).forEach(([k, v]) => { s = s.split("{" + k + "}").join(v); });
    return s.split("Simsar").join(BRAND);
  }
  // English mode: use the API's English name, else our fallback, else the Arabic text
  const FB = window.SIMSAR_EN_FALLBACK || {};
  const enText = (ar) => (ar && FB[String(ar).trim()]) || ar || "";
  const tr = (v) => (LANG === "en" ? enText(v) : v);
  const nameOf = (o) => (o ? (LANG === "en" ? o.name_en || enText(o.name_ar) : o.name_ar || o.name_en) || "" : "");
  const labelOf = (o) => (o ? (LANG === "en" ? o.label_en || enText(o.label_ar) : o.label_ar || o.label_en) || "" : "");
  function applyI18n(root = document) {
    root.querySelectorAll("[data-i18n]").forEach((el) => { el.textContent = t(el.dataset.i18n); });
    root.querySelectorAll("[data-i18n-ph]").forEach((el) => { el.placeholder = t(el.dataset.i18nPh); });
    root.querySelectorAll("[data-i18n-aria]").forEach((el) => { el.setAttribute("aria-label", t(el.dataset.i18nAria)); });
  }
  function setLang(l) { try { localStorage.setItem("simsar_lang", l); } catch {} location.reload(); }
  function toggleTheme() {
    const next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", next);
    try { localStorage.setItem("simsar_theme", next); } catch {}
  }

  // ---------- API ----------
  async function api(path, params) {
    const url = new URL(base + path);
    if (params) Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== "") url.searchParams.set(k, v); });
    const res = await fetch(url.toString(), { headers: { Accept: "application/json", "Accept-Language": LANG } });
    if (!res.ok) { const e = new Error("HTTP " + res.status); e.status = res.status; throw e; }
    return res.json();
  }
  let catPromise = null;
  const categories = () => (catPromise ||= api("/categories").then((r) => r.data || []).catch(() => []));
  // Governorates (+ cities) from GET /governorates; falls back to config.js.
  let govPromise = null;
  const governorates = () => (govPromise ||= api("/governorates")
    .then(({ data }) => data.map((g) => ({ id: g.id, name: nameOf(g), cities: (g.cities || []).map((c) => ({ id: c.id, name: nameOf(c) })) })))
    .catch(() => CFG.GOVERNORATES.map((g) => ({ ...g, cities: [] }))));

  // ---------- Safety ----------
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  function safeUrl(u, allowed = ["http:", "https:", "tel:", "mailto:"]) {
    try { const p = new URL(u, location.href); return allowed.includes(p.protocol) ? p.href : ""; } catch { return ""; }
  }

  // ---------- Formatting ----------
  const CUR = { USD: "$", EUR: "€", TRY: "₺" };
  function price(v, cur) {
    if (v === null || v === undefined) return esc(t("price_on_request"));
    const n = num.format(v);
    if (CUR[cur]) return `<bdi dir="ltr">${CUR[cur]}${n}</bdi>`;
    const label = cur === "SYP" ? (LANG === "ar" ? "ل.س" : "SYP") : esc(cur || "");
    return `<bdi>${n} ${label}</bdi>`;
  }
  const rtf = new Intl.RelativeTimeFormat(LANG, { numeric: "auto", style: "short" });
  function ago(iso) {
    if (!iso) return "";
    const diff = (new Date(iso) - Date.now()) / 1000;
    for (const [u, s] of [["year", 31536000], ["month", 2592000], ["week", 604800], ["day", 86400], ["hour", 3600], ["minute", 60]])
      if (Math.abs(diff) >= s) return rtf.format(Math.round(diff / s), u);
    return t("now");
  }
  function placeOf(ad) {
    const g = nameOf(ad.governorate), c = nameOf(ad.city);
    return esc(c && c !== g ? `${c}${LANG === "ar" ? "، " : ", "}${g}` : g);
  }
  const cover = (ad) => { const imgs = ad.images || []; return (imgs.find((i) => i.is_cover) || imgs[0])?.url || ""; };

  // ---------- Icons (outline, like the app) ----------
  const ICONS = {
    car: '<path d="M5 17h14M6.5 17v2M17.5 17v2M4 13l1.6-4.3A2 2 0 0 1 7.5 7.4h9a2 2 0 0 1 1.9 1.3L20 13M4 13h16v4H4z"/><circle cx="7.5" cy="15" r=".6"/><circle cx="16.5" cy="15" r=".6"/>',
    home: '<path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"/>',
    bike: '<circle cx="5.5" cy="16.5" r="3.5"/><circle cx="18.5" cy="16.5" r="3.5"/><path d="M5.5 16.5L9 9h5l4.5 7.5M12 9l2.5-3H17M9 9l3 7.5h6.5"/>',
    box: '<path d="M3 7.5L12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9"/>',
    pin: '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    star: '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
    chat: '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>',
    heart: '<path d="M12 20s-7.5-4.6-9.2-9.4C1.6 7 4 4 7.2 4c2 0 3.4 1.2 4.8 3 1.4-1.8 2.8-3 4.8-3C20 4 22.4 7 21.2 10.6 19.5 15.4 12 20 12 20z"/>',
    bell: '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0"/>',
    bolt: '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
    shield: '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
    sliders: '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
    phone: '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
    share: '<path d="M12 15V3M8 7l4-4 4 4"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/>',
    plus: '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
    plusBare: '<path d="M12 5v14M5 12h14"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/>',
    phoneDevice: '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    moon: '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
    chevL: '<path d="M15 6l-6 6 6 6"/>', chevR: '<path d="M9 6l6 6-6 6"/>', chevD: '<path d="M6 9l6 6 6-6"/>',
    arrowL: '<path d="M19 12H5M11 6l-6 6 6 6"/>'
  };
  const icon = (name, cls = "") => `<svg class="ico ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name] || ICONS.box}</svg>`;
  function categoryIconName(cat) {
    const s = ((cat.slug || "") + " " + (cat.name_en || "") + " " + (cat.name_ar || "")).toLowerCase();
    if (/car|سيار/.test(s)) return "car";
    if (/real|estate|house|home|property|عقار|بيت|شق/.test(s)) return "home";
    if (/motor|bike|دراج/.test(s)) return "bike";
    return "box";
  }
  function categoryVisual(cat) {
    const u = cat.icon_url && safeUrl(cat.icon_url, ["http:", "https:"]);
    return u ? `<img src="${esc(u)}" alt="">` : icon(categoryIconName(cat));
  }
  const logos = (cls = "logo") => `<img class="${cls} logo-light" src="assets/img/logo-256.png" alt="${esc(BRAND)}"><img class="${cls} logo-dark" src="assets/img/logo-dark-256.png" alt="${esc(BRAND)}">`;

  // ---------- Listing card ----------
  function adCard(ad, catNames) {
    const img = cover(ad);
    const catName = ad.category ? (catNames?.[ad.category.id] || nameOf(ad.category)) : "";
    const media = img
      ? `<img src="${esc(img)}" alt="${esc(ad.title)}" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false"><div class="noimg" hidden>${icon("image")}</div>`
      : `<div class="noimg">${icon("image")}</div>`;
    return `<article class="ad-card">
      <a href="ad.html?id=${encodeURIComponent(ad.id)}" title="${esc(catName)}">
        <div class="ad-media">${media}${ad.is_featured ? `<span class="badge-featured">${esc(t("featured_badge"))}</span>` : ""}</div>
        <div class="ad-body">
          <h3 class="ad-title">${esc(ad.title)}</h3>
          <div class="ad-price">${price(ad.price, ad.currency)}</div>
          <div class="ad-meta"><span>${icon("pin")}${placeOf(ad)}</span><span>${esc(ago(ad.published_at || ad.created_at))}</span></div>
        </div>
      </a>
      <button class="heart" data-app-only="app_save" aria-label="${esc(t("save"))}">${icon("heart")}</button>
    </article>`;
  }
  function miniCard(ad) {
    const img = cover(ad);
    return `<a class="mini-card" href="ad.html?id=${encodeURIComponent(ad.id)}" aria-label="${esc(ad.title)}">
      ${img ? `<img src="${esc(img)}" alt="" loading="lazy">` : `<div class="noimg">${icon("image")}</div>`}
      <span class="mini-price">${price(ad.price, ad.currency)}</span></a>`;
  }
  const skeletonCards = (n = 8) => Array.from({ length: n }, () => `<div class="ad-card skeleton"><div class="ad-media"></div><div class="ad-body"><div class="sk-line"></div><div class="sk-line short"></div></div></div>`).join("");

  // ---------- Layout ----------
  function storeButtons(extra = "") {
    const L = CFG.APP_LINKS;
    const btn = (href, top, bottom) => {
      const u = href && safeUrl(href, ["http:", "https:"]);
      return u
        ? `<a class="store-btn ${extra}" href="${esc(u)}" target="_blank" rel="noopener">${icon("phoneDevice")}<span><small>${esc(top)}</small>${bottom}</span></a>`
        : `<span class="store-btn soon ${extra}">${icon("phoneDevice")}<span><small>${esc(t("soon_on"))}</small>${bottom}</span></span>`;
    };
    return btn(L.android, t("get_on"), "Google Play") + btn(L.ios, t("get_on"), "App Store") +
      (L.apk && safeUrl(L.apk, ["http:", "https:"]) ? btn(L.apk, t("direct"), esc(t("apk"))) : "");
  }

  function renderHeader(active) {
    const el = document.getElementById("site-header");
    el.innerHTML = `<div class="container nav">
      <a class="brand" href="index.html">${logos()}<span class="brand-name">${esc(BRAND)}</span></a>
      <nav class="nav-links">
        <a href="index.html" class="${active === "home" ? "active" : ""}">${esc(t("nav_home"))}</a>
        <a href="ads.html" class="${active === "explore" ? "active" : ""}">${esc(t("nav_explore"))}</a>
        <a href="index.html#features">${esc(t("nav_features"))}</a>
      </nav>
      <div class="nav-tools">
        <button class="icon-btn lang-btn" id="lang-btn" title="${esc(t("lang_switch"))}" aria-label="${esc(t("lang_switch"))}">${esc(t("lang_short"))}</button>
        <button class="icon-btn" id="theme-btn" title="${esc(t("theme_toggle"))}" aria-label="${esc(t("theme_toggle"))}"><span class="t-moon">${icon("moon")}</span><span class="t-sun">${icon("sun")}</span></button>
        <a class="btn btn-primary btn-sm" href="index.html#download">${icon("phoneDevice")} ${esc(t("get_app"))}</a>
      </div>
    </div>`;
    el.querySelector("#lang-btn").onclick = () => setLang(LANG === "ar" ? "en" : "ar");
    el.querySelector("#theme-btn").onclick = toggleTheme;

    // bottom navigation on phones — same 5 tabs as the app
    const bn = document.createElement("nav");
    bn.className = "bottom-nav";
    bn.innerHTML = `
      <a href="index.html" class="${active === "home" ? "on" : ""}">${icon("home")}<span>${esc(t("nav_home"))}</span></a>
      <a href="ads.html" class="${active === "explore" ? "on" : ""}">${icon("search")}<span>${esc(t("nav_explore"))}</span></a>
      <button class="sell" data-app-only="app_sell"><span class="fab">${icon("plusBare")}</span><span>${esc(t("nav_sell"))}</span></button>
      <button data-app-only="app_messages">${icon("chat")}<span>${esc(t("nav_messages"))}</span></button>
      <button data-app-only="app_profile">${icon("user")}<span>${esc(t("nav_profile"))}</span></button>`;
    document.body.appendChild(bn);
  }

  function renderFooter() {
    const el = document.getElementById("site-footer");
    const C = CFG.CONTACT;
    const line = (v, label, href) => v && href ? `<li><a href="${esc(href)}" target="_blank" rel="noopener">${label}</a></li>` : "";
    const contacts = [
      line(C.phone, `<bdi dir="ltr">${esc(C.phone)}</bdi>`, "tel:" + C.phone),
      line(C.whatsapp, esc(t("whatsapp")), "https://wa.me/" + encodeURIComponent(C.whatsapp)),
      line(C.email, esc(C.email), "mailto:" + C.email),
      line(C.facebook, esc(t("facebook")), safeUrl(C.facebook)),
      line(C.instagram, esc(t("instagram")), safeUrl(C.instagram))
    ].join("");
    el.innerHTML = `<div class="container footer-grid">
      <div><a class="brand" href="index.html"><img class="logo" src="assets/img/logo-dark-256.png" alt=""><span class="brand-name">${esc(BRAND)}</span></a>
        <p>${esc(t("tagline"))}</p></div>
      <div><h4>${esc(t("footer_links"))}</h4><ul>
        <li><a href="index.html">${esc(t("nav_home"))}</a></li>
        <li><a href="ads.html">${esc(t("nav_explore"))}</a></li>
        <li><a href="index.html#features">${esc(t("nav_features"))}</a></li>
        <li><a href="index.html#download">${esc(t("get_app"))}</a></li>
      </ul></div>
      ${contacts ? `<div><h4>${esc(t("footer_contact"))}</h4><ul>${contacts}</ul></div>` : ""}
      <div><h4>${esc(t("footer_app"))}</h4><div class="store-col">${storeButtons("dark")}</div></div>
    </div>
    <div class="container footer-bottom">© ${new Date().getFullYear()} <span class="latin">${esc(BRAND)}</span> — ${esc(t("rights"))}</div>`;
  }

  const errorBox = () => `<div class="state-box">${icon("bolt")}<p>${esc(t("error"))}</p><button class="btn btn-outline" onclick="location.reload()">${esc(t("retry"))}</button></div>`;
  const emptyBox = (msg, sub = "") => `<div class="state-box">${icon("search")}<p>${esc(msg)}</p>${sub ? `<small>${esc(sub)}</small>` : ""}</div>`;

  function appPrompt(titleKey) {
    let m = document.getElementById("app-modal");
    if (!m) {
      m = document.createElement("div");
      m.id = "app-modal"; m.className = "modal";
      m.innerHTML = `<div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="app-modal-title">
        <button class="modal-x" aria-label="${esc(t("close"))}">×</button>
        ${logos()}
        <h3 id="app-modal-title"></h3>
        <p class="muted">${esc(t("app_only_text"))}</p>
        <div class="store-row">${storeButtons()}</div></div>`;
      document.body.appendChild(m);
      m.addEventListener("click", (e) => { if (e.target === m || e.target.classList.contains("modal-x")) m.classList.remove("open"); });
      document.addEventListener("keydown", (e) => { if (e.key === "Escape") m.classList.remove("open"); });
    }
    m.querySelector("#app-modal-title").textContent = t(titleKey);
    m.classList.add("open");
  }
  document.addEventListener("click", (e) => {
    const el = e.target.closest("[data-app-only]");
    if (el) { e.preventDefault(); appPrompt(el.getAttribute("data-app-only")); }
  });

  function stars(avg) {
    const v = Math.round((Number(avg) || 0) * 2) / 2;
    let s = "";
    for (let i = 1; i <= 5; i++) s += `<span class="star ${v >= i ? "on" : v >= i - 0.5 ? "half" : ""}">${icon("star")}</span>`;
    return `<span class="stars" aria-label="${v} / 5">${s}</span>`;
  }
  function toast(msg) {
    const el = document.createElement("div");
    el.className = "toast"; el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  }
  function setMeta(title, desc) {
    document.title = title;
    let m = document.querySelector('meta[name="description"]');
    if (!m) { m = document.createElement("meta"); m.name = "description"; document.head.appendChild(m); }
    m.content = desc;
  }

  window.Simsar = { CFG, BRAND, LANG, t, tr, nameOf, labelOf, applyI18n, api, categories, governorates, esc, safeUrl, price, ago, placeOf, cover, icon, categoryVisual, logos, adCard, miniCard, skeletonCards, storeButtons, renderHeader, renderFooter, errorBox, emptyBox, appPrompt, stars, toast, setMeta, num };
})();
