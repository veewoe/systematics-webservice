<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Str;

use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\MessageBag;


class LoanInquiryController extends Controller
{
    private $loanUrl = 'http://172.22.242.21:18000/REST/WILRACT/';

    public function index()
    {
        return view('main');
    }

    // header
    private function commonHeader()
    {
        return [
            "MessageFormat"   => "",
            "EmployeeId"      => "WI000001",
            "LanguageCd"      => "EN",
            "ApplCode"        => "TS",
            "FuncSecCode"     => "I",
            "SourceCode"      => "",
            "EffectiveDate"   => now()->format('Ymd'),
            "TransDate"       => now()->format('Ymd'),
            "TransTime"       => now()->format('His') . '01',
            "TransSeq"        => Str::random(8),
            "SuperOverride"   => "",
            "TellerOverride"  => "",
            "ReportLevels"    => "",
            "PhysicalLocation"=> "",
            "Rebid"           => "N",
            "Reentry"         => "N",
            "Correction"      => "N",
            "Training"        => "N"
        ];
    }

    // ✔ Minimal helper to avoid fatal error
    private function safeDateFormat(?string $yyyymmdd): string
    {
        $d = trim((string) $yyyymmdd);
        if ($d === '' || strlen($d) !== 8) {
            return 'N/A';
        }
        return substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2);
    }

    // ALS Loan Inquiry
    public function loansInquiry(Request $request)
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
            'Ctl2'   => 'required|string|max:4',
            'Ctl3'   => 'required|string|max:4',
            'Ctl4'   => 'required|string|max:4',
            'AcctId' => 'required|string|max:20',
        ]);
        // Build payload
        $payload = [
            "WILRACTOperation" => [
                "TSRqHdr"    => $this->commonHeader(),
                "Ctl1"       => "0008",
                "Ctl2"       => $validated['Ctl2'] ?? null,
                "Ctl3"       => $validated['Ctl3'] ?? null,
                "Ctl4"       => $validated['Ctl4'] ?? null,
                "AcctId"     => $validated['AcctId'] ?? null,
                "EffectiveDt"=> "",
            ],
        ];

        /** @var \App\Http\Controllers\ErrorController $errCtrl */
        $errCtrl = app(\App\Http\Controllers\ErrorController::class);

        
        $response = $errCtrl->callUpstream($this->loanUrl, $payload, true, 10);

        
        if (method_exists($response, 'successful') && !$response->successful()) {
            $bag = new \Illuminate\Support\ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag([
                'Unable to reach upstream service. Please try again later.',
            ]));
            return view('loan-details', [
                'details'     => [],
                'delinquency' => [],
            ])->with('errors', $bag);
        }

        
        $data = $response->json();
        if (!is_array($data)) {
            $bag = new \Illuminate\Support\ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag([
                'Invalid response format from upstream.',
            ]));
            return view('loan-details', [
                'details'     => [],
                'delinquency' => [],
            ])->with('errors', $bag);
        }

        
        [$tsHdr, $rows] = $errCtrl->parseOperationResponse($data, 'WILRACTOperationResponse', 'WILRACTRs');

        
        if (is_array($rows) && array_keys($rows) !== range(0, count($rows) - 1)) {
            $rows = [$rows];
        }
        $rowsPresent = is_array($rows) && count($rows) > 0;

        
        if ($errMsg = $errCtrl->upstreamErrorMessage($tsHdr, $rowsPresent)) {
            $bag = new \Illuminate\Support\ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag([$errMsg]));
            return view('loan-details', [
                'details'     => [],
                'delinquency' => [],
            ])->with('errors', $bag);
        }

        
        $loan = $rows[0] ?? [];
        $details = [
            'Account ID'            => $loan['AcctId'] ?? 'N/A',
            'Customer Name'         => data_get($loan, 'NameAddrDet.0.NameAddrLine', 'N/A'),
            'Address'               => collect($loan['NameAddrDet'] ?? [])
                                        ->pluck('NameAddrLine')->filter()->implode(', '),
            'City'                  => $loan['City'] ?? 'N/A',
            'Zip'                   => $loan['Zip'] ?? 'N/A',
            'Country'               => $loan['CountryCd'] ?? 'N/A',
            'Currency'              => $loan['CurrencyCd'] ?? 'N/A',
            'Original Loan Amount'  => '₱' . number_format((float)($loan['OriginalLoanAmt'] ?? 0), 2),
            'Payoff Amount'         => '₱' . number_format((float)($loan['PayoffAmt'] ?? 0), 2),
            'Principal Balance'     => '₱' . number_format((float)($loan['PrnBal'] ?? 0), 2),
            'Interest Balance'      => '₱' . number_format((float)($loan['IntBal'] ?? 0), 2),
            'Interest Rate'         => number_format((float)($loan['IntRate'] ?? 0), 2) . '%',
            'Next Due Date'         => $this->safeDateFormat($loan['NextDueDt'] ?? null),
            'Maturity Date'         => $this->safeDateFormat($loan['MaturityDt'] ?? null),
            'Auto Debit'            => $loan['AutoDebitInd'] ?? 'N/A',
            'Product Code'          => $loan['ProductCd'] ?? 'N/A',
            'Status'                => trim((string)($tsHdr['ProcessMessage'] ?? 'OK')),
        ];


            // 🔹 Map delinquency to "Days Past Due / Boundary / Cycles Past Due"
            $delinquency = collect($loan['Delinquency'] ?? [])
                ->map(function ($row) {
        
                    $r = is_array($row) ? $row : (array) $row;

                    return [
                        'DaysPastDue'  => $r['DaysPastDueCounter'] ?? null,
                        'Boundary'     => $r['DaysPastDueBoundary'] ?? null,
                        'CyclesPastDue'=> $r['CyclesPastDueCounter'] ?? null,
                    ];
                })
                ->filter(fn ($r) => array_filter($r, fn ($v) => $v !== null))
                ->values()
                ->all();


        
        return view('loan-details', [
            'details'     => $details,
            'delinquency' => $delinquency,
        ]);

    }
}


