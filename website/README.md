# Simsar Website — موقع Simsar

موقع عرض (تصفح فقط) لإعلانات Simsar، يقرأ البيانات من نفس الـ API الذي يستخدمه تطبيق Flutter **دون أي تعديل على الباك إند**.
A browse-only website for Simsar. It reads from the same Laravel API the Flutter app uses and changes nothing in the backend.

## Design
- Same look as the Simsar app: **light and dark mode** (follows the device setting; the sun/moon button switches and remembers it).
- **Arabic / English** (AR/EN button, remembered). The name **Simsar** is always shown in English.
- Brand colors are CSS variables at the top of `assets/css/style.css` (Deep Teal `#0C373D`, Turquoise `#27F5F2`, Mist `#E8F2F2`, Ink `#102326`). Fonts are self-hosted in `assets/fonts/` (no Google Fonts): Manrope for Latin, Alexandria for Arabic (both free, SIL OFL). To use **Montserrat Arabic** instead, put your licensed `.woff2` files in `assets/fonts/` (file names in `assets/css/montserrat-arabic.css`) and set `USE_MONTSERRAT_ARABIC: true` in `config.js`.
- Logo: `assets/img/logo-256.png` (light) and `logo-dark-256.png` (dark, navy/white swapped so it stays visible). To change the logo, replace these two files, `favicon.png` and `apple-touch-icon.png`.
- English names: the server stores categories, specs and places in Arabic only (the seeders leave `name_en` / `label_en` empty). `assets/js/i18n.js` has an English fallback list (`SIMSAR_EN_FALLBACK`). English names entered in the admin panel take priority.

## الصفحات / Pages
| Page | File | Uses |
|---|---|---|
| الرئيسية — بحث، فئات، بانرات ممولة، إعلانات مميزة وأحدث الإعلانات، مميزات التطبيق، تحميل التطبيق | `index.html` | `GET /categories`, `/banners`, `/ads` |
| تصفح وبحث مع فلاتر (فئة، محافظة، مدينة، سعر، مواصفات الفئة) وترتيب (الأحدث، السعر، الأكثر مشاهدة، الأقرب) | `ads.html` | `GET /ads`, `/categories/{id}`, `/governorates` |
| تفاصيل الإعلان — صور، مواصفات، وصف، البائع وتقييماته، إعلانات مشابهة | `ad.html?id=…` | `GET /ads/{id}`, `/ads/{id}/similar`, `/users/{id}/ratings` |

Actions that need an account (messaging, favorites, reporting, posting) open a "download the app" popup.
Only `GET` requests are made. No login, and nothing is written to the database.

## قبل الرفع / Before uploading
Edit **`assets/js/config.js`** only:
- `API_BASE_URL` — the live API address ending in `/api`, e.g. `https://api.example.com/api`
- `APP_LINKS` — Google Play / App Store links (they show "قريباً" until filled)
- `CONTACT` — phone, WhatsApp, email, social links for the footer
- `DEFAULT_LANG` — the language first-time visitors see (`ar` or `en`)
- `GOVERNORATES` — fallback only. The site loads governorates and cities from `GET /governorates` (added in PR #1) and uses this list only if that endpoint isn't deployed yet

Colors are at the top of `assets/css/style.css` (`--primary`, `--accent`).

## الرفع / Deploy
Static files only (no PHP, no build step). Upload the **contents** of this `website/` folder with FileZilla to any web root, e.g. `/var/www/simsar-web`, and point a domain or subdomain at it (Nginx `root /var/www/simsar-web; index index.html;`).

The API already allows cross-origin requests (`config/cors.php`, `CORS_ALLOWED_ORIGINS=*` by default). If you restrict `CORS_ALLOWED_ORIGINS` on the server later, add the website's domain to it.

## تجربة محلية / Local test
```bash
php artisan serve            # API on :8000
cd website && python3 -m http.server 8080   # open http://localhost:8080
```
