<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



use App\Http\Controllers\ApiController;



Route::get('/', [ApiController::class, 'index']);

// ALS Loan Inquiry
Route::post('/loans-inq', [ApiController::class, 'loansInquiry']);

// IM Stop Hold
Route::post('/stop-hold-inq', [ApiController::class, 'stopHoldInquiry']);
Route::post('/hold-amount-add', [ApiController::class, 'holdAmountAdd']);

Route::post('/hold-delete', [ApiController::class, 'holdDelete']);
Route::post('/stop-hold-all-add', [ApiController::class, 'holdAllAdd']);




