<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Str;

class HoldAllAddController extends Controller
{
    private $stopHoldUrl = 'http://172.22.242.21:18000/REST/WIIRSTH/';

        public function index() {
            return view('main');
        }
        
        //header
        private function commonHeader() {
            return [
                "MessageFormat" => "",
                "EmployeeId" => "WI000001",
                "LanguageCd" => "EN",
                "ApplCode" => "TS",
                "FuncSecCode" => "I",
                "SourceCode" => "",
                "EffectiveDate" => now()->format('Ymd'),
                "TransDate" => now()->format('Ymd'),
                "TransTime" => now()->format('His') . '01',
                "TransSeq" => Str::random(8),
                "SuperOverride" => "",
                "TellerOverride" => "",
                "ReportLevels" => "",
                "PhysicalLocation" => "",
                "Rebid" => "N",
                "Reentry" => "N",
                "Correction" => "N",
                "Training" => "N"
            ];
        }
//Stop Hold
        private function stopHoldRqstHdr() {
            return [
                "MsgUuid" => Str::uuid(),
                "SrcId" => "POC",
                "LclPref" => "EN",
                "ServVer" => "1.0",
                "UsrId" => "POCUSER"
            ];
        }


public function holdAllAdd(Request $request)
{
    
/** @var \App\Http\Controllers\ErrorController $errCtrl */
$errCtrl = app(\App\Http\Controllers\ErrorController::class);

$emptyMsg = $errCtrl->missingFieldsMessage([
    'Ctl2'   => $request->input('Ctl2'),
    'Ctl3'   => $request->input('Ctl3'),
    'Ctl4'   => $request->input('Ctl4'),
    'AcctId' => $request->input('AcctId'),
]);

if ($emptyMsg) {
    $bag = new \Illuminate\Support\ViewErrorBag();
    $bag->put('default', new \Illuminate\Support\MessageBag([$emptyMsg]));

    return view('loan-details', [
        'details'     => [],
        'delinquency' => [],
    ])->with('errors', $bag); // ✅ No redirect, just render view
}

    $validated = $request->validate([
        'Ctl2'          => ['nullable', 'string', 'max:10'],
        'Ctl3'          => ['nullable', 'string', 'max:10'],
        'Ctl4'          => ['nullable', 'string', 'max:10'],
        'AcctId'        => ['required', 'string', 'max:32'],
        'ExpirationDays'=> ['nullable', 'integer', 'min:1', 'max:999'],
        'StopHoldDesc'  => ['nullable', 'string', 'max:200'],
        'UniversalDesc' => ['nullable', 'string', 'max:1024'],
        'InitiatedBy'   => ['nullable', 'string', 'max:32'],
        'Branch'        => ['nullable', 'string', 'max:32'],
    ]);


    $payload = [
        "WIIRSTHOperation" => [
            "RqstHdr" => $this->stopHoldRqstHdr(),
            "TSRqHdr" => $this->commonHeader(),
            "Ctl1" => "0008",
            "Ctl2" => $request->input('Ctl2'),
            "Ctl3" => $request->input('Ctl3'),
            "Ctl4" => $request->input('Ctl4'),
            "AcctId" => $request->input('AcctId'),
            "RecsRequested" => "0000",
            "StopInd" => "",
            "HoldInd" => "",
            "HoldAllInd" => "",
            "SpecialInstructionsInd" => "",
            "SuspectInd" => "",
            "StopRangeInd" => "",
            "SuspectRangeInd" => "",
            "StartCheckNum" => "",
            "EndCheckNum" => "",
            "StartDt" => "",
            "EndDt" => "",
            "LowAmt" => "",
            "HighAmt" => "",
            "LowSeq" => "",
            "HighSeq" => "",
            "TranCd" => "34",
            "StopHoldAmt" => "",
            "ExpirationDt" => "",
            "ExpirationDays" => $request->input('ExpirationDays', '15'),
            "IssueDt"        => now()->format('Ymd'),
            "InitiatedBy" => $request->input('InitiatedBy', ''),
            "StopHoldType" => "BAL",
            "StopHoldDesc" => $request->input('StopHoldDesc', 'PAYEE UDTdescription'),
            "AllFundsInd" => "Y",
            "ForceBalNegative" => "",
            "WaiveFeeInd" => "",
            "UniversalDesc" => $request->input('UniversalDesc', ''),
            "StopHoldSeq" => ""
        ]
    ];

    try {
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->retry(2, 200)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->stopHoldUrl . '?ActionCD=AddHoldALL', $payload);

        if (!$response->successful()) {
            return back()->withErrors([
                'api' => "Hold amount add failed (HTTP {$response->status()})."
            ])->withInput();
        }

        $data  = $response->json();
        $opRes = $data['WIIRSTHOperationResponse'] ?? [];
        $tsHdr = $opRes['TSRsHdr'] ?? [];

        $details = [
            'Severity'       => $tsHdr['MaxSeverity']    ?? 'N/A',
            'Process Message'=> trim($tsHdr['ProcessMessage'] ?? 'N/A'),
            'Next Day'       => $tsHdr['NextDay']        ?? 'N/A',
        ];

        $messages = [];
        foreach (($tsHdr['TrnStatus'] ?? []) as $msg) {
            $messages[] = [
                'Code'     => $msg['MsgCode']     ?? '—',
                'Severity' => $msg['MsgSeverity'] ?? '—',
                'Text'     => $msg['MsgText']     ?? '—',
                'Account'  => $msg['MsgAcct']     ?? '—',
                'Program'  => $msg['MsgPgm']      ?? '—',
            ];
        }


        if (!empty($tsHdr['ProcessMessage'])) {
            $lastmessages = [end($messages)];
        }
        else {
         $lastmessages = [reset($messages)];   // If not complete, show all messages
        }

        return view('stop-hold-all-add', [
            'details'  => $details,
            'messages' => $lastmessages,
            'raw'      => $data,
        ]);
    } catch (\Throwable $e) {
        \Log::error('Hold Amount Add exception', ['message' => $e->getMessage()]);
        return back()->withErrors([
            'api' => 'Unexpected error while adding hold amount.'
        ])->withInput();
    }
}
}
