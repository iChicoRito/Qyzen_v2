<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

// F1: admin dashboard — blank for now. Stats/widgets to be added later (were removed with the
// blank redesign); live-refresh deferred to Stage I.
class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.dashboard');
    }
}
