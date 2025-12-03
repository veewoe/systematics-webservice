<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class TestController extends Controller {

    public function test() {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get('http://172.22.242.21:18000/REST/WILRACT/');

            $data = $response->json();
        } catch (\Throwable $th) {
            throw $th;
        }

        return $data;
    }
}