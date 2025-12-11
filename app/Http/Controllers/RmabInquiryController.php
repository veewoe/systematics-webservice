<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RmabInquiryController extends Controller
{
    // Upstream base URL for RMAB (Customer-Accounts relation)
    private $rmabUrl = 'http://172.22.242.21:18001/REST/WSCUST/';
 
    public function index()
    {
        return view('main');
    }
 
    // Header builder (same pattern as your LoanInquiryController)
    private function commonHeader()
    {
        return [
            "MessageFormat"    => "",
            "EmployeeId"       => "WI000001",
            "LanguageCd"       => "EN",
            "ApplCode"         => "TS",
            "FuncSecCode"      => "I",
            "SourceCode"       => "",
            "EffectiveDate"    => now()->format('Ymd'),
            "TransDate"        => now()->format('Ymd'),
            "TransTime"        => now()->format('His') . '01',
            "TransSeq"         => Str::random(8),
            "SuperOverride"    => "",
            "TellerOverride"   => "",
            "ReportLevels"     => "",
            "PhysicalLocation" => "",
            "Rebid"            => "N",
            "Reentry"          => "N",
            "Correction"       => "N",
            "Training"         => "N",
        ];
    }
 
    // Minimal safe date formatter
    private function safeDateFormat(?string $yyyymmdd): string
    {
        $d = trim((string) $yyyymmdd);
        if ($d === '' || strlen($d) !== 8) {
            return 'N/A';
        }
        return substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2);
    }
 
    public function inquiry(Request $request)
    {
        // Validate inputs (adjust max lengths as per upstream contract)
        $validated = $request->validate([
            'CustId'        => 'required|string|max:64',
            'CustAppl'      => 'nullable|string|max:10',
            'Ctl1'          => 'nullable|string|max:10',
            'Ctl2'          => 'nullable|string|max:10',
            'Ctl3'          => 'nullable|string|max:10',
            'Ctl4'          => 'nullable|string|max:10',
            'RecsRequested' => 'nullable|string|max:10',
            'ToEntCd'       => 'nullable|string|max:10',
            'RelationInfo'  => 'nullable|string|max:20',
            'MoreInd'       => 'nullable|string|max:1',
            'MoreKey'       => 'nullable|string|max:64',
        ]);
 
        // Build payload (structure from rmab.txt)
        $payload = [
            "WICRRACOperation" => [
                "TSRqHdr" => $this->commonHeader(),
                "CustAppl"      => $validated['CustAppl'] ?? "",
                "Ctl1"          => $validated['Ctl1'] ?? "0008",
                "Ctl2"          => $validated['Ctl2'] ?? "",
                "Ctl3"          => $validated['Ctl3'] ?? "",
                "Ctl4"          => $validated['Ctl4'] ?? "",
                "CustId"        => $validated['CustId'],
                "RecsRequested" => $validated['RecsRequested'] ?? "1000",
                "ToEntCd"       => $validated['ToEntCd'] ?? "",
                "RelationInfo"  => $validated['RelationInfo'] ?? "",
                "MoreInd"       => $validated['MoreInd'] ?? "",
                "MoreKey"       => $validated['MoreKey'] ?? "",
            ],
        ];
 
        /** @var \App\Http\Controllers\ErrorController $errCtrl */
        $errCtrl = app(\App\Http\Controllers\ErrorController::class);
 
        // Call upstream
        $response = $errCtrl->callUpstream($this->rmabUrl, $payload, true, 10);
 
        // Network-level failure handling
        if (method_exists($response, 'successful') && !$response->successful()) {
            $bag = new \Illuminate\Support\ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag([
                'Unable to reach upstream service. Please try again later.',
            ]));
            return view('rmab-details', [
                'accounts' => [],
                'summary'  => [],
            ])->with('errors', $bag);
        }
 
        // JSON decoding and structural check
        $data = $response->json();
        if (!is_array($data)) {
            $bag = new \Illuminate\Support\ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag([
                'Invalid response format from upstream.',
            ]));
            return view('rmab-details', [
                'accounts' => [],
                'summary'  => [],
            ])->with('errors', $bag);
        }
        [$tsHdr, $rows] = $errCtrl->parseOperationResponse(
            $data,
            'WICRRACOperationResponse',
            'WSCUSTRs'
        );
 
        if (is_array($rows) && array_keys($rows) !== range(0, count($rows) - 1)) {
            $rows = [$rows];
        }
        $rowsPresent = is_array($rows) && count($rows) > 0;
 
        // Check upstream error message (if any)
        if ($errMsg = $errCtrl->upstreamErrorMessage($tsHdr, $rowsPresent)) {
            $bag = new \Illuminate\Support\ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag([$errMsg]));
            return view('rmab-details', [
                'accounts' => [],
                'summary'  => [],
            ])->with('errors', $bag);
        }
 
        // Flatten AcctRelationInfo from all WSCUSTRs entries
        $accountsRaw = collect($rows)
            ->flatMap(function ($row) {
                $r = is_array($row) ? $row : (array) $row;
                $list = $r['AcctRelationInfo'] ?? [];
                if (is_array($list) && array_keys($list) !== range(0, count($list) - 1)) {
                    $list = [$list];
                }
                return $list;
            })
            ->values()
            ->all();
 
        // Map to presentation-friendly fields
        $accounts = collect($accountsRaw)->map(function ($a) {
            $x = is_array($a) ? $a : (array) $a;
 
            return [
                'Account ID'           => $x['AcctId'] ?? 'N/A',
                'Application'          => $x['AcctAppl'] ?? 'N/A',
               
'Ctl1/Ctl2/Ctl3/Ctl4' => (function () use ($x) {
    $vals = [
        $x['Ctl1'] ?? null,
        $x['Ctl2'] ?? null,
        $x['Ctl3'] ?? null,
        $x['Ctl4'] ?? null,
    ];
    $filtered = array_filter($vals, function ($v) {
        if (is_null($v)) return false;
        if (is_string($v) && trim($v) === '') return false;
        return true;
    });
 
    $joined = trim(implode(' | ', $filtered));
    return $joined !== '' ? $joined : 'N/A';
})(),
 
                'Account Type'         => $x['AcctType'] ?? 'N/A',
                'Product Code'         => $x['ProductCd'] ?? 'N/A',
                'Product Description'  => $x['ProductDesc'] ?? 'N/A',
                'Status'               => $x['AcctStatus'] ?? 'N/A',
                'Primary/Legal/Owner'  => trim(
                    implode(' | ', array_filter([
                        $x['PrimaryInd'] ?? null,
                        $x['LegalRelation'] ?? null,
                        $x['Ownership'] ?? null,
                    ]))
                ) ?: 'N/A',
                'Internet Banking'     => trim(
                    implode(' | ', array_filter([
                        'IB: ' . ($x['InternetBankingInd'] ?? 'N/A'),
                        'BillPay: ' . ($x['InternetBankingBillPayInd'] ?? 'N/A'),
                        'XferFrom: ' . ($x['InternetBankingXferFromInd'] ?? 'N/A'),
                        'XferTo: ' . ($x['InternetBankingXferToInd'] ?? 'N/A'),
                        'DownloadHist: ' . ($x['InternetBankingDownloadHist'] ?? 'N/A'),
                    ]))
                ),
                'Available Balance'    => '₱' . number_format((float)($x['AvailableBal'] ?? 0), 2),
                'Current Balance'      => '₱' . number_format((float)($x['CurrentBal'] ?? 0), 2),
                'Principal Balance'    => '₱' . number_format((float)($x['PrnBal'] ?? 0), 2),
                'Average Balance'      => '₱' . number_format((float)($x['AverageBal'] ?? 0), 2),
                'Past Due Amount'      => '₱' . number_format((float)($x['PastDueAmt'] ?? 0), 2),
                'Payment Amount'       => '₱' . number_format((float)($x['PmtAmt'] ?? 0), 2),
                'Term'                 => (string)($x['Term'] ?? 'N/A'),
                'Next Due Date'        => $this->safeDateFormat($x['NextDueDt'] ?? null),
                'Opened Date'          => $this->safeDateFormat($x['OpenedDt'] ?? null),
                'Added Date'           => $this->safeDateFormat($x['AddedDt'] ?? null),
                'Currency'             => $x['CurrencyCd'] ?? 'N/A',
                'BeneficiaryCd'        => $x['BeneficiaryCd'] ?? 'N/A',
                'SignatureCd'          => $x['SignatureCd'] ?? 'N/A',
                'Credit Limit'         => '₱' . number_format((float)($x['CreditLimit'] ?? 0), 2),
                'Available Credit'     => '₱' . number_format((float)($x['AvailableCreditAmt'] ?? 0), 2),
                'Overdrafts'           => (string)($x['NumOd'] ?? '0'),
                'NSFs'                 => (string)($x['NumNsf'] ?? '0'),
            ];
        })->values()->all();
 
        // Optional summary (header message etc.)
        $summary = [
            'Status'        => trim((string)($tsHdr['ProcessMessage'] ?? 'OK')),
            'MaxSeverity'   => trim((string)($tsHdr['MaxSeverity'] ?? '')),
            'NextDay'       => trim((string)($tsHdr['NextDay'] ?? '')),
            'TotalAccounts' => count($accounts),
        ];
 
        return view('rmab-details', [
            'accounts' => $accounts,
            'summary'  => $summary,
        ]);
    }
}