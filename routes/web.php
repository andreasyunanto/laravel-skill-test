<?php

use App\Http\Controllers\Post\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('posts', PostController::class)
        ->only(['store', 'update', 'destroy'])
        ->names([
            'store' => 'post.store',
            'update' => 'post.update',
            'destroy' => 'post.delete',
        ]);
});

Route::resource('posts', PostController::class)
    ->only(['index', 'create', 'edit', 'show'])
    ->names([
        'index' => 'post.list',
        'create' => 'post.create',
        'edit' => 'post.edit',
        'show' => 'post.view',
    ]);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
