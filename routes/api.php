<?php

use App\Http\Controllers\Api\FieldDataController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/field-data', [FieldDataController::class, 'store']);
    Route::post('/drone/field-data', [FieldDataController::class, 'drone']);
    Route::get('/field-data/{fieldData}', [FieldDataController::class, 'show']);
    Route::get('/fields/{field}/data', [FieldDataController::class, 'forField']);
});
