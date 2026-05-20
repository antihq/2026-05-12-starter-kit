<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings', 'pages::settings.show')->name('settings.show');

    Route::livewire('teams', 'pages::teams.index')->name('teams.index');
    Route::livewire('teams/create', 'pages::teams.create')->name('teams.create');
    Route::livewire('teams/{team}', 'pages::teams.show')->name('teams.show');
});
