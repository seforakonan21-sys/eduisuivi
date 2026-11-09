<?php

namespace App\Http\Controllers;
use App\Models\Bulletin;
use App\Models\Absence;
use Illuminate\Http\Request;

class EspaceParentController extends Controller
{
    public function absences(Request $request)
    {
        $enfants = $request->user()->enfants()->with('inscriptions.classe')->orderBy('nom')->get();

        $absences = Absence::whereIn('eleve_id', $enfants->pluck('id'))
            ->whereDate('date_absence', '>=', now()->subDays(90)->toDateString())
            ->orderByDesc('date_absence')
            ->get()
            ->groupBy('eleve_id');

        return view('espace.absences', compact('enfants', 'absences'));
    }

    public function bulletins(Request $request)
    {
    $enfants = $request->user()->enfants()->orderBy('nom')->get();

    $bulletins = Bulletin::with('periode')
        ->whereIn('eleve_id', $enfants->pluck('id'))
        ->where('publie', true)
        ->get()
        ->sortBy(fn ($b) => $b->periode->ordre)
        ->groupBy('eleve_id');

    return view('espace.bulletins', compact('enfants', 'bulletins'));
    }
}