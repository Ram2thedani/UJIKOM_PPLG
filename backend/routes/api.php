<?php

use App\Http\Controllers\Api\AdminClassController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminExaminerController;
use App\Http\Controllers\Api\AdminRubricController;
use App\Http\Controllers\Api\AdminStudentController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\RubricController;
use App\Http\Controllers\Api\StudentDashboardController;
use Illuminate\Support\Facades\Route;

// Public Auth & Asset Routes
Route::get('/logo', function () {
    $candidates = [
        public_path('logo-marhas.webp'),
        base_path('../frontend/public/logo-marhas.webp'),
        'C:/Users/DICOMMA !!/.gemini/antigravity/brain/135686b4-ad4e-429a-93e4-0a9da6d84751/.user_uploaded/media_1794968305429_474e5eb4.webp',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return response()->file($path, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Role Penguji & Admin
    Route::middleware('role:penguji,admin')->group(function () {
        Route::get('/classes', [QueueController::class, 'classes']);
        Route::get('/classes/{id}/queue', [QueueController::class, 'classQueue']);
        Route::get('/students/scan/{qr_token}', [QueueController::class, 'scanQr']);
        Route::get('/rubrics', [RubricController::class, 'index']);
        Route::post('/assessments/verify-pin', [AssessmentController::class, 'verifyPin']);
        Route::post('/assessments', [AssessmentController::class, 'store']);
        Route::get('/assessments/{student_id}', [AssessmentController::class, 'show']);
    });

    // Role Siswa
    Route::middleware('role:siswa')->group(function () {
        Route::get('/student/dashboard', [StudentDashboardController::class, 'index']);
    });

    // Role Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/export/{class_id}', [AdminDashboardController::class, 'export']);

        Route::post('/students/import', [AdminStudentController::class, 'import']);
        Route::apiResource('students', AdminStudentController::class)->except(['show']);

        Route::apiResource('examiners', AdminExaminerController::class)->except(['show']);
        Route::apiResource('classes', AdminClassController::class)->except(['show']);

        Route::put('/rubrics/bulk', [AdminRubricController::class, 'bulkUpdate']);
        Route::apiResource('rubrics', AdminRubricController::class)->except(['show']);
    });
});
