<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\ProductionCenterController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletLogController;
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
    Route::get('/get-user/{id}', [UserController::class, 'getUser']);
    Route::post('/search-user', [UserController::class, 'Rolesearch']);
    Route::post('/search-user-month', [UserController::class, 'Monthsearch']);

     // Menu Routes

    Route::post('/menu/create', [MenuController::class, 'createMenu']);
    Route::post('/menu/update/{id}', [MenuController::class, 'updateMenu']);
    Route::delete('/menu/delete/{id}', [MenuController::class, 'deleteMenu']);

    // Wallet Routes

    Route::post('/wallet/create', [WalletController::class, 'createWallet']);
    Route::post('/wallet/update/{id}', [WalletController::class, 'updateWallet']);
    Route::delete('/wallet/delete/{id}', [WalletController::class, 'deleteWallet']);

    // WalletLog Routes

    Route::post('/wallet-log/create', [WalletLogController::class, 'createWalletLog']);
    Route::post('/wallet-log/update/{id}', [WalletLogController::class, 'updateWalletLog']);
    Route::delete('/wallet-log/delete/{id}', [WalletLogController::class, 'deleteWalletLog']);


    // Family Route
    Route::post('/family/create', [FamilyController::class, 'createFamily']);
    Route::post('/family/update/{id}', [FamilyController::class, 'updateFamily']);
    Route::delete("/family/delete/{id}", [FamilyController::class, 'deleteFamily']);

    // Sub Family
    Route::post('/subfamily/create', [FamilyController::class, 'createSubFamily']);
    Route::post('/subfamily/update/{id}', [FamilyController::class, 'updateSubFamily']);
    Route::delete('/subfamily/delete/{id}', [FamilyController::class, 'deleteSubFamily']);


     // Production Center
     Route::get('/production-centers', [ProductionCenterController::class, 'viewProductionCenter']);
     Route::post('/create/production-centers', [ProductionCenterController::class, 'storeProductionCenter']);
     Route::post('/update/production-centers/{id}', [ProductionCenterController::class, 'updateProductionCenter']);
     Route::get('/delete/production-centers/{id}', [ProductionCenterController::class, 'destroyProductionCenter']);
     Route::post('/search/production-centers', [ProductionCenterController::class, 'ProductionCentersearch']);


    // Sector
    Route::post('/sector/create', [SectorController::class, 'createSector']);
    Route::delete('/sector/delete/{id}', [SectorController::class, 'deleteSector']);
    Route::post('/sector/addTables', [SectorController::class, 'addTables']);
    Route::post('/table/updateStatus', [SectorController::class, 'updateTableStatus']);

    // Items
    Route::post('/item/create', [ItemController::class, 'createItem']);
    Route::post('/item/update/{id}', [ItemController::class, 'updateItem']);
    Route::delete('/item/delete/{id}', [ItemController::class, 'deleteItem']);
    Route::post("/item/addToMenu", [ItemController::class, 'addToMenu']);

    // Orders
    Route::post('/order/place_new', [OrderController::class, 'placeOrder']);
    Route::get('/order/getAll', [OrderController::class, 'getAll']);
});


// User Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/family/getFamily', [FamilyController::class, 'getFamily']);
Route::get('/subfamily/getSubFamily', [FamilyController::class, 'getSubFamily']);
Route::post('/subfamily/getMultipleSubFamily', [FamilyController::class, 'getMultipleSubFamily']);
Route::get('/sector/getAll', [SectorController::class, 'getSector']);
Route::post('/sector/getWithTable', [SectorController::class, 'getSectionWithTable']);
Route::post('/menu/get', [MenuController::class, 'getMenu']);
Route::get('/item/getSingle/{id}', [ItemController::class, 'getSingleItem']);
Route::get('/item/getAll', [ItemController::class, 'getAll']);
Route::post('/item/getSubFamilyWiseItem', [ItemController::class, 'getSubFamilyWiseItem']);