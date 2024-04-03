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

// user registration protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticationController::class, 'logout']);
    Route::get('/user', [AuthenticationController::class, 'getUser']);

    // company routes
    Route::middleware([UserType::class . ':SA'])->group(function(){
        Route::post('/companies/create',[CompanyController::class, 'store']);
        Route::post('/companies/{id}',[CompanyController::class, 'update']);
        Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
        Route::get('/companies',[CompanyController::class, 'index']);
        Route::get('/companies/{id}',[CompanyController::class, 'show']);
    });

    // job description
    Route::middleware([UserType::class . ':SA,CA'])->group(function(){
        Route::post('job/create',[JobDescriptionController::class, 'store']);
        Route::get('jobs',[JobDescriptionController::class, 'index']);
        Route::get('job/{id}',[JobDescriptionController::class, 'show']);
        Route::put('job/{id}',[JobDescriptionController::class, 'update']);
        Route::delete('job/{id}',[JobDescriptionController::class, 'destroy']);
    });
});






