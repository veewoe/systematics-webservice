<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StopHoldInqController extends Controller
{      
private $stopHoldUrl = 'http://172.22.242.21:18000/REST/WIIRSTH/';

        public function index() {
            return view('main');
        }
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
}
