<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('tasks/{task}/start', 'pages::tasks.start')->name('tasks.start');
});

require __DIR__.'/settings.php';
