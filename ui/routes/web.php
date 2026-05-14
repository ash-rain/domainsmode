<?php

use App\Livewire\Domains\BulkContentCreate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('domains/bulk-create', BulkContentCreate::class)
    ->middleware(['auth', 'verified'])
    ->name('domains.bulk-create');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
