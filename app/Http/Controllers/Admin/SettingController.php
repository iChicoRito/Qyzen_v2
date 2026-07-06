<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.settings.index', [
            'offlineRegistrationEnabled' => SystemSetting::offlineRegistrationEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $request->validate([
            'offline_registration_enabled' => ['nullable', 'boolean'],
        ]);

        SystemSetting::setOfflineRegistrationEnabled((bool) ($data['offline_registration_enabled'] ?? false));

        return back()->with('status', 'Settings updated.');
    }
}
