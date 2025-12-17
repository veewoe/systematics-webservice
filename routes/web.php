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
use App\Http\Controllers\HoldAmountAddController;
use App\Http\Controllers\LoanInquiryController;
use App\Http\Controllers\StopHoldInqController;
use App\Http\Controllers\StopHoldDeleteController;
use App\Http\Controllers\HoldAllAddController;
use App\Http\Controllers\RmabInquiryController;

use App\Http\Controllers\PartyRelController;


Route::get('/', [ApiController::class, 'index']);

// ALS Loan Inquiry
Route::post('/loans-inq', [LoanInquiryController::class, 'loansInquiry']);

// IM Stop Hold

Route::get('/stop-hold/inq', [StopHoldInqController::class, 'show'])
    ->name('stopHold.inq');

// Optional: keep your POST for submitting the inquiry form
Route::post('/stop-hold-inq', [StopHoldInqController::class, 'stopHoldInquiry'])
->name('stopHold.inquiry');


Route::post('/stop-hold', [StopHoldDeleteController::class, 'deleteStopHold'])
    ->name('stopHold.delete');


Route::post('/hold-amount-add', [HoldAmountAddController::class, 'holdAmountAdd']);

// Route::post('/stop-hold-all-add', [HoldAllAddController::class, 'holdAllAdd']);
Route::post('/hold-all-add', [HoldAllAddController::class, 'holdAllAdd']);


Route::post('/stop-hold/delete', [StopHoldDeleteController::class, 'deleteStopHold'])
     ->name('stophold.delete');
Route::post('/rmab/inquiry', [RmabInquiryController::class, 'inquiry']);
 


Route::post('/party-rel/store', [PartyRelController::class, 'store'])->name('party-rel.store');