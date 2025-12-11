<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HoldAmountAddController extends Controller
{
    private $stopHoldUrl = 'http://172.22.242.21:18000/REST/WIIRSTH/?ActionCD=ADD ';
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

    public function holdAmountAdd(Request $request)
{

    
/** @var \App\Http\Controllers\ErrorController $errCtrl */
$errCtrl = app(\App\Http\Controllers\ErrorController::class);

$emptyMsg = $errCtrl->missingFieldsMessage([
    'Ctl2'   => $request->input('Ctl2'),
    'Ctl3'   => $request->input('Ctl3'),
    'Ctl4'   => $request->input('Ctl4'),
    'AcctId' => $request->input('AcctId'),
    'StopHoldAmt' => $request->input('StopHoldAmt'),
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
        'StopHoldAmt' => ['required', 'regex:/^\d+(\.\d{1,17})?$/'],
        'ExpirationDays'=> ['nullable', 'integer', 'min:1', 'max:999'],
        'StopHoldDesc'  => ['nullable', 'string', 'max:200'],
        'UniversalDesc' => ['nullable', 'string', 'max:1024'],
        'InitiatedBy'   => ['nullable', 'string', 'max:32'],
        'Branch'        => ['nullable', 'string', 'max:32'],
    ]);

    $rawAmt = $validated['StopHoldAmt'];
    $minorUnits = (int) round(((float) $rawAmt));
    $paddedAmt = str_pad((string) $minorUnits, 17, '0', STR_PAD_LEFT);

    $payload = [
        "WIIRSTHOperation" => [
            "TSRqHdr"         => $this->commonHeader(),
            "Ctl1"            => "0008",
            "Ctl2"            => $validated['Ctl2'] ?? "",
            "Ctl3"            => $validated['Ctl3'] ?? "",
            "Ctl4"            => $validated['Ctl4'] ?? "",
            "AcctId"          => $validated['AcctId'],
            "RecsRequested"   => "0110",
            "IssueDt"        => now()->format('Ymd'),
            "TranCd"          => "34",
            "StopHoldAmt"     => $paddedAmt,
            "ExpirationDt"    => "",
            "ExpirationDays"  => (string)($validated['ExpirationDays'] ?? 5),
            "StopHoldType"    => "BAL",
            "StopHoldDesc"    => $validated['StopHoldDesc'] ?? "PAYEE UDTdescription",
            "UniversalDesc"   => $validated['UniversalDesc'] ?? "",
            "StopHoldSeq"     => "",
            "InitiatedBy"     => $validated['InitiatedBy'] ?? "",
            "Branch"          => $validated['Branch'] ?? "",
        ]
    ];

    try {
        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->retry(2, 200)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->stopHoldUrl . '?ActionCD=ADD', $payload);

        if (!$response->successful()) {
            return back()->withErrors([
                'api' => "Hold amount add failed (HTTP {$response->status()})."
            ])->withInput();
        }

        $data  = $response->json();
        $opRes = $data['WIIRSTHOperationResponse'] ?? [];
        $tsHdr = $opRes['TSRsHdr'] ?? [];

        
// Assume $resp is the decoded JSON from WIIRSTH ADD
$raw = $resp ?? [];
$hdr = data_get($raw, 'WIIRSTHOperationResponse.TSRsHdr', []);
$maxSeverity = strtoupper((string) data_get($hdr, 'MaxSeverity', 'I'));

$trnStatus = collect(data_get($hdr, 'TrnStatus', []));
$messages = $trnStatus->map(function ($m) {
    return [
        'Code'     => data_get($m, 'MsgCode'),
        'Severity' => data_get($m, 'MsgSeverity'),
        'Text'     => data_get($m, 'MsgText'),
        'Account'  => data_get($m, 'MsgAcct'),
        'Program'  => data_get($m, 'MsgPgm'),
    ];
})->all();

// Map severity to alert style
$severityMap = [
    'I' => ['class' => 'alert-info',    'label' => 'Information'],
    'W' => ['class' => 'alert-warning', 'label' => 'Warning'],
    'E' => ['class' => 'alert-danger',  'label' => 'Error'],
    'F' => ['class' => 'alert-danger',  'label' => 'Fatal'],
];

// Banner headline: prefer first message, else ProcessMessage
$headline     = $trnStatus->first()['MsgText'] ?? data_get($hdr, 'ProcessMessage', 'PROCESS COMPLETE');
$headlineCode = $trnStatus->first()['MsgCode'] ?? '';

$banner = [
    'show'    => true,
    'class'   => ($severityMap[$maxSeverity]['class'] ?? 'alert-info'),
    'label'   => ($severityMap[$maxSeverity]['label'] ?? 'Information'),
    'code'    => $headlineCode,
    'text'    => $headline,
    'isError' => in_array($maxSeverity, ['W', 'E', 'F'], true),
];

// Optional: data for your existing Summary table
$details = [
    'MaxSeverity'    => $maxSeverity,
    'ProcessMessage' => data_get($hdr, 'ProcessMessage'),
    'NextDay'        => data_get($hdr, 'NextDay'),
];



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

// Keep only the last message
if (!empty($messages)) {
    $last = end($messages);          // fetch last element
    $messages = [$last];             // make it a single-item array for the Blade forelse
} else {
    $messages = [];                  // ensure it's an array
}


        return view('hold-amount-add', [
            'details'  => $details,
            'messages' => $messages,
            'raw'      => $data,
        ]);
    } catch (\Throwable $e) {
        Log::error('Hold Amount Add exception', ['message' => $e->getMessage()]);
        return back()->withErrors([
            'api' => 'Unexpected error while adding hold amount.'
        ])->withInput();
    }
}
}
