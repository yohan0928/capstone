<?php

use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:super_admin', 'prevent_back_history'])->prefix('main')->name('main.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'showOwners'])->name('showOwners');
    Route::patch('/{owner:uuid}/status', [SuperAdminController::class, 'updateOwnerAccountStatus'])->name('updateOwnerAccountStatus');
});
