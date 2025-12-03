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
        $payload = [
            "WIIRSTHOperation" => [
                "TSRqHdr" => $this->commonHeader(),
                "Ctl1" => "0008",
                "Ctl2" => $request->input('Ctl2'),
                "Ctl3" => $request->input('Ctl3'),
                "Ctl4" => $request->input('Ctl4'),
                "AcctId" => $request->input('AcctId'),
                "RecsRequested" => "0001",
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
                "TranCd" => "",
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
                "UniversalDesc" => "",
                "StopHoldSeq" => ""
            ]
        ];

        Log::info('StopHold Inquiry Payload', $payload);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->withBody(json_encode($payload), 'application/json')
            ->send('GET', $this->stopHoldUrl);

        Log::info('StopHold Inquiry Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return response()->json($response->json());
    }


    public function holdAmountAdd(Request $request)
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
                "StopHoldAmt" => str_pad($request->input('StopHoldAmt'), 17, "0", STR_PAD_LEFT), // Format like Postman
                "ExpirationDt" => "",
                "ExpirationDays" => "5",
                "IssueDt" => "",
                "InitiatedBy" => "",
                "StopHoldType" => "BAL",
                "StopHoldDesc" => "PAYEE UDTdescription",
                "AllFundsInd" => "",
                "ForceBalNegative" => "",
                "WaiveFeeInd" => "",
                "UniversalDesc" => "SSSS56789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+line2-789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+line3-789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+line4-789+abcdefghi+123456789+abcdefghi+123456789+abcdefghi+",
                "StopHoldSeq" => ""
            ]
        ];

        Log::info('Hold Amount Add Payload', $payload);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->withBody(json_encode($payload), 'application/json')
            ->post($this->stopHoldUrl . '?ActionCD=ADD');

        Log::info('Hold Amount Add Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return response()->json($response->json());
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
