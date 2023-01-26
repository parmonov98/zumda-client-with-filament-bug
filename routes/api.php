<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Bot\BaseRouter;
use App\Http\Controllers\Bot\TelegramController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('auth')->group(function (){
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function (){
    // auth required endpoints

    Route::get('orders', [OrderController::class, 'index'])->name('list');
    Route::get('get_available_orders', [OrderController::class, 'get_available_orders'])->name('list.available_ones');
});

Route::post('/tokens/create', function (Request $request) {

    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
})->name("tokens.create");

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::fallback(function (){
    dd('fallback');
});
