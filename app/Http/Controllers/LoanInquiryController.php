<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Str;

class LoanInquiryController extends Controller
{
    private $loanUrl = 'http://172.22.242.21:18000/REST/WILRACT/';

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
}