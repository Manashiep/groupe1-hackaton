<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Quartier;
use App\Services\CleanlinessIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuartierController extends Controller
{
    public function index(CleanlinessIndex $index): View
    {
        $quartiers = Quartier::withCount([
            'schedules' => fn ($q) => $q->where('is_active', true),
            'residents',
            'reports as open_reports' => fn ($q) => $q->whereIn('status', ReportStatus::open()),
        ])->orderBy('ville')->orderBy('name')->get();

        return view('admin.quartiers.index', [
            'quartiers' => $quartiers->map(fn ($q) => ['quartier' => $q, ...$index->for($q)]),
        ]);
    }

    public function create(): View
    {
        return view('admin.quartiers.form', ['quartier' => new Quartier(['ile' => 'Ngazidja', 'ville' => 'Moroni'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quartier = Quartier::create([...$data, 'slug' => $this->uniqueSlug($data['name'])]);

        return redirect()->route('admin.quartiers.index')->with('success', "Quartier « {$quartier->name} » ajouté.");
    }

    public function edit(Quartier $quartier): View
    {
        return view('admin.quartiers.form', ['quartier' => $quartier]);
    }

    public function update(Request $request, Quartier $quartier): RedirectResponse
    {
        $quartier->update($this->validated($request, $quartier));

        return redirect()->route('admin.quartiers.index')->with('success', "Quartier « {$quartier->name} » mis à jour.");
    }

    public function destroy(Quartier $quartier): RedirectResponse
    {
        if ($quartier->reports()->exists() || $quartier->residents()->exists()) {
            return back()->with('error', 'Ce quartier a des habitants ou des signalements : il ne peut pas être supprimé.');
        }
        $quartier->delete();

        return back()->with('success', 'Quartier supprimé.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Quartier $quartier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('quartiers')->where('ville', $request->input('ville'))->ignore($quartier)],
            'ville' => ['required', 'string', 'max:100'],
            'ile' => ['required', Rule::in(['Ngazidja', 'Ndzuwani', 'Mwali'])],
            'latitude' => ['required', 'numeric', 'between:-12.6,-11.2'],
            'longitude' => ['required', 'numeric', 'between:43.1,44.7'],
            'population' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = $base = Str::slug($name);
        for ($i = 2; Quartier::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
