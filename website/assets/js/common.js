/* Shared helpers: API calls, formatting, header/footer, ad cards. */
(function () {
  const CFG = window.SIMSAR_CONFIG;
  const base = CFG.API_BASE_URL.replace(/\/+$/, "");

  // ---------- API ----------
  async function api(path, params) {
    const url = new URL(base + path);
    if (params) {
      Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== "") url.searchParams.set(k, v);
      });
    }
    const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
    if (!res.ok) {
      const err = new Error("HTTP " + res.status);
      err.status = res.status;
      throw err;
    }
    return res.json();
  }

  // ---------- Safety ----------
  function esc(s) {
    return String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  }
  function safeUrl(u, allowed = ["http:", "https:", "tel:", "mailto:"]) {
    try {
      const p = new URL(u, location.href);
      return allowed.includes(p.protocol) ? p.href : "";
    } catch { return ""; }
  }

  // ---------- Formatting ----------
  const CURRENCY = { USD: "$", SYP: "ل.س", EUR: "€", TRY: "₺" };
  function price(v, cur) {
    if (v === null || v === undefined) return "السعر عند التواصل";
    const n = new Intl.NumberFormat("en-US", { maximumFractionDigits: 2 }).format(v);
    const c = CURRENCY[cur] || cur || "";
    return c === "$" ? `${n} $` : `${n} ${esc(c)}`;
  }
  const rtf = new Intl.RelativeTimeFormat("ar", { numeric: "auto" });
  function ago(iso) {
    if (!iso) return "";
    const diff = (new Date(iso) - Date.now()) / 1000;
    const units = [["year", 31536000], ["month", 2592000], ["week", 604800], ["day", 86400], ["hour", 3600], ["minute", 60]];
    for (const [u, s] of units) if (Math.abs(diff) >= s) return rtf.format(Math.round(diff / s), u);
    return "الآن";
  }
  function location_(ad) {
    return [ad.governorate?.name_ar, ad.city?.name_ar && ad.city.name_ar !== ad.governorate?.name_ar ? ad.city.name_ar : null]
      .filter(Boolean).map(esc).join("، ");
  }
  function cover(ad) {
    const imgs = ad.images || [];
    return (imgs.find((i) => i.is_cover) || imgs[0])?.url || "";
  }

  // ---------- Icons ----------
  const ICONS = {
    car: '<path d="M5 17h14M6.5 17v2M17.5 17v2M4 13l1.6-4.3A2 2 0 0 1 7.5 7.4h9a2 2 0 0 1 1.9 1.3L20 13M4 13h16v4H4z"/><circle cx="7.5" cy="15" r=".6"/><circle cx="16.5" cy="15" r=".6"/>',
    home: '<path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"/>',
    bike: '<circle cx="5.5" cy="16.5" r="3.5"/><circle cx="18.5" cy="16.5" r="3.5"/><path d="M5.5 16.5L9 9h5l4.5 7.5M12 9l2.5-3H17M9 9l3 7.5h6.5"/>',
    box: '<path d="M3 7.5L12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9"/>',
    pin: '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    star: '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
    chat: '<path d="M4 5h16v11H8l-4 4z"/>',
    heart: '<path d="M12 20s-7.5-4.6-9.2-9.4C1.6 7 4 4 7.2 4c2 0 3.4 1.2 4.8 3 1.4-1.8 2.8-3 4.8-3C20 4 22.4 7 21.2 10.6 19.5 15.4 12 20 12 20z"/>',
    bell: '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0"/>',
    bolt: '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
    shield: '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
    filter: '<path d="M3 5h18l-7 8v6l-4-2v-4z"/>',
    phone: '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
    share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/>',
    phoneDevice: '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
    chevL: '<path d="M15 6l-6 6 6 6"/>',
    chevR: '<path d="M9 6l6 6-6 6"/>'
  };
  function icon(name, cls = "") {
    return `<svg class="ico ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name] || ICONS.box}</svg>`;
  }
  function categoryIcon(cat) {
    const s = ((cat.slug || "") + " " + (cat.name_en || "") + " " + (cat.name_ar || "")).toLowerCase();
    if (/car|سيار/.test(s)) return "car";
    if (/real|estate|house|property|عقار|بيت|شق/.test(s)) return "home";
    if (/motor|bike|دراج/.test(s)) return "bike";
    return "box";
  }
  function categoryVisual(cat, cls = "") {
    const u = cat.icon_url && safeUrl(cat.icon_url, ["http:", "https:"]);
    return u ? `<img class="cat-img ${cls}" src="${esc(u)}" alt="">` : icon(categoryIcon(cat), cls);
  }

  // ---------- Ad card ----------
  function adCard(ad) {
    const img = cover(ad);
    const media = img
      ? `<img src="${esc(img)}" alt="${esc(ad.title)}" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false"><div class="noimg" hidden>${icon("image")}</div>`
      : `<div class="noimg">${icon("image")}</div>`;
    const loc = location_(ad);
    return `<a class="ad-card" href="ad.html?id=${encodeURIComponent(ad.id)}">
      <div class="ad-media">${media}
        ${ad.is_featured ? `<span class="badge-featured">${icon("bolt")} مميز</span>` : ""}
        ${ad.category ? `<span class="badge-cat">${esc(ad.category.name_ar)}</span>` : ""}
      </div>
      <div class="ad-body">
        <h3 class="ad-title">${esc(ad.title)}</h3>
        <div class="ad-price">${price(ad.price, ad.currency)}</div>
        <div class="ad-meta">
          ${loc ? `<span>${icon("pin")}${loc}</span>` : ""}
          <span>${icon("clock")}${esc(ago(ad.published_at || ad.created_at))}</span>
        </div>
      </div>
    </a>`;
  }
  function skeletonCards(n = 8) {
    return Array.from({ length: n }, () => `<div class="ad-card skeleton"><div class="ad-media"></div><div class="ad-body"><div class="sk-line"></div><div class="sk-line short"></div></div></div>`).join("");
  }

  // ---------- Layout ----------
  function storeButtons(extraCls = "") {
    const L = CFG.APP_LINKS;
    const btn = (href, top, bottom) => {
      const u = href && safeUrl(href, ["http:", "https:"]);
      return u
        ? `<a class="store-btn ${extraCls}" href="${esc(u)}" target="_blank" rel="noopener">${icon("phoneDevice")}<span><small>${top}</small>${bottom}</span></a>`
        : `<span class="store-btn soon ${extraCls}">${icon("phoneDevice")}<span><small>قريباً على</small>${bottom}</span></span>`;
    };
    return btn(L.android, "حمّله من", "Google Play") + btn(L.ios, "حمّله من", "App Store") +
      (L.apk && safeUrl(L.apk, ["http:", "https:"]) ? btn(L.apk, "تحميل مباشر", "ملف APK") : "");
  }

  function renderHeader(active) {
    const el = document.getElementById("site-header");
    if (!el) return;
    el.innerHTML = `<div class="container nav">
      <a class="brand" href="index.html"><span class="brand-mark">س</span><span>${esc(CFG.SITE_NAME)}</span></a>
      <nav class="nav-links">
        <a href="index.html" class="${active === "home" ? "active" : ""}">الرئيسية</a>
        <a href="ads.html" class="${active === "ads" ? "active" : ""}">تصفح الإعلانات</a>
        <a href="index.html#features">المميزات</a>
      </nav>
      <a class="btn btn-primary btn-sm" href="index.html#download">${icon("phoneDevice")} حمّل التطبيق</a>
    </div>`;
  }

  function renderFooter() {
    const el = document.getElementById("site-footer");
    if (!el) return;
    const C = CFG.CONTACT;
    const line = (v, label, href) => v ? `<li><a href="${esc(href)}" target="_blank" rel="noopener">${label}</a></li>` : "";
    const contacts = [
      line(C.phone, esc(C.phone), "tel:" + C.phone),
      line(C.whatsapp, "واتساب", "https://wa.me/" + encodeURIComponent(C.whatsapp)),
      line(C.email, esc(C.email), "mailto:" + C.email),
      line(C.facebook, "فيسبوك", safeUrl(C.facebook)),
      line(C.instagram, "إنستغرام", safeUrl(C.instagram))
    ].join("");
    el.innerHTML = `<div class="container footer-grid">
      <div>
        <a class="brand" href="index.html"><span class="brand-mark">س</span><span>${esc(CFG.SITE_NAME)}</span></a>
        <p class="muted">${esc(CFG.SITE_TAGLINE)}</p>
      </div>
      <div><h4>روابط</h4><ul>
        <li><a href="index.html">الرئيسية</a></li>
        <li><a href="ads.html">تصفح الإعلانات</a></li>
        <li><a href="index.html#features">مميزات التطبيق</a></li>
        <li><a href="index.html#download">تحميل التطبيق</a></li>
      </ul></div>
      ${contacts ? `<div><h4>تواصل معنا</h4><ul>${contacts}</ul></div>` : ""}
      <div><h4>التطبيق</h4><div class="store-col">${storeButtons("dark")}</div></div>
    </div>
    <div class="container footer-bottom">© ${new Date().getFullYear()} ${esc(CFG.SITE_NAME)} — جميع الحقوق محفوظة</div>`;
  }

  function errorBox(msg = "تعذّر تحميل البيانات. تأكد من اتصالك بالإنترنت ثم أعد المحاولة.") {
    return `<div class="state-box">${icon("bolt")}<p>${esc(msg)}</p><button class="btn btn-outline" onclick="location.reload()">إعادة المحاولة</button></div>`;
  }
  function emptyBox(msg, sub = "") {
    return `<div class="state-box">${icon("search")}<p>${esc(msg)}</p>${sub ? `<small class="muted">${esc(sub)}</small>` : ""}</div>`;
  }

  // App-only action prompt (chat, favorites, posting...)
  function appPrompt(title) {
    let m = document.getElementById("app-modal");
    if (!m) {
      m = document.createElement("div");
      m.id = "app-modal";
      m.className = "modal";
      m.innerHTML = `<div class="modal-card" role="dialog" aria-modal="true">
        <button class="modal-x" aria-label="إغلاق">×</button>
        <div class="modal-ico">${icon("phoneDevice")}</div>
        <h3 id="app-modal-title"></h3>
        <p class="muted">هذه الميزة متاحة في تطبيق ${esc(CFG.SITE_NAME)}. حمّل التطبيق مجاناً للمراسلة، حفظ الإعلانات في المفضلة، نشر إعلاناتك وتقييم البائعين.</p>
        <div class="store-row">${storeButtons()}</div>
      </div>`;
      document.body.appendChild(m);
      m.addEventListener("click", (e) => { if (e.target === m || e.target.classList.contains("modal-x")) m.classList.remove("open"); });
      document.addEventListener("keydown", (e) => { if (e.key === "Escape") m.classList.remove("open"); });
    }
    m.querySelector("#app-modal-title").textContent = title;
    m.classList.add("open");
  }

  function stars(avg) {
    const v = Math.round((Number(avg) || 0) * 2) / 2;
    let s = "";
    for (let i = 1; i <= 5; i++) s += `<span class="star ${v >= i ? "on" : v >= i - 0.5 ? "half" : ""}">${icon("star")}</span>`;
    return `<span class="stars" aria-label="${v} من 5">${s}</span>`;
  }

  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-app-only]");
    if (t) { e.preventDefault(); appPrompt(t.getAttribute("data-app-only")); }
  });

  window.Simsar = { CFG, api, esc, safeUrl, price, ago, location: location_, cover, icon, categoryVisual, adCard, skeletonCards, storeButtons, renderHeader, renderFooter, errorBox, emptyBox, appPrompt, stars };
})();
