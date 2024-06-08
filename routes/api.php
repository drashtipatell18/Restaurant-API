<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FamilyController;
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

// Roles Routes
// Route::get("/roles", [RoleController::class, 'getRole']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'getRole']);

    // Register Routes

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/create-user', [UserController::class, 'storeUser']);
    Route::post('/update-user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/delete-user/{id}', [UserController::class, 'destroyUser']);
    Route::get('/search-user', [UserController::class, 'search']);

    // Family Route
    Route::post('family/create', [FamilyController::class, 'createFamily']);

});



// User Routes
Route::post('/auth/login', [AuthController::class, 'login']);