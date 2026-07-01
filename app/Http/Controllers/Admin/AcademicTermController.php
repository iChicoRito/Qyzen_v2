<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// F8: admin academic-term management.
class AcademicTermController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AcademicTerm::class);

        $terms = AcademicTerm::with('year')->orderByDesc('id')->get();

        return view('admin.academic-terms.index', compact('terms'));
    }

    public function create(): View
    {
        $this->authorize('create', AcademicTerm::class);

        return view('admin.academic-terms.create', ['years' => AcademicYear::orderByDesc('year')->get()]);
    }

    public function store(StoreAcademicTermRequest $request): RedirectResponse
    {
        $this->authorize('create', AcademicTerm::class);

        AcademicTerm::create($request->validated());

        return redirect()->route('admin.academic-terms.index')->with('status', 'Academic term created.');
    }

    public function show(AcademicTerm $academicTerm): View
    {
        $this->authorize('view', $academicTerm);

        $academicTerm->load('year');

        return view('admin.academic-terms.show', ['term' => $academicTerm]);
    }

    public function edit(AcademicTerm $academicTerm): View
    {
        $this->authorize('update', $academicTerm);

        return view('admin.academic-terms.edit', [
            'term' => $academicTerm,
            'years' => AcademicYear::orderByDesc('year')->get(),
        ]);
    }

    public function update(UpdateAcademicTermRequest $request, AcademicTerm $academicTerm): RedirectResponse
    {
        $this->authorize('update', $academicTerm);

        $academicTerm->update($request->validated());

        return redirect()->route('admin.academic-terms.index')->with('status', 'Academic term updated.');
    }

    public function destroy(AcademicTerm $academicTerm): RedirectResponse
    {
        $this->authorize('delete', $academicTerm);

        $academicTerm->delete();

        return redirect()->route('admin.academic-terms.index')->with('status', 'Academic term deleted.');
    }
}
