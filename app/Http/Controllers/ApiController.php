<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Str;


    class ApiController extends Controller
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

// ALS Loan Inquiry moved
// Stop Hold Inquiry moved
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
    }