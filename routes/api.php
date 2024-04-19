<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UserType;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// user registration public routes

Route::post('/register', [AuthenticationController::class, 'createUser']);
Route::post('/login', [AuthenticationController::class, 'loginUser']);
Route::post('/password/reset', [AuthenticationController::class, 'resetPassword']);

// user registration protected routes
Route::middleware('auth:sanctum')->group(function () {
    // routes for logout , get SA and CA statistics and get user
    Route::post('/logout', [AuthenticationController::class, 'logout']);
    Route::get('/user', [AuthenticationController::class, 'getUser']);
    Route::get('/statistics', [StatisticsController::class, 'getStatistics']);

    // company routes for CRUD operations
    Route::middleware([UserType::class . ':SA'])->prefix('/companies')->group(function () {
        Route::post('/create', [CompanyController::class, 'store']);
        Route::post('/{id}', [CompanyController::class, 'update']);
        Route::post('/delete/{id}', [CompanyController::class, 'destroy']);
        Route::get('', [CompanyController::class, 'index']);
        Route::get('/{id}', [CompanyController::class, 'show']);
    });

    // job routes for CRUD operations
    Route::middleware([UserType::class . ':SA,CA'])->group(function () {
        Route::get('jobs', [JobDescriptionController::class, 'index']);
        Route::prefix('job')->group(function () {
            Route::post('/create', [JobDescriptionController::class, 'store']);
            Route::get('/{id}', [JobDescriptionController::class, 'show']);
            Route::post('/update/{id}', [JobDescriptionController::class, 'update']);
            Route::post('/delete/{id}', [JobDescriptionController::class, 'destroy']);
        });
    });

    // employee routes for CRUD operations
    Route::middleware([UserType::class . ':SA,CA'])->group(function () {
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::get('getallcompanies', [CompanyController::class, 'getAllCompanies']);
        Route::prefix('employee')->group(function () {
            Route::post('/create', [EmployeeController::class, 'store']);
            Route::get('/{id}', [EmployeeController::class, 'show']);
            Route::post('/update/{id}', [EmployeeController::class, 'update']);
            Route::post('/{id}', [EmployeeController::class, 'destroy']);
        });
    });
});
