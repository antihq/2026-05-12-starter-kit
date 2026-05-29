<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages::home')->name('home')->middleware('guest');

Route::get('/manifest', function () {
    return response()->view('manifest-json')->header('Content-Type', 'application/json');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('/dashboard', 'pages.dashboard')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}', 'pages::invitations.show')->name('invitations.show');
});

require __DIR__.'/settings.php';
