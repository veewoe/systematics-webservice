
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

