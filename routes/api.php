<?php

use App\Http\Controllers\ChatbotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/chatbot/message', [ChatbotController::class, 'store']);
Route::get('/chatbot/categories', [ChatbotController::class, 'categories']);
