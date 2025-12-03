<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Str;
    use GuzzleHttp\Client;

    class ApiController extends Controller
    {
        private $loanUrl = 'http://172.22.242.21:18000/REST/WILRACT/';
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

        // ALS Loan Inquiry
        
        public function loansInquiry(Request $request)
        {
            $payload = [
        "WILRACTOperation" => [
            "TSRqHdr" => $this->commonHeader(),
            "Ctl1" => "0008",
            "Ctl2" => $request->input('Ctl2'),
            "Ctl3" => $request->input('Ctl3'),
            "Ctl4" => $request->input('Ctl4'),
            "AcctId" => $request->input('AcctId'),
            "EffectiveDt" => ""
            ]
        ];

    $response = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post($this->loanUrl, $payload);

    $data = $response->json();
    $loan = $data['WILRACTOperationResponse']['WILRACTRs'][0] ?? [];

    $details = [
        'Account ID' => $loan['AcctId'] ?? 'N/A',
        'Customer Name' => $loan['NameAddrDet'][0]['NameAddrLine'] ?? 'N/A',
        'Address' => collect($loan['NameAddrDet'])->pluck('NameAddrLine')->filter()->implode(', '),
        'City' => $loan['City'] ?? 'N/A',
        'Zip' => $loan['Zip'] ?? 'N/A',
        'Country' => $loan['CountryCd'] ?? 'N/A',
        'Currency' => $loan['CurrencyCd'] ?? 'N/A',
        'Original Loan Amount' => '₱' . number_format($loan['OriginalLoanAmt'] ?? 0, 2),
        'Payoff Amount' => '₱' . number_format($loan['PayoffAmt'] ?? 0, 2),
        'Principal Balance' => '₱' . number_format($loan['PrnBal'] ?? 0, 2),
        'Interest Balance' => '₱' . number_format($loan['IntBal'] ?? 0, 2),
        'Interest Rate' => number_format($loan['IntRate'] ?? 0, 2) . '%',
        'Next Due Date' => \Carbon\Carbon::createFromFormat('Ymd', $loan['NextDueDt'])->format('M d, Y'),
        'Maturity Date' => \Carbon\Carbon::createFromFormat('Ymd', $loan['MaturityDt'])->format('M d, Y'),
        'Auto Debit' => $loan['AutoDebitInd'] ?? 'N/A',
        'Product Code' => $loan['ProductCd'] ?? 'N/A',
        'Status' => trim($data['WILRACTOperationResponse']['TSRsHdr']['ProcessMessage'] ?? 'N/A')
    ];

    return view('loan-details', [
        'details' => $details,
        'delinquency' => $loan['Delinquency'] ?? []
    ]);
}

        
// Stop Hold Inquiry
public function stopHoldInquiry(Request $request)
{
    $validated = $request->validate([
        'Ctl2'   => ['nullable', 'string', 'max:10'],
        'Ctl3'   => ['nullable', 'string', 'max:10'],
        'Ctl4'   => ['nullable', 'string', 'max:10'],
        'AcctId' => ['required', 'string', 'max:32'],
        'verb'   => ['nullable', 'in:GET,POST'], 
    ]);

    $acctRaw = preg_replace('/\D+/', '', $validated['AcctId']); // keep digits
    $acctId = str_pad($acctRaw, 13, '0', STR_PAD_LEFT);

    $ctl1 = '0008';
    $ctl2 = isset($validated['Ctl2']) ? str_pad($validated['Ctl2'], 4, '0', STR_PAD_LEFT) : '';
    $ctl3 = isset($validated['Ctl3']) ? str_pad($validated['Ctl3'], 4, '0', STR_PAD_LEFT) : '';
    $ctl4 = isset($validated['Ctl4']) ? str_pad($validated['Ctl4'], 4, '0', STR_PAD_LEFT) : '';
    $payload = [
        "WIIRSTHOperation" => [
            "TSRqHdr" => $this->commonHeader(), // EmployeeId, ApplCode, etc.
            // Many Systematics endpoints expect TS detail under a second object
            "TSRqDtl" => [
                "Ctl1"          => $ctl1,
                "Ctl2"          => $ctl2,
                "Ctl3"          => $ctl3,
                "Ctl4"          => $ctl4,
                "AcctId"        => $acctId,
                "RecsRequested" => "0001",
                // Leave optional filters blank per spec
                "StopInd"               => "",
                "HoldInd"               => "",
                "HoldAllInd"            => "",
                "SpecialInstructionsInd"=> "",
                "SuspectInd"            => "",
                "StopRangeInd"          => "",
                "SuspectRangeInd"       => "",
                "StartCheckNum"         => "",
                "EndCheckNum"           => "",
                "StartDt"               => "",
                "EndDt"                 => "",
                "LowAmt"                => "",
                "HighAmt"               => "",
                "LowSeq"                => "",
                "HighSeq"               => "",
                "TranCd"                => "",
                "StopHoldAmt"           => "",
                "ExpirationDt"          => "",
                "ExpirationDays"        => "",
                "IssueDt"               => "",
                "InitiatedBy"           => "",
                "StopHoldType"          => "",
                "StopHoldDesc"          => "",
                "AllFundsInd"           => "",
                "ForceBalNegative"      => "",
                "WaiveFeeInd"           => "",
                "UniversalDesc"         => "",
                "StopHoldSeq"           => "",
            ],
        ],
    ];

    // ---- Decide verb: default GET (per reference), fallback to POST if needed ----
    $verb = $validated['verb'] ?? config('systematics.wiirsth_verb', 'GET');

    try {
        // Log the outbound payload once for troubleshooting
        Log::debug('StopHold request', ['verb' => $verb, 'url' => $this->stopHoldUrl, 'payload' => $payload]);

        $http = \Illuminate\Support\Facades\Http::timeout(15)->retry(2, 250)
            ->acceptJson()
            ->withHeaders(['Content-Type' => 'application/json']);

        if (strtoupper($verb) === 'GET') {
            // Some gateways support GET with body; Laravel's send() allows this
            $response = $http->send('GET', $this->stopHoldUrl, ['json' => $payload]);
        } else {
            $response = $http->post($this->stopHoldUrl, $payload);
        }

        if (!$response->successful()) {
            Log::warning('StopHold non-200', ['status' => $response->status(), 'body' => $response->body()]);
            return back()->withErrors([
                'api' => "StopHold inquiry failed (HTTP {$response->status()})."
            ])->withInput();
        }

        $data = $response->json();
        Log::debug('StopHold raw response', ['data' => $data]);

        // ---- Safely unwrap response ----
        $opRes = $data['WIIRSTHOperationResponse'] ?? [];
        $tsHdr = $opRes['TSRsHdr'] ?? [];
        $rsList = $opRes['WIIRSTHRs'] ?? [];
        $rs0 = is_array($rsList) && count($rsList) ? $rsList[0] : [];

        // ---- Date formatter: supports 8-digit Ymd, 0, and 999999(no expiry) ----
        $fmtYmd = function ($val) {
            if ($val === null || $val === '' || !is_numeric($val)) return 'N/A';
            $ival = (int)$val;
            if ($ival === 0) return 'N/A';
            if ($ival === 999999) return 'No Expiry';
            // Most dates are Ymd (8); if 6 (Ymd w/o century), we can try to upcast
            $str = (string)$val;
            if (strlen($str) === 6) {
                // naive upcast to 20yy if needed; adjust per bank rule
                $str = '20' . $str; // e.g., 201708 -> 20201708 (if upstream does 6-digit)
            }
            $str = str_pad($str, 8, '0', STR_PAD_LEFT);
            try {
                return \Carbon\Carbon::createFromFormat('Ymd', $str)->format('M d, Y');
            } catch (\Throwable $e) {
                return 'N/A';
            }
        };
        $details = [
            'Account ID'        => $rs0['AcctId']        ?? $acctId,
            'Ctl1'              => $rs0['Ctl1']          ?? $ctl1,
            'Ctl2'              => $rs0['Ctl2']          ?? ($ctl2 ?: 'N/A'),
            'Ctl3'              => $rs0['Ctl3']          ?? ($ctl3 ?: 'N/A'),
            'Ctl4'              => $rs0['Ctl4']          ?? ($ctl4 ?: 'N/A'),
            'Records Returned'  => $rs0['RecsReturned']  ?? '0',
            'More Indicator'    => $rs0['MoreInd']       ?? 'N/A',
            'Status'            => trim($tsHdr['ProcessMessage'] ?? 'N/A'),
        ];

        // ---- Build table rows ----
        $list = $rs0['StopHoldList'] ?? [];
        $items = [];
        foreach ($list as $row) {
            $items[] = [
                'Seq'               => $row['StopHoldSeq']         ?? '—',
                'Type'              => $row['Type']                 ?? '—',
                'SubType'           => $row['SubType']              ?? '—',
                'Currency'          => $row['CurrencyCd']           ?? '—',
                'Amount'            => is_numeric($row['StopHoldAmt'] ?? null)
                                       ? '₱' . number_format((float)$row['StopHoldAmt'], 2)
                                       : '—',
                'Entry Date'        => $fmtYmd($row['EntryDt']      ?? null),
                'Issue Date'        => $fmtYmd($row['IssueDt']      ?? null),
                'Expiration Date'   => $fmtYmd($row['ExpirationDt'] ?? null),
                'Exp Days'          => $row['ExpirationDays']       ?? '—',
                'Exp Remaining'     => $row['ExpirationRemainingDays'] ?? '—',
                'Start Check #'     => $row['StartCheckNum']        ?? '—',
                'End Check #'       => $row['EndCheckNum']          ?? '—',
                'Waive Fee'         => $row['WaiveFeeInd']          ?? '—',
                'Initiated By'      => $row['InitiatedBy']          ?? '—',
                'Branch'            => $row['Branch']               ?? '—',
                'Description'       => $row['StopHoldDesc']         ?? (
                    // If UniversalDesc carries the message, show the first line
                    isset($row['UniversalDesc'][0]['UniversalDescLine'])
                        ? $row['UniversalDesc'][0]['UniversalDescLine']
                        : '—'
                ),
            ];
        }

        // ---- Handle no items gracefully ----
        if (empty($items)) {
            $details['Status'] = $details['Status'] ?: 'PROCESS COMPLETE';
            $items = []; // keep empty; view can show "No stop/hold found"
        }

        return view('stop-hold-inq', [
            'details' => $details,
            'items'   => $items,
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('StopHold exception', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);
        return back()->withErrors([
            'api' => 'Unexpected error while performing StopHold inquiry.'
        ])->withInput();
    }
}




    
public function holdAmountAdd(Request $request)
{
    // Basic validation (adjust rules to your needs)
    $validated = $request->validate([
        'Ctl2'          => ['nullable', 'string', 'max:10'],
        'Ctl3'          => ['nullable', 'string', 'max:10'],
        'Ctl4'          => ['nullable', 'string', 'max:10'],
        'AcctId'        => ['required', 'string', 'max:32'],
        'StopHoldAmt'   => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
        'ExpirationDays'=> ['nullable', 'integer', 'min:1', 'max:999'],
        'StopHoldDesc'  => ['nullable', 'string', 'max:200'],
        'UniversalDesc' => ['nullable', 'string', 'max:1024'],
        'InitiatedBy'   => ['nullable', 'string', 'max:32'],
        'Branch'        => ['nullable', 'string', 'max:32'],
    ]);

    // Format amount to 17-char padded minor units (e.g., cents)
    $rawAmt = $validated['StopHoldAmt'];
    $minorUnits = (int) round(((float) $rawAmt) * 100);
    $paddedAmt = str_pad((string) $minorUnits, 17, '0', STR_PAD_LEFT);

    $payload = [
        "WIIRSTHOperation" => [
            "TSRqHdr"         => $this->commonHeader(),
            "Ctl1"            => "0008",
            "Ctl2"            => $validated['Ctl2'] ?? "",
            "Ctl3"            => $validated['Ctl3'] ?? "",
            "Ctl4"            => $validated['Ctl4'] ?? "",
            "AcctId"          => $validated['AcctId'],
            "RecsRequested"   => "0000",
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

        // Summary details like your other views
        $details = [
            'Severity'       => $tsHdr['MaxSeverity']    ?? 'N/A',
            'Process Message'=> trim($tsHdr['ProcessMessage'] ?? 'N/A'),
            'Next Day'       => $tsHdr['NextDay']        ?? 'N/A',
        ];

        // Messages table: TrnStatus entries
        $messages = [];
        foreach (($tsHdr['TrnStatus'] ?? []) as $msg) {
            $messages[] = [
                'Code'      => $msg['MsgCode']     ?? '—',
                'Severity'  => $msg['MsgSeverity'] ?? '—',
                'Text'      => $msg['MsgText']     ?? '—',
                'Account'   => $msg['MsgAcct']     ?? '—',
                'Program'   => $msg['MsgPgm']      ?? '—',
                // 'Dag'       => $msg['MsgDag']      ?? '—',
                // 'Field'     => $msg['MsgField']    ?? '—',
                // 'Dim1'      => $msg['MsgFieldDim1']?? '—',
                // 'Dim2'      => $msg['MsgFieldDim2']?? '—',
                // 'Dim3'      => $msg['MsgFieldDim3']?? '—',
            ];
        }

        return view('hold-amount-add', [
            'details'  => $details,
            'messages' => $messages,
            // 'raw'      => $data,
        ]);
    } catch (\Throwable $e) {
        Log::error('Hold Amount Add exception', ['message' => $e->getMessage()]);
        return back()->withErrors([
            'api' => 'Unexpected error while adding hold amount.'
        ])->withInput();
    }
}
public function holdAllAdd(Request $request)
    {
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
                "ExpirationDays" => "15",
                "IssueDt" => "",
                "InitiatedBy" => "",
                "StopHoldType" => "BAL",
                "StopHoldDesc" => "PAYEE UDTdescription",
                "AllFundsInd" => "Y",
                "ForceBalNegative" => "",
                "WaiveFeeInd" => "",
                "UniversalDesc" => "SSSS56789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+line2-789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+line3-789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+line4-789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+",
                "StopHoldSeq" => ""
            ]
        ];

        Log::info('Hold All Add Payload', $payload);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->withBody(json_encode($payload), 'application/json')
            ->post($this->stopHoldUrl . '?ActionCD=AddHoldALL');

        Log::info('Hold All Add Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return response()->json([
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    }


        //Hold Delete
    public function holdDelete(Request $request)
    {
        $payload = [
            "WIIRSTHOperation" => [
                "TSRqHdr" => $this->commonHeader(),
                "Ctl1" => "0008",
                "Ctl2" => $request->input('Ctl2'),
                "Ctl3" => $request->input('Ctl3'),
                "Ctl4" => $request->input('Ctl4'),
                "AcctId" => $request->input('AcctId'),
                "RecsRequested" => "0000",
                "TranCd" => "30", // Matches Postman
                "StopHoldSeq" => $request->input('StopHoldSeq'), // Required for delete
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
                "StopHoldAmt" => "",
                "ExpirationDt" => "",
                "ExpirationDays" => "",
                "IssueDt" => "",
                "InitiatedBy" => "",
                "StopHoldType" => "",
                "StopHoldDesc" => "",
                "AllFundsInd" => "",
                "ForceBalNegative" => "",
                "WaiveFeeInd" => "",
                "UniversalDescLine" => "WWWW"
            ]
        ];

        Log::info('Hold Delete Payload', $payload);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->withBody(json_encode($payload), 'application/json')
            ->post($this->stopHoldUrl . '?ActionCD=D');

        Log::info('Hold Delete Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return response()->json($response->json());
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
    }