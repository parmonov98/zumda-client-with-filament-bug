<?php

use App\Http\Controllers\Bot\TelegramController;
use Illuminate\Support\Facades\Route;

Route::any('/' . config('bot.token'), [TelegramController::class, 'index'])->name('webhook');


Route::get('/set', [TelegramController::class, 'setWebHook'])->name('setWebHook');
Route::get('/get', [TelegramController::class, 'getWebHook'])->name('getWebHook');


Route::get('/error', function () {
    abort(200, "505 error occured!");
});
Route::get('/clear', [TelegramController::class, 'setClearWebHook'])->name('setClearWebHook');


Route::fallback(function (){
    dd('fallback bot');
});
