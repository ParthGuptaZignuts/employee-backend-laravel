<?php

namespace App\Http\Controllers;

use App\Models\JobDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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

// public routes

// route for registering
Route::post('/register', [AuthenticationController::class, 'createUser']);
// route for login
Route::post('/login', [AuthenticationController::class, 'loginUser']);
// route for password reset
Route::post('/password/reset', [AuthenticationController::class, 'resetPassword']);
// route for getting the company detials 
Route::get('/companyinfo', [CompanyController::class, 'companyWithLogo']);
// route for getting all jobs detials
Route::get("/jobsInfo", [JobDescriptionController::class, "AllJobsInfo"]);
// route for getting jobs Status
Route::get("/jobsStatus", [JobApplicationController::class, "JobsStatus"]);


// protected routes
Route::middleware('auth:sanctum')->group(function () {
    // routes for logout , get SA and CA statistics and get user
    Route::post('/logout', [AuthenticationController::class, 'logout']);
    Route::get('/user', [AuthenticationController::class, 'getUser']);
    Route::get('/statistics', [StatisticsController::class, 'getStatistics']);

    // route with prefix of companies and crud (CREATE READ UPDATE DELETE) of companies
    Route::middleware('checkUserType:SA')->prefix('/companies')->group(function () {
        Route::post('/create', [CompanyController::class, 'store']);
        Route::post('/{id}', [CompanyController::class, 'update']);
        Route::post('/delete/{id}', [CompanyController::class, 'destroy']);
        Route::get('', [CompanyController::class, 'index']);
        Route::get('/{id}', [CompanyController::class, 'show']);
    });

    // job routes for CRUD (CREATE READ UPDATE DELETE) operations
    Route::middleware('checkUserType:SA,CA')->group(function () {
        Route::get('jobs', [JobDescriptionController::class, 'index']);
        Route::prefix('job')->group(function () {
            Route::post('/create', [JobDescriptionController::class, 'store']);
            Route::get('/{id}', [JobDescriptionController::class, 'show']);
            Route::post('/update/{id}', [JobDescriptionController::class, 'update']);
            Route::post('/delete/{id}', [JobDescriptionController::class, 'destroy']);
        });
    });

    // employee routes for CRUD (CREATE READ UPDATE DELETE) operations
    Route::middleware('checkUserType:SA,CA')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::get('getallcompanies', [CompanyController::class, 'getAllCompanies']);
        Route::prefix('employee')->group(function () {
            Route::post('/create', [EmployeeController::class, 'store']);
            Route::get('/{id}', [EmployeeController::class, 'show']);
            Route::post('/update/{id}', [EmployeeController::class, 'update']);
            Route::post('/{id}', [EmployeeController::class, 'destroy']);
        });
    });

    // Job application routes for CRUD (CREATE READ UPDATE DELETE) operations 
    Route::middleware('checkUserType:SA,CA')->group(function () {
        Route::prefix('allCandidateInfo')->group(function () {
            Route::get('', [JobApplicationController::class, 'getAllDetails']);
            Route::get('/{id}', [JobApplicationController::class, 'show']);
            Route::post('/update/{id}', [JobApplicationController::class, 'update']);
            Route::post('/delete/{id}', [JobApplicationController::class, 'delete']);
        });
    });

    Route::post("/userJobDetails", [JobApplicationController::class, 'store']);
});
