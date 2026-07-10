<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DatabaseBackupService;
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

    // Task 05: streams a full schema+data SQL export — no shell_exec/mysqldump on Hostinger.
    public function downloadDatabase()
    {
        $this->authorize('viewAny', User::class);

        return response()->streamDownload(function () {
            foreach (app(DatabaseBackupService::class)->lines() as $line) {
                echo $line;
            }
        }, 'qyzen-backup-'.now()->format('Y-m-d_His').'.sql', [
            'Content-Type' => 'application/sql',
        ]);
    }
}
