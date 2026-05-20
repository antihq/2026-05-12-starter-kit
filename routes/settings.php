<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings', 'pages::settings.show')->name('settings.show');

    Route::livewire('teams', 'pages::teams.index')->name('teams.index');
    Route::livewire('teams/create', 'pages::teams.create')->name('teams.create');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::livewire('teams/{team}', 'pages::teams.show')->name('teams.show');
        Route::livewire('teams/{team}/edit', 'pages::teams.edit')->name('teams.edit');
        Route::livewire('teams/{team}/delete', 'pages::teams.delete')->name('teams.delete');
        Route::livewire('teams/{team}/members', 'pages::teams.members.index')->name('teams.members');
        Route::livewire('teams/{team}/members/{user}', 'pages::teams.members.show')->name('teams.members.show');
        Route::livewire('teams/{team}/members/{user}/edit', 'pages::teams.members.edit')->name('teams.members.edit');
        Route::livewire('teams/{team}/invitations', 'pages::teams.invitations.index')->name('teams.invitations');
        Route::livewire('teams/{team}/invitations/create', 'pages::teams.invitations.create')->name('teams.invitations.create');
        Route::livewire('teams/{team}/invitations/{invitation}', 'pages::teams.invitations.show')->name('teams.invitations.show');
    });
});
