<?php
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
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

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
    Route::resource('eleves', EleveController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::post('eleves/{eleve}/inscriptions', [InscriptionController::class, 'store'])
        ->name('eleves.inscriptions.store');
    Route::patch('inscriptions/{inscription}', [InscriptionController::class, 'update'])
        ->name('inscriptions.update');

    Route::post('eleves/{eleve}/parents', [EleveParentController::class, 'store'])
        ->name('eleves.parents.store');
    Route::delete('eleves/{eleve}/parents/{parent}', [EleveParentController::class, 'destroy'])
        ->name('eleves.parents.destroy');

    Route::resource('parents', ParentController::class)->only(['index', 'store', 'destroy']);
});