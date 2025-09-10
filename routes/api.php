<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelController; // Adjust controller namespace as needed

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Your Excel API routes
Route::get('/excel-sheet-data', [ExcelController::class, 'getSheetData']);
Route::post('/save-excel-cell', [ExcelController::class, 'saveCellData']);

Route::middleware('auth:sanctum')->get('/current-user', [DashboardController::class, 'currentUser']);
