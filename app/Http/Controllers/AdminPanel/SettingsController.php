<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

// Settings the client edits without server access: the Sham Cash account
// sellers pay to, and the contact details shown in the privacy policy.
class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.index', [
            'values' => collect(Setting::KEYS)->mapWithKeys(fn ($label, $key) => [$key => Setting::get($key)]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'shamcash_account_name' => ['nullable', 'string', 'max:150'],
            'shamcash_account_number' => ['nullable', 'string', 'max:100'],
            'shamcash_instructions' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        Setting::put($data);

        return back()->with('status', __('تم حفظ الإعدادات.'));
    }
}
