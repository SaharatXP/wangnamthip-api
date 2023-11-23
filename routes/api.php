<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BranchsController;
use App\Http\Controllers\DealersController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\DB;


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



Route::get('/login', function () {
    abort(401);
})->name('login');
Route::post('login', function () {
    request()->validate([
        'username' => 'required|string',
        'password' => 'required|string',

    ]);
    $credentials = request()->only(['username', 'password']);
    if (!auth()->validate($credentials)) {
        return response()->json(['message' => 'ชื่อผู้ใช่งาน หรือ รหัสผ่านไม่ถูกต้อง'], 401);
    } else {
        $user = User::where('username', $credentials['username'])->first();
        $user->tokens()->delete();
        $token = $user->createToken($user->username, [$user->role]);
        $access_branch = DB::table('branchs')->select('branch_id', 'branch_name')->whereIn('branch_id', preg_split("/[,]/", $user->access_branch))->where('is_active', true)->get();
        $response = [
            'status' => 'OK',
            'token' => $token->plainTextToken,
            'role' => $user->role,
            'name' => $user->name,
            'access_branch' => $access_branch
        ];
        return response()->json($response);
    }
});


Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('/register', [AuthController::class, 'createUser']);

    //! Products
    Route::get('products', [ProductController::class, 'index']);
    Route::post('product/store', [ProductController::class, 'store']);
    Route::get('product/show', [ProductController::class, 'edit']);
    Route::put('product/update', [ProductController::class, 'update']);
    Route::delete('product/delete', [ProductController::class, 'delete']);

    //! Branchs
    Route::get('branchs', [BranchsController::class, 'index']);
    Route::post('branch/store', [BranchsController::class, 'store']);
    Route::get('branch/show', [BranchsController::class, 'edit']);
    Route::put('branch/update', [BranchsController::class, 'update']);
    Route::delete('branch/delete', [BranchsController::class, 'delete']);


    //! Dealers
    Route::get('dealers', [DealersController::class, 'index']);
    Route::post('dealer/store', [DealersController::class, 'store']);
    Route::get('dealer/show', [DealersController::class, 'edit']);
    Route::put('dealer/update', [DealersController::class, 'update']);
    Route::delete('dealer/delete', [DealersController::class, 'delete']);

    //! Orders
    Route::get('order/master/scan', [OrdersController::class, 'master_product_find_one']);
    Route::get('order/master/checkpd', [OrdersController::class, 'master_product_check_price']);
    Route::get('order/master/customer', [OrdersController::class, 'master_customer_find_one']);
    // Route::get('order/master/branch', [OrdersController::class, 'master_customer_find_one']);

    Route::get('orders', [OrdersController::class, 'index']);
    Route::post('order/store', [OrdersController::class, 'store']);

    //! Dashboard 
    Route::get('dashboard/today', [DashboardController::class, 'dashboard_today']);
    Route::get('dashboard/fromto', [DashboardController::class, 'dashboard_fdate_tdate']);
});
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
