<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OrderController::class, 'index'])->name('orders.index');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
Route::post('/orders/reset', [OrderController::class, 'reset'])->name('orders.reset');
