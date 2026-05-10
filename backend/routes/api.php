<?php

use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::post('/receipts', [ReceiptController::class, 'upload']);

Route::get('/expenses', [ExpenseController::class, 'index']);
Route::get('/expenses/{id}', [ExpenseController::class, 'show'])->whereNumber('id');
Route::patch('/expenses/{id}', [ExpenseController::class, 'update'])->whereNumber('id');
Route::post('/expenses/{id}/reevaluate', [ExpenseController::class, 'reevaluate'])->whereNumber('id');
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])->whereNumber('id');

Route::get('/policies', [PolicyController::class, 'index']);
Route::post('/policies', [PolicyController::class, 'store']);
Route::patch('/policies/{id}', [PolicyController::class, 'update'])->whereNumber('id');
Route::delete('/policies/{id}', [PolicyController::class, 'destroy'])->whereNumber('id');
