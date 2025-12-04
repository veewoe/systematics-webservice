<?php

    namespace App\Http\Controllers;

    use Illuminate\Support\Facades\Validator;
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


        
/**
 * Perform upstream call with GET → POST fallback.
 * Set $preferGetFirst=false for endpoints that are POST-only.
 */
private function callUpstream(string $url, array $payload, bool $preferGetFirst = true, int $timeout = 10)
{
    if ($preferGetFirst) {
        // Try GET (with JSON body) first
        $response = \Illuminate\Support\Facades\Http::timeout($timeout)
            ->retry(2, 200)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withBody(json_encode($payload), 'application/json')
            ->get($url);

        if ($response->successful()) {
            return $response;
        }

        // Fallback: POST
        return \Illuminate\Support\Facades\Http::timeout($timeout)
            ->retry(2, 200)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);
    }

    // POST-only path
    return \Illuminate\Support\Facades\Http::timeout($timeout)
        ->retry(2, 200)
        ->asJson()
        ->post($url, $payload);
}


private function upstreamErrorMessage(?array $tsHdr, bool $rowsPresent): ?string
{
    $tsHdr    = $tsHdr ?? [];
    $severity = strtoupper(trim((string)($tsHdr['MaxSeverity'] ?? '')));
    if ($severity === 'E' || !$rowsPresent) {
        $status  = ($tsHdr['TrnStatus'] ?? [])[0] ?? [];
        $msgCode = trim((string)($status['MsgCode'] ?? 'UNKNOWN'));
        $msgText = trim((string)($status['MsgText'] ?? 'Upstream error'));
        return "{$msgCode}: {$msgText} Severity {$severity}";
    }
    return null;
}


private function parseOperationResponse(array $data, string $opResponseKey, string $rowsKey): array
{
    $opRes = $data[$opResponseKey] ?? [];
    $tsHdr = $opRes['TSRsHdr'] ?? [];
    $rows  = $opRes[$rowsKey] ?? [];
    return [$tsHdr, $rows];
}

        // ALS Loan Inquiry
        
public function loansInquiry(Request $request)
{
    // Validate controls + account ID
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'Ctl1'   => ['nullable', 'string', 'max:10'],
        'Ctl2'   => ['required', 'string', 'max:10'],
        'Ctl3'   => ['required', 'string', 'max:10'],
        'Ctl4'   => ['required', 'string', 'max:10'],
        'AcctId' => ['required'],
    ], [
        'AcctId.required' => 'Account ID is required.',
    ]);

    if ($validator->fails()) {
        return view('loan-details', [
            'error'       => $validator->errors()->first('AcctId') ?? 'Invalid input.',
            'details'     => [],
            'delinquency' => [],
        ]);
    }

    // Inputs
    $ctl1   = trim((string) $request->input('Ctl1', '0008'));
    $ctl2   = trim((string) $request->input('Ctl2', ''));
    $ctl3   = trim((string) $request->input('Ctl3', ''));
    $ctl4   = trim((string) $request->input('Ctl4', ''));
    $acctId = trim((string) $request->input('AcctId'));

    // Payload
    $payload = [
        "WILRACTOperation" => [
            "TSRqHdr"    => $this->commonHeader(),
            "Ctl1"       => $ctl1,
            "Ctl2"       => $ctl2,
            "Ctl3"       => $ctl3,
            "Ctl4"       => $ctl4,
            "AcctId"     => $acctId,
            "EffectiveDt"=> "",
        ],
    ];

    $error       = null;
    $details     = [];
    $delinquency = [];

    try {
        // GET → POST fallback preserved
        $response = $this->callUpstream($this->loanUrl, $payload, /* preferGetFirst */ true);

        if (!$response->successful()) {
            $error = "Request failed (HTTP {$response->status()}). Please try again.";
        } else {
            $data = $response->json();
            [$tsHdr, $rows] = $this->parseOperationResponse($data, 'WILRACTOperationResponse', 'WILRACTRs');

            // Upstream business error banner
            $error = $this->upstreamErrorMessage($tsHdr, !empty($rows));

            if (!$error) {
                // Success: build details without any date formatting fields
                $loan = $rows[0] ?? [];

                $nameAddrLines = collect($loan['NameAddrDet'] ?? [])
                    ->pluck('NameAddrLine')->filter();

                $details = [
                    'Account ID'           => (string)($loan['AcctId'] ?? 'N/A'),
                    'Customer Name'        => $nameAddrLines->first() ?? 'N/A',
                    'Address'              => $nameAddrLines->implode(', '),
                    'City'                 => $loan['City'] ?? 'N/A',
                    'Zip'                  => $loan['Zip'] ?? 'N/A',
                    'Country'              => $loan['CountryCd'] ?? 'N/A',
                    'Currency'             => $loan['CurrencyCd'] ?? 'N/A',
                    'Original Loan Amount' => '₱' . number_format((float)($loan['OriginalLoanAmt'] ?? 0), 2),
                    'Payoff Amount'        => '₱' . number_format((float)($loan['PayoffAmt'] ?? 0), 2),
                    'Principal Balance'    => '₱' . number_format((float)($loan['PrnBal'] ?? 0), 2),
                    'Interest Balance'     => '₱' . number_format((float)($loan['IntBal'] ?? 0), 2),
                    'Interest Rate'        => number_format((float)($loan['IntRate'] ?? 0), 2) . '%',
                    'Auto Debit'           => $loan['AutoDebitInd'] ?? 'N/A',
                    'Product Code'         => $loan['ProductCd'] ?? 'N/A',
                    'Status'               => trim((string)($tsHdr['ProcessMessage'] ?? 'COMPLETE')),
                ];

                $delinquency = $loan['Delinquency'] ?? [];
            }
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('LoanInquiry exception', ['message' => $e->getMessage()]);
        $error = 'Unexpected error.';
    }

    return view('loan-details', [
        'error'       => $error,
        'details'     => $details,
        'delinquency' => $delinquency,
    ]);
}


