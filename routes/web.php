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
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->names([
            'index' => 'post.list',
            'create' => 'post.create',
            'store' => 'post.store',
            'show' => 'post.view',
            'edit' => 'post.edit',
            'update' => 'post.update',
            'destroy' => 'post.delete',
        ]);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
