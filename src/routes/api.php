<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NoticeTypeController;

Route::prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index']);
    Route::post('/', [MemberController::class, 'store']);
    Route::get('/{id}', [MemberController::class, 'show']);
    Route::put('/{id}', [MemberController::class, 'update']);
    Route::delete('/{id}', [MemberController::class, 'destroy']);
});

Route::prefix('notices')->group(function () {
    Route::get('/', [NoticeController::class, 'index']);
    Route::post('/', [NoticeController::class, 'store']);
    Route::get('/{id}', [NoticeController::class, 'show']);
    Route::put('/{id}', [NoticeController::class, 'update']);
    Route::delete('/{id}', [NoticeController::class, 'destroy']);
});

Route::prefix('notice-types')->group(function () {
    Route::get('/', [NoticeTypeController::class, 'index']);
    Route::post('/', [NoticeTypeController::class, 'store']);
    Route::get('/{id}', [NoticeTypeController::class, 'show']);
    Route::put('/{id}', [NoticeTypeController::class, 'update']);
    Route::delete('/{id}', [NoticeTypeController::class, 'destroy']);
});
