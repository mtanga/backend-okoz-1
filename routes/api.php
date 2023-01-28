<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ServiceController;


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

Route::get('roles', [UserController::class, 'roles']);
Route::post('register', [UserController::class, 'register']);
Route::post('emailexist', [UserController::class, 'emailexist']);

Route::post('resent', [UserController::class, 'resent']);
Route::post('login', [UserController::class, 'login']);
Route::post('reset', [UserController::class, 'reset']);
Route::post('newsletter', [UserController::class, 'newsletter']);

Route::post('categories', [ServiceController::class, 'categories']);


Route::get('sms', [UserController::class, 'sms']); 


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();

});

Route::middleware('auth:sanctum')->group( function () {
        Route::post('editpass', [UserController::class, 'editpass']);
        Route::post('photo', [UserController::class, 'photo']);
        Route::post('editprofile', [UserController::class, 'editprofile']);
        
        //Afficher les utilisateurs
        Route::post('users', [UserController::class, 'users']);
});
