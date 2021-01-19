<?php


\Route::prefix('/payment')->group(function () {
    \Route::get('/confirm/{code}', [\App\Http\Controllers\PaymentController::class, 'confirment'])->name('payment.confirment');
    \Route::post('/confirm/{code}', [\App\Http\Controllers\PaymentController::class, 'confirm'])->name('payment.confirm');
    \Route::post('/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
    \Route::post('/fail', [\App\Http\Controllers\PaymentController::class, 'fail'])->name('payment.fail');
});

Route::get('/', [\App\Http\Controllers\Controller::class, 'index']);


