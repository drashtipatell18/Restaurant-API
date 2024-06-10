<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\ProductionCenterController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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


Route::middleware('auth:sanctum')->group(function () {

    // Roles Routes

    Route::get('/roles', [RoleController::class, 'getRole']);

    // Register Routes

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/create-user', [UserController::class, 'storeUser']);
    Route::post('/update-user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/delete-user/{id}', [UserController::class, 'destroyUser']);
    Route::get('/search-user', [UserController::class, 'search']);

    // Family Route
    Route::post('/family/create', [FamilyController::class, 'createFamily']);
    Route::post('/family/update/{id}', [FamilyController::class, 'updateFamily']);
    Route::delete("/family/delete/{id}", [FamilyController::class, 'deleteFamily']);

    // Sub Family
    Route::post('/subfamily/create', [FamilyController::class, 'createSubFamily']);
    Route::post('/subfamily/update/{id}', [FamilyController::class, 'updateSubFamily']);
    Route::post('/subfamily/delete/{id}', [FamilyController::class, 'deleteSubFamily']);

     // Production Center
     Route::get('/production-centers', [ProductionCenterController::class, 'viewProductionCenter']);
     Route::post('/create/production-centers', [ProductionCenterController::class, 'storeProductionCenter']);
     Route::post('/update/production-centers/{id}', [ProductionCenterController::class, 'updateProductionCenter']);
     Route::get('/delete/production-centers/{id}', [ProductionCenterController::class, 'destroyProductionCenter']);
     Route::post('/search/production-centers', [ProductionCenterController::class, 'ProductionCentersearch']);

});


// User Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/family/getFamily', [FamilyController::class, 'getFamily']);
Route::get('/subfamily/getSubFamily', [FamilyController::class, 'getSubFamily']);
Route::post('/subfamily/getMultipleSubFamily', [FamilyController::class, 'getMultipleSubFamily']);