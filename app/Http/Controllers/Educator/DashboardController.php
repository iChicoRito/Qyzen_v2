<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

// G1: educator dashboard — blank for now. Ownership-scoped stats/widgets to be added later.
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('educator.dashboard');
    }
}
