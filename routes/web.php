<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SceneController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::put('/settings/theme', [SettingController::class, 'updateTheme'])->name('settings.theme');

Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

Route::post('/contents', [ContentController::class, 'store'])->name('contents.store');
Route::get('/contents/{content}', [ContentController::class, 'show'])->name('contents.show');
Route::put('/contents/{content}', [ContentController::class, 'update'])->name('contents.update');
Route::delete('/contents/{content}', [ContentController::class, 'destroy'])->name('contents.destroy');

Route::post('/contents/{content}/scenes', [SceneController::class, 'store'])->name('scenes.store');
Route::get('/scenes/{scene}', [SceneController::class, 'show'])->name('scenes.show');
Route::get('/scenes/{scene}/gif', [SceneController::class, 'gif'])->name('scenes.media.gif');
Route::get('/scenes/{scene}/audio', [SceneController::class, 'audio'])->name('scenes.media.audio');
Route::put('/scenes/{scene}', [SceneController::class, 'update'])->name('scenes.update');
Route::delete('/scenes/{scene}', [SceneController::class, 'destroy'])->name('scenes.destroy');
Route::post('/scenes/{scene}/duplicate', [SceneController::class, 'duplicate'])->name('scenes.duplicate');

Route::get('/exports/scenes/{scene}', [ExportController::class, 'scene'])->name('exports.scenes');
Route::get('/exports/contents/{content}', [ExportController::class, 'content'])->name('exports.contents');
