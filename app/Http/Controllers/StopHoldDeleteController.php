<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StopHoldDeleteController extends Controller
{
    
public function stopHoldDelete(Request $request)
{
    $controlCount = 4; // adjust if you ever add more Ctl fields

    // Validate
    $ctlRules = [];
    for ($i = 1; $i <= $controlCount; $i++) {
        $max = ($i === 4) ? 10 : 4;
        $ctlRules["Ctl{$i}"] = ['nullable', 'string', "max:{$max}"];
    }
    $validated = $request->validate($ctlRules + [
        'AcctId'      => ['required', 'string', 'max:32'],
        'StopHoldSeq' => ['required', 'string', 'max:10'],
    ]);

    // Build Ctl payload
    $ctlPayload = [];
    for ($i = 1; $i <= $controlCount; $i++) {
        $k = "Ctl{$i}";
        $ctlPayload[$k] = $validated[$k] ?? '';
    }

    $hdr = array_merge($this->commonHeader(), [
        'MessageFormat'    => '',
        'EmployeeId'       => 'WI000001',
        'LanguageCd'       => 'EN',
        'ApplCode'         => 'TS',
        'FuncSecCode'      => 'I',
        'SourceCode'       => '',
        'EffectiveDate'    => \Carbon\Carbon::now()->format('Ymd'),
        'TransTime'        => \Carbon\Carbon::now()->format('His') . '01',
        'SuperOverride'    => '',
        'TellerOverride'   => '',
        'PhysicalLocation' => '',
        'Rebid'            => 'N',
        'Reentry'          => 'N',
        'Correction'       => 'N',
        'Training'         => 'N',
    ]);

    $payload = [
        'WIIRSTHOperation' => array_merge(
            ['TSRqHdr' => $hdr],
            $ctlPayload + [
                'AcctId'        => $validated['AcctId'],
                'RecsRequested' => '0000',
                'TranCd'        => '30',                      // per your sample
                'StopHoldSeq'   => $validated['StopHoldSeq'], // the sequence number
                // other fields empty unless required by host
                'UniversalDescLine' => '',
            ]
        ),
    ];

    try {
        $url = $this->stopHoldUrl . '?ActionCD=D';
        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->retry(2, 250)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if (!$response->successful()) {
            return back()->withErrors([
                'api' => "Hold Delete failed (HTTP {$response->status()})."
            ])->withInput();
        }

        $dataOut = $response->json();
        $tsHdr   = $dataOut['WIIRSTHOperationResponse']['TSRsHdr'] ?? [];
        $messages = $tsHdr['TrnStatus'] ?? [];

        // Flash, then you can optionally re-query to refresh the list
        return redirect()->back()->with([
            'success'  => trim(($tsHdr['ProcessMessage'] ?? 'PROCESS COMPLETE')),
            'messages' => $messages,
            'raw'      => $dataOut,
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Stop/Hold delete exception', ['message' => $e->getMessage()]);
        return back()->withErrors([
            'api' => 'Unexpected error while performing Hold Delete.'
        ])->withInput();
    }
}

}
