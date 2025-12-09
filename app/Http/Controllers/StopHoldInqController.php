<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class StopHoldInqController extends Controller
{      
        private $stopHoldUrl = 'http://172.22.242.21:18000/REST/WIIRSTH/';
    
        public function index() {
            return view('main');
        }

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
        $errormsg = $status['MsgText'];
        $msgCode = trim((string)($status['MsgCode'] ?? 'UNKNOWN'));
        $msgText = trim((string)($status['MsgText'] ?? 'Upstream error'));
        return "Error Code:{$msgCode}: {$msgText}: Severity: {$tsHdr['MaxSeverity']}";
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
        
public function stopHoldInquiry(Request $request)
{
    $validated = $request->validate([
        'Ctl2'   => ['nullable', 'string', 'max:10'],
        'Ctl3'   => ['nullable', 'string', 'max:10'],
        'Ctl4'   => ['nullable', 'string', 'max:10'],
        'AcctId' => ['required', 'string', 'max:32'],
    ]);
 
    $tsRqHdr = [
        "MessageFormat"    => "",
        "EmployeeId"       => "WI000001",
        "LanguageCd"       => "EN",
        "ApplCode"         => "TS",
        "FuncSecCode"      => "I",
        "SourceCode"       => "",
        "EffectiveDate"    => now(),
        "TransTime"        => now(),   
        "SuperOverride"    => "",
        "TellerOverride"   => "",
        "PhysicalLocation" => "",
        "Rebid"            => "N",
        "Reentry"          => "N",
        "Correction"       => "N",
        "Training"         => "N",
    ];
 

    $ctl1   = "0008";
    $ctl2   = trim((string)($validated['Ctl2'] ?? "0001"));
    $ctl3   = trim((string)($validated['Ctl3'] ?? "0000"));
    $ctl4   = trim((string)($validated['Ctl4'] ?? "1888"));
    $acctId = trim((string)$validated['AcctId']);
 

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
   
        $response = $this->callUpstream($this->stopHoldUrl, $payload,true);
 
        if (!$response->successful()) {
            return back()->withErrors([
                'api' => "StopHold inquiry failed (HTTP {$response->status()})."
            ])->withInput();
        }
 
        $data = $response->json();
        [$tsHdr, $rows] = $this->parseOperationResponse($data, 'WIIRSTHOperationResponse', 'WIIRSTHRs');
 
        $errorBanner = $this->upstreamErrorMessage($tsHdr, !empty($rows));
 
        $rs0 = $rows[0] ?? [];
        $details = [
            'Account ID'       => $rs0['AcctId'] ?? 'N/A',
            'Ctl1'             => $rs0['Ctl1'] ?? 'N/A',
            'Ctl2'             => $rs0['Ctl2'] ?? 'N/A',
            'Ctl3'             => $rs0['Ctl3'] ?? 'N/A',
            'Ctl4'             => $rs0['Ctl4'] ?? 'N/A',
            'Records Returned' => $rs0['RecsReturned'] ?? '0',
            'More Indicator'   => $rs0['MoreInd'] ?? 'N/A',
            'Transaction Status'           => trim((string)($tsHdr['ProcessMessage'] ?? 'N/A')),
        ];
 
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
 
        return view('stop-hold-inq', [
            'error'   => $errorBanner,
            'details' => $details,
            'items'   => $items,
        ]);
 
    } catch (\Throwable $e) {
        Log::error('StopHold exception', ['message' => $e->getMessage()]);
        return back()->withErrors([
            'api' => 'Unexpected error while performing StopHold inquiry.'
        ])->withInput();
    }
}

public function show(Request $request)
{
    $acctNo = $request->query('acctNo');
    $cbr    = $request->query('cbr');
    $cbi    = $request->query('cbi');
    $cba    = $request->query('cba');

    $payload = array_filter([
        'acctNo' => $acctNo,
        'cbr'    => $cbr,
        'cbi'    => $cbi,
        'cba'    => $cba,
    ], fn ($v) => !is_null($v) && $v !== '');

    $resp = Http::timeout(10)->retry(2, 200)->asForm()
        ->post('http://172.22.242.21:18000/REST/WIIRSTH/?ActionCD=I', $payload);

    if ($resp->failed()) {
        $msg = 'Inquiry failed. ' . \Illuminate\Support\Str::limit(strip_tags($resp->body()), 250);

        return view('stopHold.inquiry', [
            'details' => [],
            'items'   => [],
            'tsHdr'   => [],
            'tsMsgs'  => [],
        ])->withErrors(['api' => $msg]);
    }

    $json = $resp->json();

    return view('stopHold.inquiry', compact('details', 'items', 'tsHdr', 'tsMsgs'));
}

}