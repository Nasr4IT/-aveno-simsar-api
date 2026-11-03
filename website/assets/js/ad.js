(function () {
  const S = window.Simsar, C = S.CFG, I = S.icon, esc = S.esc;
  S.renderHeader("ads");
  S.renderFooter();

  const root = document.getElementById("ad-root");
  const id = new URLSearchParams(location.search).get("id");
  if (!id) { root.innerHTML = S.emptyBox("لم يتم تحديد إعلان"); return; }

  S.api("/ads/" + encodeURIComponent(id)).then(({ data: ad }) => {
    render(ad);
    loadSimilar(ad.id);
    if (ad.user?.id) loadRatings(ad.user.id);
  }).catch((e) => {
    root.innerHTML = e.status === 404
      ? `<div style="padding:40px 0">${S.emptyBox("هذا الإعلان غير متوفر", "ربما تم بيعه أو حذفه أو أنه قيد المراجعة.")}<p style="text-align:center"><a class="btn btn-primary" href="ads.html">تصفح إعلانات أخرى</a></p></div>`
      : `<div style="padding:40px 0">${S.errorBox()}</div>`;
  });

  function render(ad) {
    document.title = `${ad.title} — ${C.SITE_NAME}`;
    const meta = document.createElement("meta");
    meta.name = "description"; meta.content = (ad.description || ad.title).slice(0, 160);
    document.head.appendChild(meta);

    const images = [...(ad.images || [])].sort((a, b) => (b.is_cover ? 1 : 0) - (a.is_cover ? 1 : 0));
    const loc = S.location(ad);
    const u = ad.user || {};
    const initial = esc((u.name || "؟").trim().charAt(0));
    const avatarUrl = u.avatar_url && S.safeUrl(u.avatar_url, ["http:", "https:"]);
    const since = u.created_at ? new Date(u.created_at).toLocaleDateString("ar", { year: "numeric", month: "long" }) : "";
    const isSold = ad.status === "sold";
    const phone = C.SHOW_SELLER_PHONE && u.phone ? String(u.phone).replace(/[^\d+]/g, "") : "";
    const waNumber = phone ? phone.replace(/^\+/, "").replace(/^0/, "963") : "";
    const catLink = ad.category ? `<a href="ads.html?category_id=${encodeURIComponent(ad.category.id)}">${esc(ad.category.name_ar)}</a> <span>/</span>` : "";

    root.innerHTML = `
      <nav class="crumbs"><a href="index.html">الرئيسية</a><span>/</span><a href="ads.html">الإعلانات</a><span>/</span>${catLink}<span>${esc(ad.title)}</span></nav>
      <div class="detail">
        <div>
          <div class="gallery-main" id="gal">
            ${images.length ? `<img id="gal-img" src="${esc(images[0].url)}" alt="${esc(ad.title)}">` : `<div class="noimg">${I("image")}</div>`}
            ${images.length > 1 ? `<button class="gal-nav gal-prev" aria-label="السابق">${I("chevR")}</button><button class="gal-nav gal-next" aria-label="التالي">${I("chevL")}</button><span class="gal-count" id="gal-count">1 / ${images.length}</span>` : ""}
            ${ad.is_featured ? `<span class="badge-featured">${I("bolt")} مميز</span>` : ""}
          </div>
          ${images.length > 1 ? `<div class="thumbs" id="thumbs">${images.map((im, i) => `<button class="${i === 0 ? "on" : ""}" data-i="${i}" aria-label="صورة ${i + 1}"><img src="${esc(im.url)}" alt="" loading="lazy"></button>`).join("")}</div>` : ""}

          <div class="panel" style="margin-top:20px">
            <h1 class="detail-title">${esc(ad.title)}</h1>
            <div class="detail-price">${S.price(ad.price, ad.currency)} ${isSold ? `<span class="chip" style="background:#fee2e2;color:#b91c1c;vertical-align:middle">تم البيع</span>` : ""}</div>
            <div class="detail-meta">
              ${loc ? `<span>${I("pin")}${loc}</span>` : ""}
              <span>${I("clock")}نُشر ${esc(S.ago(ad.published_at || ad.created_at))}</span>
              <span>${I("eye")}${new Intl.NumberFormat("en-US").format(ad.views_count || 0)} مشاهدة</span>
              <span>رقم الإعلان: ${esc(ad.id)}</span>
            </div>
          </div>

          ${(ad.attributes || []).length ? `<div class="panel"><h2>المواصفات</h2><div class="specs">
            ${ad.attributes.map((a) => `<div class="spec"><small>${esc(a.label_ar)}</small><b>${esc(fmtAttr(a.value))}</b></div>`).join("")}
          </div></div>` : ""}

          ${ad.description ? `<div class="panel"><h2>الوصف</h2><p class="desc">${esc(ad.description)}</p></div>` : ""}

          <div class="panel" id="reviews-panel" hidden><h2>تقييمات البائع</h2><div id="reviews"></div></div>
        </div>

        <aside class="sticky-side">
          <div class="panel">
            <div class="seller">
              <div class="avatar">${avatarUrl ? `<img src="${esc(avatarUrl)}" alt="">` : initial}</div>
              <div>
                <h3>${esc(u.name || "البائع")}</h3>
                <div>${S.stars(u.rating_average)} <small class="muted">(${esc(u.rating_count || 0)} تقييم)</small></div>
                ${since ? `<small class="muted">عضو منذ ${esc(since)}</small>` : ""}
              </div>
            </div>
            <div class="actions">
              <button class="btn btn-primary btn-block" data-app-only="راسل البائع عبر التطبيق">${I("chat")} مراسلة البائع</button>
              ${phone ? `<button class="btn btn-outline btn-block" id="show-phone">${I("phone")} إظهار رقم الهاتف</button>` : ""}
              <div class="row">
                <button class="btn btn-outline" data-app-only="احفظ الإعلان في المفضلة عبر التطبيق">${I("heart")} حفظ</button>
                <button class="btn btn-outline" id="share">${I("share")} مشاركة</button>
              </div>
              <button class="btn btn-ghost btn-sm" data-app-only="الإبلاغ عن الإعلانات متاح عبر التطبيق">الإبلاغ عن هذا الإعلان</button>
            </div>
          </div>
          <div class="tip"><b>نصائح للأمان:</b> عاين المنتج بنفسك قبل الدفع، التقِ بالبائع في مكان عام، ولا ترسل أي مبلغ مقدماً.</div>
          <div class="panel" style="margin-top:20px;text-align:center">
            <h2>تجربة أفضل على التطبيق</h2>
            <p class="muted" style="margin-top:0">محادثة، مفضلة، إشعارات ونشر إعلاناتك مجاناً.</p>
            <div class="store-row" style="justify-content:center">${S.storeButtons()}</div>
          </div>
        </aside>
      </div>`;

    // Gallery
    if (images.length > 1) {
      let cur = 0;
      const img = document.getElementById("gal-img"), count = document.getElementById("gal-count");
      const thumbs = [...document.querySelectorAll("#thumbs button")];
      const show = (n) => {
        cur = (n + images.length) % images.length;
        img.src = images[cur].url;
        count.textContent = `${cur + 1} / ${images.length}`;
        thumbs.forEach((t, i) => t.classList.toggle("on", i === cur));
      };
      root.querySelector(".gal-prev").onclick = () => show(cur - 1);
      root.querySelector(".gal-next").onclick = () => show(cur + 1);
      thumbs.forEach((t) => t.onclick = () => show(+t.dataset.i));
      let x0 = null;
      const gal = document.getElementById("gal");
      gal.addEventListener("touchstart", (e) => { x0 = e.touches[0].clientX; }, { passive: true });
      gal.addEventListener("touchend", (e) => {
        if (x0 === null) return;
        const dx = e.changedTouches[0].clientX - x0;
        if (Math.abs(dx) > 40) show(cur + (dx > 0 ? 1 : -1)); // RTL: swipe right = next
        x0 = null;
      });
    }

    // Phone reveal
    const sp = document.getElementById("show-phone");
    if (sp) sp.onclick = () => {
      sp.outerHTML = `<div class="row"><a class="btn btn-outline" href="tel:${esc(phone)}" dir="ltr">${I("phone")} ${esc(u.phone)}</a>
        <a class="btn btn-outline" href="https://wa.me/${esc(waNumber)}?text=${encodeURIComponent("مرحباً، بخصوص إعلانك على " + C.SITE_NAME + ": " + ad.title + " " + location.href)}" target="_blank" rel="noopener">${I("chat")} واتساب</a></div>`;
    };

    // Share
    document.getElementById("share").onclick = async () => {
      const data = { title: ad.title, text: ad.title, url: location.href };
      if (navigator.share) { try { await navigator.share(data); } catch {} return; }
      try { await navigator.clipboard.writeText(location.href); toast("تم نسخ رابط الإعلان"); } catch { prompt("انسخ الرابط:", location.href); }
    };
  }

  function fmtAttr(v) {
    if (v === "1" || v === true) return "نعم";
    if (v === "0" || v === false) return "لا";
    if (Array.isArray(v)) return v.join("، ");
    try { const p = JSON.parse(v); if (Array.isArray(p)) return p.join("، "); } catch {}
    return v ?? "—";
  }

  function toast(msg) {
    const t = document.createElement("div");
    t.className = "toast"; t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
  }

  function loadSimilar(adId) {
    S.api(`/ads/${encodeURIComponent(adId)}/similar`).then(({ data }) => {
      if (!data || !data.length) return;
      document.getElementById("similar").innerHTML = data.slice(0, 8).map(S.adCard).join("");
      document.getElementById("similar-section").hidden = false;
    }).catch(() => {});
  }

  function loadRatings(userId) {
    S.api(`/users/${encodeURIComponent(userId)}/ratings`).then(({ data }) => {
      if (!data || !data.length) return;
      document.getElementById("reviews").innerHTML = data.slice(0, 5).map((r) => `<div class="review">
        <div class="review-head"><span>${esc(r.rater?.name || "مستخدم")}</span>${S.stars(r.score)}</div>
        ${r.comment ? `<p>${esc(r.comment)}</p>` : ""}
        <small class="muted">${esc(S.ago(r.created_at))}</small>
      </div>`).join("");
      document.getElementById("reviews-panel").hidden = false;
    }).catch(() => {});
  }
})();
