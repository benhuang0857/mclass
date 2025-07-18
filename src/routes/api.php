<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NoticeTypeController;
use App\Http\Controllers\InvitationCodeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LevelTypeController;
use App\Http\Controllers\LangTypeController;
use App\Http\Controllers\TechMethodTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CourseInfoTypeController;
use App\Http\Controllers\CourseStatusTypeController;
use App\Http\Controllers\ClubCourseInfoController;
use App\Http\Controllers\ClubCourseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;

Route::prefix('invitation-codes')->group(function () {
    Route::get('/', [InvitationCodeController::class, 'index']);
    Route::post('/', [InvitationCodeController::class, 'store']);
    Route::get('/{id}', [InvitationCodeController::class, 'show']);
    Route::put('/{id}', [InvitationCodeController::class, 'update']);
    Route::delete('/{id}', [InvitationCodeController::class, 'destroy']);
});

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

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);

    // Follower-related routes
    Route::post('/{id}/follower', [ProductController::class, 'addFollower']);
    Route::delete('/{id}/follower', [ProductController::class, 'removeFollower']);

    // Visibler-related routes
    Route::post('/{id}/visibler', [ProductController::class, 'addVisibler']);
    Route::delete('/{id}/visibler', [ProductController::class, 'removeVisibler']);
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
    Route::delete('/{id}', [OrderController::class, 'destroy']);
});

Route::prefix('course-info-types')->group(function () {
    Route::get('/', [CourseInfoTypeController::class, 'index']);
    Route::post('/', [CourseInfoTypeController::class, 'store']);
    Route::get('/{id}', [CourseInfoTypeController::class, 'show']);
    Route::put('/{id}', [CourseInfoTypeController::class, 'update']);
    Route::delete('/{id}', [CourseInfoTypeController::class, 'destroy']);
});

Route::prefix('course-status-types')->group(function () {
    Route::get('/', [CourseStatusTypeController::class, 'index']);
    Route::post('/', [CourseStatusTypeController::class, 'store']);
    Route::get('/{id}', [CourseStatusTypeController::class, 'show']);
    Route::put('/{id}', [CourseStatusTypeController::class, 'update']);
    Route::delete('/{id}', [CourseStatusTypeController::class, 'destroy']);
});

Route::prefix('club-course-info')->group(function () {
    Route::get('/', [ClubCourseInfoController::class, 'index']);
    Route::post('/', [ClubCourseInfoController::class, 'store']);
    Route::get('/{id}', [ClubCourseInfoController::class, 'show']);
    Route::put('/{id}', [ClubCourseInfoController::class, 'update']);
    Route::delete('/{id}', [ClubCourseInfoController::class, 'destroy']);
});

Route::prefix('club-course')->group(function () {
    Route::get('/', [ClubCourseController::class, 'index']);
    Route::post('/', [ClubCourseController::class, 'store']);
    Route::get('/{id}', [ClubCourseController::class, 'show']);
    Route::put('/{id}', [ClubCourseController::class, 'update']);
    Route::delete('/{id}', [ClubCourseController::class, 'destroy']);
});

########## Types ##########

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('/{id}', [RoleController::class, 'show']);
    Route::put('/{id}', [RoleController::class, 'update']);
    Route::delete('/{id}', [RoleController::class, 'destroy']);
});

Route::prefix('notice-types')->group(function () {
    Route::get('/', [NoticeTypeController::class, 'index']);
    Route::post('/', [NoticeTypeController::class, 'store']);
    Route::get('/{id}', [NoticeTypeController::class, 'show']);
    Route::put('/{id}', [NoticeTypeController::class, 'update']);
    Route::delete('/{id}', [NoticeTypeController::class, 'destroy']);
});

Route::prefix('level-types')->group(function () {
    Route::get('/', [LevelTypeController::class, 'index']);
    Route::post('/', [LevelTypeController::class, 'store']);
    Route::get('/{id}', [LevelTypeController::class, 'show']);
    Route::put('/{id}', [LevelTypeController::class, 'update']);
    Route::delete('/{id}', [LevelTypeController::class, 'destroy']);
});

Route::prefix('lang-types')->group(function () {
    Route::get('/', [LangTypeController::class, 'index']);
    Route::post('/', [LangTypeController::class, 'store']);
    Route::get('/{id}', [LangTypeController::class, 'show']);
    Route::put('/{id}', [LangTypeController::class, 'update']);
    Route::delete('/{id}', [LangTypeController::class, 'destroy']);
});

Route::prefix('tech-method-types')->group(function () {
    Route::get('/', [TechMethodTypeController::class, 'index']);
    Route::post('/', [TechMethodTypeController::class, 'store']);
    Route::get('/{id}', [TechMethodTypeController::class, 'show']);
    Route::put('/{id}', [TechMethodTypeController::class, 'update']);
    Route::delete('/{id}', [TechMethodTypeController::class, 'destroy']);
});

########## Search API ##########

Route::prefix('search')->group(function () {
    // 通用搜尋
    Route::get('/', [SearchController::class, 'search']);
    
    // 全域快速搜尋
    Route::get('/global', [SearchController::class, 'globalSearch']);
    
    // 搜尋建議
    Route::get('/suggestions', [SearchController::class, 'suggestions']);
    
    // 取得可用篩選器
    Route::get('/filters', [SearchController::class, 'getAvailableFilters']);
    
    // 特定類型搜尋
    Route::get('/members', [SearchController::class, 'searchMembers']);
    Route::get('/products', [SearchController::class, 'searchProducts']);
    Route::get('/courses', [SearchController::class, 'searchCourses']);
});

########## Comment API ##########

Route::prefix('comments')->group(function () {
    // 獲取評論列表
    Route::get('/', [CommentController::class, 'index']);
    
    // 創建評論
    Route::post('/', [CommentController::class, 'store']);
    
    // 搜尋評論
    Route::get('/search', [CommentController::class, 'search']);
    
    // 獲取熱門評論
    Route::get('/trending', [CommentController::class, 'trending']);
    
    // 獲取評論統計
    Route::get('/statistics', [CommentController::class, 'statistics']);
    
    // 單個評論操作
    Route::prefix('{comment}')->group(function () {
        Route::get('/', [CommentController::class, 'show']);
        Route::put('/', [CommentController::class, 'update']);
        Route::delete('/', [CommentController::class, 'destroy']);
        
        // 互動功能
        Route::post('/like', [CommentController::class, 'toggleLike']);
        Route::post('/reaction', [CommentController::class, 'addReaction']);
        Route::post('/report', [CommentController::class, 'report']);
    });
});