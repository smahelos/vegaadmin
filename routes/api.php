<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ClientCrudController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Kategorie
Route::get('/clients', [ClientCrudController::class, 'index']);
Route::get('/client/{id}', [ClientCrudController::class, 'fetch']);