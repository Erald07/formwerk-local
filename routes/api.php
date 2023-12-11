<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FormsController;

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

Route::middleware(['apiToken', 'api'])->group(function () {
    Route::get('/forms', [FormsController::class, 'list']);
    Route::get('/forms-with-folders', [FormsController::class, 'formsWithFolders']);
    Route::get('/forms/{id}', [FormsController::class, 'getForm']);
});
