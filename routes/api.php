<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ModelController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
})->name('api.user');

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('/models', ModelController::class);
    Route::post('/import', ImportController::class);
    Route::get('/export', [ExportController::class, 'index'])->name('api.index-export');
    Route::get('/export/create', [ExportController::class, 'create'])->name('api.create-export');
});
Route::get('/download', DownloadController::class)
    ->name('api.download')->middleware('signed');
