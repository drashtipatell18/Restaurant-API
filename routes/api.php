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
use App\Http\Controllers\BoxController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GroupController;
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
Route::post('/create-user', [UserController::class, 'storeUser']);
Route::middleware('auth:sanctum')->group(function () {

    // Dasboard
    Route::post('/dashboard', [UserController::class, 'dashboard']);

    // Roles Routes
    Route::get('/roles', [RoleController::class, 'getRole']);

    // Register Routes
    Route::get('/users', [UserController::class, 'index']);

    Route::post('/update-user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/delete-user/{id}', [UserController::class, 'destroyUser']);
    Route::get('/get-user/{id}', [UserController::class, 'getUser']);
    Route::post('/search-user', [UserController::class, 'Rolesearch']);
    Route::post('/search-user-month', [UserController::class, 'Monthsearch']);
    Route::get('/get-users', [UserController::class, 'index']);
    Route::get("/user/{id}/getOrders", [UserController::class, 'getOrders']);

    Route::get('/getCasherUser', [UserController::class, 'getCasherUser']);

     // Menu Routes
    Route::post('/menu/create', [MenuController::class, 'createMenu']);
    Route::post('/menu/update/{id}', [MenuController::class, 'updateMenu']);
    Route::delete('/menu/delete/{id}', [MenuController::class, 'deleteMenu']);


    Route::delete('/menu/{menuId}/item/{itemId}', [MenuController::class, 'deleteItem'])->name('menu.item.remove');

    // Wallet Routes
    Route::post('/wallet/create', [WalletController::class, 'createWallet']);
    Route::post('/wallet/update/{id}', [WalletController::class, 'updateWallet']);
    Route::delete('/wallet/delete/{id}', [WalletController::class, 'deleteWallet']);

    // WalletLog Routes
    Route::post('/wallet-log/create', [WalletLogController::class, 'createWalletLog']);
    Route::post('/wallet-log/update/{id}', [WalletLogController::class, 'updateWalletLog']);
    Route::delete('/wallet-log/delete/{id}', [WalletLogController::class, 'deleteWalletLog']);
    Route::get("/wallet/getUserLog/{id}", [WalletLogController::class, 'getWalletLog']);

    // Boxs Routes
    Route::post('/box/create', [BoxController::class, 'createBox']);
    Route::post('/box/update/{id}', [BoxController::class, 'updateBox']);
    Route::delete('/box/delete/{id}', [BoxController::class, 'deleteBox']);
    Route::post('/box-serach', [BoxController::class, 'Boxsearch']);
    Route::post('/box/statusChange', [BoxController::class, 'BoxStatusChange']);
    Route::get('/get-boxs', [BoxController::class, 'index']);
    Route::get('/get-all-boxs-log', [BoxController::class, 'getAllBoxsLog']);
    Route::get('/get-boxlogs-all/{id}', [BoxController::class, 'getAllBox']);
    Route::get('/get-boxlogs-all/{id}', [BoxController::class, 'getAllBox']);
    Route::get('/get-boxlogs/{id}', [BoxController::class, 'GetAllBoxLog']);
    Route::get('/box/orderReport/{id}', [BoxController::class, 'BoxReportMonthWise']);

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
    Route::post('/production-centers/getProducts', [ProductionCenterController::class, 'getProducts']);


    // Sector and Table
    Route::post('/sector/create', [SectorController::class, 'createSector']);
    Route::post('/sector/update/{id}', [SectorController::class, 'updateSector']);
    Route::delete('/sector/delete/{id}', [SectorController::class, 'deleteSector']);
    Route::post('/sector/addTables', [SectorController::class, 'addTables']);
    Route::post('/table/updateStatus', [SectorController::class, 'updateTableStatus']);
    Route::get('/table/getStats/{id}', [SectorController::class, 'getTableStats']);
    Route::get('/kds/{table_id}', [SectorController::class, 'getKds']);

    // Items
    Route::post('/item/create', [ItemController::class, 'createItem']);
    Route::post('/item/update/{id}', [ItemController::class, 'updateItem']);
    Route::delete('/item/delete/{id}', [ItemController::class, 'deleteItem']);
    Route::post("/item/addToMenu", [ItemController::class, 'addToMenu']);
    Route::get("/item/getSaleReport/{id}", [ItemController::class, 'getSaleReport']);



    // Orders
    Route::post('/order/place_new', [OrderController::class, 'placeOrder']);
    Route::post('/order/addItem', [OrderController::class, 'addItem']);
    Route::post('/order/updateItem/{id}', [OrderController::class, 'UpdateItem']);
    Route::get('/order/getAll', [OrderController::class, 'getAll']);
    Route::delete('/order/delete/{id}', [OrderController::class, 'deleteOrder']);
    Route::delete('/order/deleteSingle/{id}', [OrderController::class, 'deleteSingle']);
    Route::get('/order/getSingle/{id}', [OrderController::class, 'getSingle']);
    Route::post('/order/updateStatus', [OrderController::class, 'updateOrderStatus']);
    Route::get('/order/addTip/{id}', [OrderController::class, 'addTip']);
    Route::post('/order/addNote/{id}', [OrderController::class, 'addNote']);
    Route::get('/order/getLog/{id}', [OrderController::class,'getOrderLog']);
    Route::post('/order/updateorderreason/{id}', [OrderController::class,'UpdateOrderReason']);

    Route::get('/orders/last', [OrderController::class, 'getLastOrder']);
    Route::get('/orders/last', [OrderController::class, 'getLastOrder']);

    // Group
    Route::post('/group/create', [GroupController::class,'create']);
    Route::post('/group/update/{id}', [GroupController::class,'update']);
    Route::delete('/group/delete/{id}', [GroupController::class,'delete']);
    Route::post('/group/addUser', [GroupController::class,'addUser']);
    Route::post('/group/deleteUser', [GroupController::class,'deleteUser']);
    Route::get('/group/getMyGroups', [GroupController::class, 'getMyGroups']);
    Route::get('/group/getAllGroups', [GroupController::class, 'getAllGroups']);
    Route::get('/group/getChats/{id}', [GroupController::class, 'getGroupChats']);

    // Chat
    Route::post('/chat/addToGroup', [ChatController::class, 'addGroupChat']);
    Route::post('/chat/sendMessage',[ChatController::class, 'makeNewChat']);
    Route::get('/chat/getChatUsers',[ChatController::class, 'getAllChats']);
    Route::get('/chat/getSpecificUserChat/{id}',[ChatController::class, 'getSpecificUserChat']);

    //Payment
    Route::get('/get-payments', [PaymentController::class, 'GetPayment']);
    Route::get('/getsinglepayments/{order_master_id}', [PaymentController::class, 'getsinglePayments']);
    Route::post('/payment/insert',[PaymentController::class, 'InsertPayment']);
});


    // User Routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/invite', [AuthController::class, 'invite']);
    Route::post('set-password/{id}', [AuthController::class, 'setPassword']);
    Route::get('/family/getFamily', [FamilyController::class, 'getFamily']);
    Route::get('/subfamily/getSubFamily', [FamilyController::class, 'getSubFamily']);
    Route::post('/subfamily/getMultipleSubFamily', [FamilyController::class, 'getMultipleSubFamily']);
    Route::get('/sector/getAll', [SectorController::class, 'getSector']);
    Route::post('/sector/getWithTable', [SectorController::class, 'getSectionWithTable']);
    Route::get('/sector/by-table/{tableId}', [SectorController::class, 'getSectorByTableId']);
    Route::post('/menu/get', [MenuController::class, 'getMenu']);
    Route::get('/item/getSingle/{id}', [ItemController::class, 'getSingleItem']);
    Route::get('/item/getAll', [ItemController::class, 'getAll']);
    Route::post('/item/getSubFamilyWiseItem', [ItemController::class, 'getSubFamilyWiseItem']);
