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
    ]);

    $base = [
        "TSRqHdr" => $this->commonHeader(),
        "Ctl1"    => "0008",
        "Ctl2"    => $validated['Ctl2'] ?? "",
        "Ctl3"    => $validated['Ctl3'] ?? "",
        "Ctl4"    => $validated['Ctl4'] ?? "",
        "AcctId"  => $validated['AcctId'],
        "RecsRequested" => "0001",
        // ... other fields left blank per spec
    ];

    try {
        // Prefer POST with JSON; change to GET+query if upstream requires it.
        $payload = ["WIIRSTHOperation" => $base];

        $response = \Illuminate\Support\Facades\Http::timeout(10)
            ->retry(2, 200)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->stopHoldUrl, $payload);

        if (!$response->successful()) {
            return back()->withErrors([
                'api' => "StopHold inquiry failed (HTTP {$response->status()})."
            ])->withInput();
        }

        $data  = $response->json();
        $opRes = $data['WIIRSTHOperationResponse'] ?? [];
        $tsHdr = $opRes['TSRsHdr'] ?? [];
        $rs0   = ($opRes['WIIRSTHRs'] ?? [])[0] ?? [];

        // Helper to format Ymd or special values (0, 999999) safely
        $fmtYmd = function ($val) {
            if (empty($val) || !is_numeric($val)) return 'N/A';
            if ((int)$val === 0) return 'N/A';
            if ((int)$val === 999999) return 'No Expiry';
            $str = str_pad((string)$val, 8, '0', STR_PAD_LEFT);
            try {
                return \Carbon\Carbon::createFromFormat('Ymd', $str)->format('M d, Y');
            } catch (\Throwable $e) {
                return 'N/A';
            }
        };

        // Summary details for the header table
        $details = [
            'Account ID'   => $rs0['AcctId']        ?? 'N/A',
            'Ctl1'         => $rs0['Ctl1']          ?? 'N/A',
            'Ctl2'         => $rs0['Ctl2']          ?? 'N/A',
            'Ctl3'         => $rs0['Ctl3']          ?? 'N/A',
            'Ctl4'         => $rs0['Ctl4']          ?? 'N/A',
            'Records Returned' => $rs0['RecsReturned'] ?? '0',
            'More Indicator'   => $rs0['MoreInd']      ?? 'N/A',
            'Status'       => trim($tsHdr['ProcessMessage'] ?? 'N/A'),
        ];

        // Row items for StopHoldList
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
                'Description'       => $row['StopHoldDesc']         ?? '—',
            ];
        }

        // Render like loan inquiry
        return view('stop-hold-inq', [
            'details' => $details,
            'items'   => $items,
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('StopHold exception', ['message' => $e->getMessage()]);
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
