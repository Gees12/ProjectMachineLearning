<?php

use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\FactorController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ResponseController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\Student\SubmissionController;
use Illuminate\Support\Facades\Route;

// Daftar route: docs/03_System_Design/API_Documentation.md bagian B

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/change', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/password/change', [AuthController::class, 'updatePassword'])->name('password.change');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', HomeController::class)->name('home');

        // Mahasiswa
        Route::middleware('role:mahasiswa')->prefix('survey')->name('survey.')->controller(SubmissionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/consent', 'consent')->name('consent');
            Route::post('/start', 'start')->name('start');
            Route::get('/fill', 'fill')->name('fill');
            Route::post('/submit', 'submit')->name('submit');
            Route::get('/thanks', 'thanks')->name('thanks');
        });

        // Pengelola dan Admin
        Route::middleware('role:pengelola,admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/compare', [DashboardController::class, 'compare'])->name('dashboard.compare');
            Route::get('/simulation', [SimulationController::class, 'index'])->name('simulation.index');
            Route::post('/simulation', [SimulationController::class, 'run'])->name('simulation.run');
            Route::get('/reports/{survey}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
            Route::get('/reports/{survey}/excel', [ReportController::class, 'excel'])->name('reports.excel');
        });

        // Admin
        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
            Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset');
            Route::delete('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');

            Route::resource('surveys', SurveyController::class);
            Route::post('/surveys/{survey}/duplicate', [SurveyController::class, 'duplicate'])->name('surveys.duplicate');
            Route::patch('/surveys/{survey}/open', [SurveyController::class, 'open'])->name('surveys.open');
            Route::patch('/surveys/{survey}/close', [SurveyController::class, 'close'])->name('surveys.close');

            Route::post('/surveys/{survey}/factors', [FactorController::class, 'store'])->name('factors.store');
            Route::put('/surveys/{survey}/factors/{factor}', [FactorController::class, 'update'])->name('factors.update');
            Route::delete('/surveys/{survey}/factors/{factor}', [FactorController::class, 'destroy'])->name('factors.destroy');

            Route::resource('surveys.questions', QuestionController::class)->except(['index', 'show']);

            Route::get('/surveys/{survey}/responses', [ResponseController::class, 'index'])->name('responses.index');
            Route::get('/surveys/{survey}/responses/export', [ResponseController::class, 'export'])->name('responses.export');
            Route::patch('/responses/{response}/validity', [ResponseController::class, 'toggleValidity'])->name('responses.validity');

            Route::get('/surveys/{survey}/analyses', [AnalysisController::class, 'index'])->name('analyses.index');
            Route::post('/surveys/{survey}/analyze', [AnalysisController::class, 'run'])->name('analyses.run');
        });
    });
});
