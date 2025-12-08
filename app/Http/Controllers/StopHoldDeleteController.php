<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StopHoldDeleteController extends Controller
{

    public function deleteHold(Request $req)
    {
        // Validate incoming payload
        $data = $req->validate([
            'acctNo'     => ['required', 'string'],
            'sequenceNo' => ['required', 'string'], // change to 'integer' if numeric
        ]);

        $url = 'http://172.22.242.21:18000/REST/WIIRSTH/?ActionCD=D';

        try {
            // Call upstream with form-encoded (adjust if JSON is required)
            $resp = Http::timeout(10)
                ->retry(2, 200)
                ->asForm()
                ->post($url, $data);

            if ($resp->failed()) {
                Log::warning('StopHold delete failed', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                    'acctNo' => $data['acctNo'],
                    'seq'    => $data['sequenceNo'],
                ]);

                $msg = 'Delete hold failed.';
                $json = $resp->json();
                if (is_array($json)) {
                    $hdr = $json['WIIRSTHOperationResponse']['TSRsHdr'] ?? [];
                    $status = $hdr['TrnStatus'][0] ?? [];
                    $code = $status['MsgCode'] ?? null;
                    $text = $status['MsgText'] ?? null;
                    if ($code || $text) {
                        $msg .= ' ' . trim(($code ?? '') . ' ' . ($text ?? ''));
                    }
                } else {
                  
                    $msg .= ' ' . $resp->body();
                }

                return back()->withErrors(['api' => $msg])->withInput();
            }

            return back()->with('status', 'Hold deleted successfully.');

        } catch (\Throwable $e) {
            Log::error('StopHold delete exception', [
                'message' => $e->getMessage(),
                'acctNo'  => $data['acctNo'],
                'seq'     => $data['sequenceNo'],
            ]);

            return back()->withErrors(['api' => 'Unexpected error while deleting hold.'])->withInput();
        }
    }
}
