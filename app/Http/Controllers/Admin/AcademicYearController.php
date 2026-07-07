<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// F7: admin academic-year management. Delete cascades to child terms (FK is
// restrictOnDelete in the migration, so the cascade is hand-rolled in a transaction).
class AcademicYearController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicYear::class);

        $query = AcademicYear::withCount('terms');
        TableQuery::search($query, $request->query('search'), ['year']);
        TableQuery::filters($query, $request, ['status' => 'is_active']);
        TableQuery::sort($query, $request, [
            'year' => 'year',
            'terms' => 'terms_count',
            'status' => 'is_active',
        ], 'year', 'desc');

        $years = $query->paginate(TableQuery::perPage($request))->withQueryString();

        return view('admin.academic-years.index', compact('years'));
    }

    public function create(): View
    {
        $this->authorize('create', AcademicYear::class);

        return view('admin.academic-years.create');
    }

    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        AcademicYear::create($request->validated());

        return redirect()->route('admin.academic-years.index')->with('status', 'Academic year created.');
    }

    public function show(AcademicYear $academicYear): View
    {
        $this->authorize('view', $academicYear);

        $academicYear->load('terms');

        return view('admin.academic-years.show', ['year' => $academicYear]);
    }

    public function edit(AcademicYear $academicYear): View
    {
        $this->authorize('update', $academicYear);

        return view('admin.academic-years.edit', ['year' => $academicYear]);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $academicYear->update($request->validated());

        return redirect()->route('admin.academic-years.index')->with('status', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        DB::transaction(function () use ($academicYear) {
            $academicYear->terms()->delete();   // child terms first (FK restricts on delete)
            $academicYear->delete();
        });

        return redirect()->route('admin.academic-years.index')->with('status', 'Academic year and its terms deleted.');
    }
}