public function stopHoldInquiry(Request $request)
{
    // 1) Validate controls + account ID
    $validated = $request->validate([
        'Ctl2'   => ['nullable', 'string', 'max:10'],
        'Ctl3'   => ['nullable', 'string', 'max:10'],
        'Ctl4'   => ['nullable', 'string', 'max:10'],
        'AcctId' => ['required', 'string', 'max:32'],
    ]);

    // 2) Hard-coded TSRqHdr (exactly as your spec)
    $tsRqHdr = [
        "MessageFormat"    => "",
        "EmployeeId"       => "WI000001",
        "LanguageCd"       => "EN",
        "ApplCode"         => "TS",
        "FuncSecCode"      => "I",
        "SourceCode"       => "",
        "EffectiveDate"    => now(),   // ← hard-coded
        "TransTime"        => now(),   // ← hard-coded
        "SuperOverride"    => "",
        "TellerOverride"   => "",
        "PhysicalLocation" => "",
        "Rebid"            => "N",
        "Reentry"          => "N",
        "Correction"       => "N",
        "Training"         => "N",
    ];

    // 3) Controls: hard-coded defaults if no input
    $ctl1   = "0008";
    $ctl2   = trim((string)($validated['Ctl2'] ?? "0001"));
    $ctl3   = trim((string)($validated['Ctl3'] ?? "0000"));
    $ctl4   = trim((string)($validated['Ctl4'] ?? "1888"));
    $acctId = trim((string)$validated['AcctId']);

    // 4) Operation base: match your spec; blank strings where no input option exists
    $base = [
        "TSRqHdr"             => $tsRqHdr,
        "Ctl1"                => $ctl1,
        "Ctl2"                => $ctl2,
        "Ctl3"                => $ctl3,
        "Ctl4"                => $ctl4,
        "AcctId"              => $acctId,
        "RecsRequested"       => "0005",
        "StopInd"             => "",
        "HoldInd"             => "",
        "HoldAllInd"          => "",
        "SpecialInstructionsInd" => "",
        "SuspectInd"          => "",
        "StopRangeInd"        => "",
        "SuspectRangeInd"     => "",
        "StartCheckNum"       => "",
        "EndCheckNum"         => "",
        "StartDt"             => "",
        "EndDt"               => "",
        "LowAmt"              => "",
        "HighAmt"             => "",
        "LowSeq"              => "",
        "HighSeq"             => "",
        "TranCd"              => "",
        "StopHoldAmt"         => "",
        "ExpirationDt"        => "",
        "ExpirationDays"      => "",
        "IssueDt"             => "",
        "InitiatedBy"         => "",
        "StopHoldType"        => "",
        "StopHoldDesc"        => "",
        "AllFundsInd"         => "",
        "ForceBalNegative"    => "",
        "WaiveFeeInd"         => "",
        "UniversalDesc"       => "",
        "StopHoldSeq"         => "",
    ];

    $payload     = ["WIIRSTHOperation" => $base];
    $errorBanner = null;
    $details     = [];
    $items       = [];

    try {
        // 5) Upstream call: GET first (per spec), then POST fallback
        $response = $this->callUpstream($this->stopHoldUrl, $payload, /* preferGetFirst */ true);

        // Transport error (HTTP not 2xx) → back with error
        if (!$response->successful()) {
            return back()->withErrors([
                'api' => "StopHold inquiry failed (HTTP {$response->status()})."
            ])->withInput();
        }

        // 6) Parse envelope: TSRsHdr + WIIRSTHRs
        $data = $response->json();
        [$tsHdr, $rows] = $this->parseOperationResponse($data, 'WIIRSTHOperationResponse', 'WIIRSTHRs');

        // 7) Upstream business error banner (severity 'E' or empty rows)
        $errorBanner = $this->upstreamErrorMessage($tsHdr, !empty($rows));

        // 8) Header details (use first results row for summary)
        $rs0 = $rows[0] ?? [];
        $details = [
            'Account ID'       => $rs0['AcctId'] ?? 'N/A',
            'Ctl1'             => $rs0['Ctl1'] ?? 'N/A',
            'Ctl2'             => $rs0['Ctl2'] ?? 'N/A',
            'Ctl3'             => $rs0['Ctl3'] ?? 'N/A',
            'Ctl4'             => $rs0['Ctl4'] ?? 'N/A',
            'Records Returned' => $rs0['RecsReturned'] ?? '0',
            'More Indicator'   => $rs0['MoreInd'] ?? 'N/A',
            'Status'           => trim((string)($tsHdr['ProcessMessage'] ?? 'N/A')),
        ];

        // 9) Stop/Hold List rows (keys align with your Blade)
        $list = $rs0['StopHoldList'] ?? [];
        foreach ($list as $row) {
            $items[] = [
                'Seq'             => $row['StopHoldSeq'] ?? '—',
                'Type'            => $row['Type'] ?? '—',
                'SubType'         => $row['SubType'] ?? '—',
                'Currency'        => $row['CurrencyCd'] ?? '—',
                'Amount'          => is_numeric($row['StopHoldAmt'] ?? null)
                                        ? '₱' . number_format((float)$row['StopHoldAmt'], 2)
                                        : '—',
                // Keep upstream integer dates as-is (no formatting)
                'Entry Date'      => $row['EntryDt'] ?? '—',
                'Issue Date'      => $row['IssueDt'] ?? '—',
                'Expiration Date' => $row['ExpirationDt'] ?? '—',
                'Exp Days'        => $row['ExpirationDays'] ?? '—',
                'Exp Remaining'   => $row['ExpirationRemainingDays'] ?? '—',
                'Start Check #'   => $row['StartCheckNum'] ?? '—',
                'End Check #'     => $row['EndCheckNum'] ?? '—',
                'Waive Fee'       => $row['WaiveFeeInd'] ?? '—',
                'Initiated By'    => $row['InitiatedBy'] ?? '—',
                'Branch'          => $row['Branch'] ?? '—',
                'Description'     => $row['StopHoldDesc'] ?? '—',
            ];
        }

        // 10) Render Blade with standardized variables (your current Blade matches this)
        return view('stop-hold-inq', [
            'error'   => $errorBanner,
            'details' => $details,
            'items'   => $items,
        ]);

    } catch (\Throwable $e) {
        \Log::error('StopHold exception', ['message' => $e->getMessage()]);
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
        \Log::error('Hold Amount Add exception', ['message' => $e->getMessage()]);
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
