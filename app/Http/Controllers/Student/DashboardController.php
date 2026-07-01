<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

// H1: student dashboard — blank for now. Own-data stats/widgets to be added later.
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('student.dashboard');
    }
}
