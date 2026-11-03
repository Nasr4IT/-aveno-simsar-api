# موقع سمسار — Simsar Website

موقع عرض (تصفح فقط) لإعلانات سمسار، يقرأ البيانات من نفس الـ API الذي يستخدمه تطبيق Flutter **دون أي تعديل على الباك إند**.
A browse-only website for Simsar. It reads from the same Laravel API the Flutter app uses and changes nothing in the backend.

## الصفحات / Pages
| Page | File | Uses |
|---|---|---|
| الرئيسية — بحث، فئات، بانرات ممولة، إعلانات مميزة وأحدث الإعلانات، مميزات التطبيق، تحميل التطبيق | `index.html` | `GET /categories`, `/banners`, `/ads` |
| تصفح وبحث مع فلاتر (فئة، محافظة، سعر، مواصفات الفئة) وترتيب (الأحدث، السعر، الأكثر مشاهدة، الأقرب) | `ads.html` | `GET /ads`, `/categories/{id}` |
| تفاصيل الإعلان — صور، مواصفات، وصف، البائع وتقييماته، إعلانات مشابهة | `ad.html?id=…` | `GET /ads/{id}`, `/ads/{id}/similar`, `/users/{id}/ratings` |

Actions that need an account (messaging, favorites, reporting, posting) open a "download the app" popup.
Only `GET` requests are made. No login, and nothing is written to the database.

## قبل الرفع / Before uploading
Edit **`assets/js/config.js`** only:
- `API_BASE_URL` — the live API address ending in `/api`, e.g. `https://api.example.com/api`
- `APP_LINKS` — Google Play / App Store links (they show "قريباً" until filled)
- `CONTACT` — phone, WhatsApp, email, social links for the footer
- `GOVERNORATES` — must match the ids in the server's `governorates` table (the API has no endpoint for them)

Colors are at the top of `assets/css/style.css` (`--primary`, `--accent`).

## الرفع / Deploy
Static files only (no PHP, no build step). Upload the **contents** of this `website/` folder with FileZilla to any web root, e.g. `/var/www/simsar-web`, and point a domain or subdomain at it (Nginx `root /var/www/simsar-web; index index.html;`).

The API already allows cross-origin requests (`config/cors.php`, `CORS_ALLOWED_ORIGINS=*` by default). If you restrict `CORS_ALLOWED_ORIGINS` on the server later, add the website's domain to it.

## تجربة محلية / Local test
```bash
php artisan serve            # API on :8000
cd website && python3 -m http.server 8080   # open http://localhost:8080
```
