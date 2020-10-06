<?php


\Route::prefix('/payment')->group(function () {
    \Route::get('/confirm/{id}', [\App\Http\Controllers\PaymentController::class, 'confirment'])->name('nestpay.confirment');
    \Route::post('/confirm/{id}', [\App\Http\Controllers\PaymentController::class, 'confirm'])->name('nestpay.confirm');
    \Route::post('/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
    \Route::post('/fail', [\App\Http\Controllers\PaymentController::class, 'fail'])->name('payment.fail');
});


