<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);
        $announcements = Announcement::visibleTo(Auth::user())
            ->with(['educator:id,given_name,surname,profile_picture', 'subject:id,subject_code,subject_name'])
            ->latest()->paginate(10);

        return view('student.announcements.index', compact('announcements'));
    }

    public function image(Announcement $announcement, int $image)
    {
        $this->authorize('view', $announcement);
        $item = $announcement->images[$image] ?? null;
        abort_unless(is_array($item) && isset($item['path']) && Storage::disk('local')->exists($item['path']), 404);

        return response()->file(Storage::disk('local')->path($item['path']), [
            'Content-Type' => $item['mime'] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($item['name'] ?? 'announcement-image').'"',
        ]);
    }
}
