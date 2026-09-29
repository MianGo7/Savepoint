<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('projects', 'pages::projects.index')->name('projects.index');
    Route::livewire('projects/{project}', 'pages::projects.show')->name('projects.show');

    Route::livewire('projects/{project}/tasks/create', 'pages::tasks.form')->name('tasks.create');
    Route::livewire('tasks/{task}/edit', 'pages::tasks.form')->name('tasks.edit');
    Route::livewire('tasks/{task}/start', 'pages::tasks.start')->name('tasks.start');
});

require __DIR__.'/settings.php';
