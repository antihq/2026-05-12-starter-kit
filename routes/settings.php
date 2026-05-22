<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings', 'pages::settings.show')->name('settings.show');

    Route::livewire('teams', 'pages::teams.switch')->name('teams.switch');
    Route::livewire('teams/{team}', 'pages::teams.show')->name('teams.show');
});
