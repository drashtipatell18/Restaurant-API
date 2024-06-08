<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
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
});

// User Routes
Route::post('/auth/login', [AuthController::class, 'login']);