<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiController;

Route::post('/employee-evaluate', [GeminiController::class, 'generate']);
