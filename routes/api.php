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
Route::post('register', [AuthenticationController::class, 'registerUser']);

// route for login
Route::post('login', [AuthenticationController::class, 'loginUser']);

// route for password reset
Route::post('password/set', [AuthenticationController::class, 'setPassword']);

// route for getting the company detials 
Route::get('companyinfo', [CompanyController::class, 'companyWithLogo']);

// route for getting all jobs detials
Route::get("jobsInfo", [JobDescriptionController::class, "AllJobsInfo"]);

// route for getting jobs Status
Route::get("jobsStatus", [JobApplicationController::class, "JobsStatus"]);

// protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::controller(AuthenticationController::class)->group(function () {

        Route::get('user', 'getUser');
        Route::post('logout', 'logout');

    });

    Route::get('statistics', [StatisticsController::class, 'getStatistics']);

    // route with prefix of companies and crud (CREATE READ UPDATE DELETE) of companies
    Route::middleware('checkUserType:SA')->prefix('/companies')->group(function () {

        Route::controller(CompanyController::class)->group(function () {

            Route::get('', 'index');
            Route::get('show/{id}', 'show');
            Route::post('create', 'store');
            Route::post('update/{id}', 'update');
            Route::post('delete/{id}', 'destroy');

        });

    });

    // employee routes for CRUD (CREATE READ UPDATE DELETE) operations
    Route::middleware('checkUserType:SA,CA')->group(function () {

        Route::get('getallcompanies', [CompanyController::class, 'getAllCompanies']);

        Route::controller(EmployeeController::class)->group(function () {

            Route::get('employees', 'index');

            Route::prefix('employee')->group(function () {

                Route::get('show/{id}', 'show');
                Route::post('create', 'store');
                Route::post('update/{id}', 'update');
                Route::post('delete/{id}', 'destroy');

            });

        });

    });

    // job routes for CRUD (CREATE READ UPDATE DELETE) operations
    Route::middleware('checkUserType:SA,CA')->group(function () {

        Route::controller(JobDescriptionController::class)->group(function () {

            Route::get('jobs', 'index');

            Route::prefix('job')->group(function () {

                Route::get('show/{id}', 'show');
                Route::post('create', 'store');
                Route::post('update/{id}', 'update');
                Route::post('delete/{id}', 'destroy');

            });

        });

    });

    // Job application routes for CRUD (CREATE READ UPDATE DELETE) operations 
    Route::middleware('checkUserType:SA,CA')->group(function () {

        Route::controller(JobApplicationController::class)->group(function () {

            Route::prefix('allCandidateInfo')->group(function () {

                Route::get('', 'getAllDetails');
                Route::get('/{id}', 'show');
                Route::post('/update/{id}', 'update');
                Route::post('/delete/{id}', 'delete');

            });

        });

    });

    Route::post("/userJobDetails", [JobApplicationController::class, 'store']);
});
