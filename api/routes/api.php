<?php

use App\Http\Controllers\Api\DomainController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::get('/domains', [DomainController::class, 'index']);
    Route::post('/domains/{domain}/content', [DomainController::class, 'storeContent']);
});
