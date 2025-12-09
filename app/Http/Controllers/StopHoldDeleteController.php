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
        'sequenceNo' => ['required', 'integer'],
        'cbr'        => ['nullable', 'string'],
        'cbi'        => ['nullable', 'string'],
        'cba'        => ['nullable', 'string'],
        'tab'        => ['nullable', 'string'],
    ]);

    $url = 'http://172.22.242.21:18000/REST/WIIRSTH/?ActionCD=D';

    try {
        $resp = Http::timeout(10)
            ->retry(2, 200)
            ->asForm()
            ->post($url, [
                'acctNo'     => $data['acctNo'],
                'sequenceNo' => $data['sequenceNo'],
            ]);

        if ($resp->failed()) {
            $msg = 'Delete hold failed.';
            $json = $resp->json();
            if (is_array($json)) {
                $hdr    = $json['WIIRSTHOperationResponse']['TSRsHdr'] ?? [];
                $status = $hdr['TrnStatus'][0] ?? [];
                $code   = $status['MsgCode'] ?? null;
                $text   = $status['MsgText'] ?? null;
                if ($code || $text) {
                    $msg .= ' ' . trim(($code ?? '') . ' ' . ($text ?? ''));
                }
            } else {
                $msg .= ' ' . $resp->body();
            }

            // Redirect back to inquiry route with error
            return redirect()
                ->route('stopHold.inquiry', [
                    'cbr'    => $data['cbr'] ?? null,
                    'cbi'    => $data['cbi'] ?? null,
                    'cba'    => $data['cba'] ?? null,
                    'acctNo' => $data['acctNo'],
                    'tab'    => $data['tab'] ?? 'stopHold',
                ])
                ->withErrors(['api' => $msg]);
        }

        // Success → redirect to inquiry route with flash status
        return redirect()
            ->route('stopHold.inquiry', [
                'cbr'    => $data['cbr'] ?? null,
                'cbi'    => $data['cbi'] ?? null,
                'cba'    => $data['cba'] ?? null,
                'acctNo' => $data['acctNo'],
                'tab'    => $data['tab'] ?? 'stopHold', // ensures we land on the right tab
            ])
            ->with('status', "Stop/Hold seq {$data['sequenceNo']} deleted.");

    } catch (\Throwable $e) {
        Log::error('StopHold delete exception', [
            'message' => $e->getMessage(),
            'acctNo'  => $data['acctNo'],
            'seq'     => $data['sequenceNo'],
        ]);

        return redirect()
            ->route('stopHold.inquiry', [
                'cbr'    => $data['cbr'] ?? null,
                'cbi'    => $data['cbi'] ?? null,
                'cba'    => $data['cba'] ?? null,
                'acctNo' => $data['acctNo'],
                'tab'    => $data['tab'] ?? 'stopHold',
            ])
            ->withErrors(['api' => 'Unexpected error while deleting hold.']);
    }
}

}
