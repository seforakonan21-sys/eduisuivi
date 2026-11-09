<?php
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AppelController;
use App\Http\Controllers\EspaceParentController;
use App\Http\Controllers\NotificationController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EleveController;
use App\Http\Controllers\EleveParentController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\Structure\AffectationController;
use App\Http\Controllers\Structure\AnneeScolaireController;
use App\Http\Controllers\Structure\ClasseController;
use App\Http\Controllers\Structure\MatiereController;
use App\Http\Controllers\Structure\NiveauController;
use App\Http\Controllers\Structure\PeriodeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ResultatController;
use App\Http\Controllers\AppreciationController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\EtablissementController;

Route::redirect('/', '/connexion');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')   // 5 tentatives par minute max
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    // Exemple de zone réservée : sert aussi à tester le middleware de rôle
    Route::view('/administration', 'administration')
        ->middleware('role:admin_general')
        ->name('administration');
});

Route::middleware(['auth', 'role:directeur,administratif'])
    ->prefix('structure')
    ->name('structure.')
    ->group(function () {
        $crud = fn (string $nom, string $controleur, string $param) => Route::resource($nom, $controleur, [
            'only' => ['index', 'store', 'destroy'],
            'parameters' => [$nom => $param],
        ]);

        $crud('annees', AnneeScolaireController::class, 'annee');
        $crud('periodes', PeriodeController::class, 'periode');
        $crud('niveaux', NiveauController::class, 'niveau');
        $crud('classes', ClasseController::class, 'classe');
        $crud('matieres', MatiereController::class, 'matiere');
        $crud('affectations', AffectationController::class, 'affectation');
    });

Route::middleware(['auth', 'role:directeur,administratif'])->group(function () {
    Route::resource('eleves', EleveController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['eleves' => 'eleve']);

    Route::post('eleves/{eleve}/inscriptions', [InscriptionController::class, 'store'])
        ->name('eleves.inscriptions.store');
    Route::patch('inscriptions/{inscription}', [InscriptionController::class, 'update'])
        ->name('inscriptions.update');

    Route::post('eleves/{eleve}/parents', [EleveParentController::class, 'store'])
        ->name('eleves.parents.store');
    Route::delete('eleves/{eleve}/parents/{parent}', [EleveParentController::class, 'destroy'])
        ->name('eleves.parents.destroy');

    Route::resource('parents', ParentController::class)
        ->only(['index', 'store', 'destroy'])
        ->parameters(['parents' => 'parent']);
});
Route::middleware(['auth', 'role:directeur,enseignant'])->group(function () {
    Route::get('evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('evaluations/create', [EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::delete('evaluations/{evaluation}', [EvaluationController::class, 'destroy'])->name('evaluations.destroy');

    Route::get('evaluations/{evaluation}/notes', [NoteController::class, 'edit'])->name('evaluations.notes.edit');
    Route::put('evaluations/{evaluation}/notes', [NoteController::class, 'update'])->name('evaluations.notes.update');
});

// La validation est réservée au directeur
Route::middleware(['auth', 'role:directeur'])->group(function () {
    Route::patch('evaluations/{evaluation}/valider', [EvaluationController::class, 'valider'])->name('evaluations.valider');
    Route::patch('evaluations/{evaluation}/devalider', [EvaluationController::class, 'devalider'])->name('evaluations.devalider');
});

Route::middleware(['auth', 'role:directeur,administratif,enseignant'])
    ->get('resultats', [ResultatController::class, 'index'])->name('resultats.index');
    Route::middleware(['auth', 'role:directeur,administratif,enseignant'])->group(function () {
    Route::get('appel', [AppelController::class, 'index'])->name('appel.index');
    Route::post('appel', [AppelController::class, 'store'])->name('appel.store');
    Route::get('absences', [AbsenceController::class, 'index'])->name('absences.index');
});

Route::middleware(['auth', 'role:directeur,administratif'])->group(function () {
    Route::patch('absences/{absence}/justification', [AbsenceController::class, 'justifier'])->name('absences.justifier');
    Route::delete('absences/{absence}/justification', [AbsenceController::class, 'retirerJustification'])->name('absences.retirer');
});

Route::middleware(['auth', 'role:parent'])
    ->get('mon-espace/absences', [EspaceParentController::class, 'absences'])->name('espace.absences');

Route::middleware('auth')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/lues', [NotificationController::class, 'toutLire'])->name('notifications.lues');
});

Route::middleware(['auth', 'role:directeur,enseignant'])->group(function () {
    Route::get('appreciations', [AppreciationController::class, 'edit'])->name('appreciations.edit');
    Route::put('appreciations', [AppreciationController::class, 'update'])->name('appreciations.update');
});

Route::middleware(['auth', 'role:directeur,administratif'])->group(function () {
    Route::get('bulletins', [BulletinController::class, 'index'])->name('bulletins.index');
    Route::get('bulletins/classes/{classe}/periodes/{periode}/pdf', [BulletinController::class, 'pdfClasse'])
        ->name('bulletins.pdf.classe');
});

Route::middleware(['auth', 'role:directeur'])->group(function () {
    Route::get('bulletins/eleves/{eleve}/periodes/{periode}/edit', [BulletinController::class, 'edit'])->name('bulletins.edit');
    Route::put('bulletins/eleves/{eleve}/periodes/{periode}', [BulletinController::class, 'update'])->name('bulletins.update');
    Route::post('bulletins/classes/{classe}/periodes/{periode}/publication', [BulletinController::class, 'publier'])
        ->name('bulletins.publier');
    Route::delete('bulletins/classes/{classe}/periodes/{periode}/publication', [BulletinController::class, 'depublier'])
        ->name('bulletins.depublier');

    Route::get('mon-etablissement', [EtablissementController::class, 'edit'])->name('etablissement.edit');
    Route::put('mon-etablissement', [EtablissementController::class, 'update'])->name('etablissement.update');
});

// Le PDF d'un élève : direction, secrétariat, et parents (seulement si le bulletin est publié)
Route::middleware(['auth', 'role:directeur,administratif,parent'])
    ->get('bulletins/eleves/{eleve}/periodes/{periode}/pdf', [BulletinController::class, 'pdf'])
    ->name('bulletins.pdf');

Route::middleware(['auth', 'role:parent'])
    ->get('mon-espace/bulletins', [EspaceParentController::class, 'bulletins'])->name('espace.bulletins');