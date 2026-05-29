<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SceneController;
use App\Http\Controllers\TransitionTemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('contents.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/theme', [SettingController::class, 'updateTheme'])->name('settings.theme');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password.reset');
        Route::put('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');

        Route::post('/transition-templates', [TransitionTemplateController::class, 'store'])->name('transition-templates.store');
        Route::delete('/transition-templates/{transitionTemplate}', [TransitionTemplateController::class, 'destroy'])->name('transition-templates.destroy');
    });

    Route::get('/contents', [ContentController::class, 'index'])->name('contents.index');
    Route::post('/contents', [ContentController::class, 'store'])->name('contents.store');
    Route::get('/contents/{content}', [ContentController::class, 'show'])->name('contents.show');
    Route::put('/contents/{content}', [ContentController::class, 'update'])->name('contents.update');
    Route::delete('/contents/{content}', [ContentController::class, 'destroy'])->name('contents.destroy');

    Route::get('/transition-templates', [TransitionTemplateController::class, 'index'])->name('transition-templates.index');
    Route::get('/transition-templates/{transitionTemplate}/gif', [TransitionTemplateController::class, 'gif'])->name('transition-templates.media.gif');
    Route::get('/transition-templates/{transitionTemplate}/audio', [TransitionTemplateController::class, 'audio'])->name('transition-templates.media.audio');

    Route::get('/preview', [PreviewController::class, 'index'])->name('preview.index');
    Route::post('/contents/{content}/scenes', [SceneController::class, 'store'])->name('scenes.store');
    Route::get('/scenes/{scene}', [SceneController::class, 'show'])->name('scenes.show');
    Route::get('/scenes/{scene}/gif', [SceneController::class, 'gif'])->name('scenes.media.gif');
    Route::get('/scenes/{scene}/audio', [SceneController::class, 'audio'])->name('scenes.media.audio');
    Route::put('/scenes/{scene}', [SceneController::class, 'update'])->name('scenes.update');
    Route::delete('/scenes/{scene}', [SceneController::class, 'destroy'])->name('scenes.destroy');
    Route::post('/scenes/{scene}/duplicate', [SceneController::class, 'duplicate'])->name('scenes.duplicate');

    Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('/exports/scenes/{scene}', [ExportController::class, 'scene'])->name('exports.scenes');
    Route::get('/exports/contents/{content}', [ExportController::class, 'content'])->name('exports.contents');
});
