<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AccountController extends Controller
{
    

public function fetchAccount(Request $request)
{
    $response = Http::get('http://172.22.242.21:18000/REST/WILRACT/', [
        'Ctl2' => $request->Ctl2,
        'Ctl3' => $request->Ctl3,
        'Ctl4' => $request->Ctl4,
        'Account' => $request->Account,
    ]);

    $accountInfo = $response->json();
    dd($accountInfo);
    return view('account', compact('accountInfo'));
}

}
