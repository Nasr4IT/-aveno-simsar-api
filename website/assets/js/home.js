(function () {
  const S = window.Simsar, C = S.CFG;
  S.renderHeader("home");
  S.renderFooter();

  document.getElementById("hero-tagline").textContent = C.SITE_TAGLINE;
  document.getElementById("hero-search-btn").innerHTML = S.icon("search") + " بحث";
  document.getElementById("arrow1").innerHTML = S.icon("chevL");
  document.getElementById("arrow2").innerHTML = S.icon("chevL");
  document.getElementById("download-buttons").innerHTML = S.storeButtons();

  const gov = document.getElementById("hero-gov");
  C.GOVERNORATES.forEach((g) => gov.add(new Option(g.name, g.id)));

  // Drop empty params so the browse URL stays clean
  document.getElementById("hero-search").addEventListener("submit", (e) => {
    e.preventDefault();
    const p = new URLSearchParams();
    new FormData(e.target).forEach((v, k) => { if (v) p.set(k, v); });
    location.href = "ads.html" + (p.toString() ? "?" + p : "");
  });

  // ---- Features of the app ----
  const features = [
    ["search", "بحث ذكي وفلاتر متقدمة", "ابحث بالاسم حتى لو أخطأت في الكتابة، وفلتر حسب الفئة والمحافظة والسعر والمواصفات."],
    ["pin", "الأقرب إليك", "رتّب الإعلانات حسب المسافة لتجد ما تريده في حيّك."],
    ["chat", "محادثة مباشرة", "راسل البائع مباشرة داخل التطبيق واتفق على كل التفاصيل."],
    ["heart", "المفضلة", "احفظ الإعلانات التي أعجبتك وارجع إليها في أي وقت."],
    ["star", "تقييم البائعين", "اطّلع على تقييمات البائع قبل التعامل معه وقيّم تجربتك."],
    ["bell", "إشعارات فورية", "تنبيه عند وصول رسالة جديدة أو الموافقة على إعلانك."],
    ["bolt", "إعلانات مميزة", "اجعل إعلانك في المقدمة لمدة 15 أو 30 أو 60 يوماً."],
    ["shield", "مراجعة كل إعلان", "يراجع فريقنا كل إعلان قبل نشره، مع إمكانية الإبلاغ عن أي مخالفة."],
    ["plus", "نشر مجاني وسريع", "أضف صورك ومواصفات منتجك وانشر إعلانك خلال دقائق."]
  ];
  document.getElementById("features-grid").innerHTML = features
    .map(([i, t, d]) => `<div class="feature">${S.icon(i)}<h3>${t}</h3><p>${d}</p></div>`).join("");

  // ---- Categories ----
  const catsEl = document.getElementById("cats");
  catsEl.innerHTML = '<div class="cat skeleton" style="height:120px"></div>'.repeat(3);
  S.api("/categories").then(({ data }) => {
    const heroCat = document.getElementById("hero-cat");
    data.forEach((c) => heroCat.add(new Option(c.name_ar, c.id)));
    catsEl.innerHTML = data.map((c) => `<a class="cat" href="ads.html?category_id=${c.id}">${S.categoryVisual(c)}<span>${S.esc(c.name_ar)}</span></a>`).join("")
      + `<a class="cat" href="ads.html">${S.icon("search")}<span>كل الإعلانات</span></a>`;
  }).catch(() => { catsEl.innerHTML = ""; });

  // ---- Banners ----
  S.api("/banners").then(({ data }) => {
    if (!data || !data.length) return;
    const el = document.getElementById("banners");
    el.innerHTML = data.map((b, i) => {
      const link = b.link_url ? S.safeUrl(b.link_url) : "";
      const img = `<img src="${S.esc(b.image_url)}" alt="${S.esc(b.title || "")}">`;
      return `<div class="banner-slide ${i === 0 ? "on" : ""}">${link ? `<a href="${S.esc(link)}" target="_blank" rel="noopener">${img}</a>` : img}</div>`;
    }).join("") + `<span class="sponsored">إعلان ممول</span>` +
      (data.length > 1 ? `<div class="banner-dots">${data.map((_, i) => `<button aria-label="${i + 1}" class="${i === 0 ? "on" : ""}"></button>`).join("")}</div>` : "");
    document.getElementById("banners-section").hidden = false;
    if (data.length > 1) {
      let cur = 0;
      const slides = el.querySelectorAll(".banner-slide"), dots = el.querySelectorAll(".banner-dots button");
      const go = (n) => { slides[cur].classList.remove("on"); dots[cur].classList.remove("on"); cur = (n + slides.length) % slides.length; slides[cur].classList.add("on"); dots[cur].classList.add("on"); };
      dots.forEach((d, i) => d.addEventListener("click", () => go(i)));
      setInterval(() => go(cur + 1), 5000);
    }
  }).catch(() => {});

  // ---- Ads: featured come first in the default order ----
  const latest = document.getElementById("latest");
  latest.innerHTML = S.skeletonCards(8);
  S.api("/ads").then((res) => {
    const ads = res.data || [];
    document.getElementById("stat-ads").textContent = new Intl.NumberFormat("en-US").format(res.meta?.total ?? ads.length);
    const featured = ads.filter((a) => a.is_featured);
    if (featured.length) {
      document.getElementById("featured").innerHTML = featured.slice(0, 4).map(S.adCard).join("");
      document.getElementById("featured-section").hidden = false;
    }
    const rest = [...ads].sort((a, b) => new Date(b.published_at || b.created_at) - new Date(a.published_at || a.created_at));
    latest.innerHTML = rest.length ? rest.slice(0, 8).map(S.adCard).join("") : S.emptyBox("لا توجد إعلانات بعد", "كن أول من ينشر إعلاناً عبر التطبيق!");
  }).catch(() => { latest.innerHTML = S.errorBox(); });
})();
