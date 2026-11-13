<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use App\Notifications\AdApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

// The public website in two languages (Arabic default, English via the
// /lang/{locale} switch), plus the shared chrome every page carries: the
// dark-mode toggle and the "Powered by AvenoCode" footer.
class SiteLocalizationTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_the_site_is_arabic_and_right_to_left_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('استكشف')
            ->assertSee('English');
    }

    public function test_every_page_has_the_dark_mode_toggle_and_the_powered_by_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="theme-toggle"', false)
            ->assertSee('Powered by <strong>AvenoCode</strong>', false);

        $this->get('/explore')->assertOk()->assertSee('AvenoCode');
        $this->get('/login')->assertOk()->assertSee('AvenoCode');
    }

    public function test_switching_to_english_sets_a_cookie_and_returns_to_the_previous_page(): void
    {
        $this->from('/explore')->get('/lang/en')
            ->assertRedirect('/explore')
            ->assertCookie('locale', 'en');
    }

    public function test_an_unsupported_language_is_a_404(): void
    {
        $this->get('/lang/fr')->assertNotFound();
    }

    public function test_with_the_english_cookie_the_site_is_english_left_to_right_and_uses_english_data(): void
    {
        $cars = Category::factory()->create(['name_ar' => 'سيارات', 'name_en' => 'Cars']);
        Ad::factory()->create(['title' => 'Toyota Corolla 2019', 'status' => 'approved', 'category_id' => $cars->id]);

        $this->withCookie('locale', 'en')->get('/')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Explore')
            ->assertSee('Latest ads')
            ->assertSee('Cars')
            ->assertDontSee('سيارات')
            ->assertSee('العربية');
    }

    public function test_an_english_category_name_falls_back_to_arabic_when_blank(): void
    {
        $cat = Category::factory()->create(['name_ar' => 'أثاث', 'name_en' => '']);

        app()->setLocale('en');
        $this->assertSame('أثاث', $cat->localized('name'));
        app()->setLocale('ar');
        $this->assertSame('أثاث', $cat->localized('name'));
    }

    public function test_notifications_are_rendered_in_the_visitors_language(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'title' => 'Blue sofa']);
        $user->notify(new AdApproved($ad));

        // Arabic first: withCookie() sticks to every later request in the test.
        $this->actingAs($user)->get('/notifications')
            ->assertOk()
            ->assertSee('تمت الموافقة على إعلانك');

        $this->actingAs($user)->withCookie('locale', 'en')->get('/notifications')
            ->assertOk()
            ->assertSee('Your ad &quot;Blue sofa&quot; was approved and is now live.', false);
    }

    public function test_validation_errors_follow_the_site_language(): void
    {
        $this->from('/register')->post('/register', [])->assertRedirect('/register');
        $this->get('/register')->assertSee('حقل الاسم مطلوب.');

        $this->withCookie('locale', 'en')->from('/register')->post('/register', [])->assertRedirect('/register');
        $this->withCookie('locale', 'en')->get('/register')->assertSee('The name field is required.');
    }

    public function test_the_admin_panel_follows_the_same_language_choice(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('لوحة التحكم');

        $this->actingAs($admin)->withCookie('locale', 'en')->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Control panel')
            ->assertSee('Ads pending review')
            ->assertSee('data-theme-toggle', false);

        $this->withCookie('locale', 'en')->get(route('admin.ads.index'))->assertOk()->assertSee('Review ads');
    }

    public function test_the_admin_login_page_has_the_language_and_theme_toggles(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee(route('site.locale', 'en'), false);
    }

    public function test_push_notifications_to_sellers_stay_arabic_when_the_admin_uses_english(): void
    {
        $this->assertStringContainsString(
            "'تمت الموافقة على إعلانك'",
            file_get_contents(app_path('Http/Controllers/AdminPanel/AdModerationController.php')),
        );
    }

    // Guard against untranslated text: every __('…') / trans_choice('…') key
    // used by the website must have an English entry in lang/en.json.
    // (Arabic needs no file — the Arabic text is the key itself.)
    public function test_every_website_string_has_an_english_translation(): void
    {
        $english = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);

        $files = array_merge(
            $this->filesIn(resource_path('views/site'), '.blade.php'),
            $this->filesIn(resource_path('views/admin'), '.blade.php'),
            $this->filesIn(app_path('Http/Controllers/Site'), '.php'),
            $this->filesIn(app_path('Http/Controllers/AdminPanel'), '.php'),
            $this->filesIn(app_path('Services'), '.php'),
        );

        $missing = [];
        foreach ($files as $file) {
            preg_match_all("/(?:__|trans_choice)\\('((?:[^'\\\\]|\\\\.)*)'/u", file_get_contents($file), $matches);
            foreach ($matches[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                if (! array_key_exists($key, $english)) {
                    $missing[] = basename($file).': '.$key;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Add these keys to lang/en.json');
    }

    private function filesIn(string $dir, string $suffix): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }
}
