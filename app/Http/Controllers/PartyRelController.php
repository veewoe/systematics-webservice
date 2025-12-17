<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PartyRelController extends Controller
{
    private string $partyRelUrl = 'http://172.22.242.21:18001/REST/PartyRel/';

    private function commonHeader(): array
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
    
    public function store(Request $request)
    {
        // Validation (CustId only; optional paging fields)
        $validator = Validator::make($request->all(), [
            'Ctl1'       => ['required', 'regex:/^\d{4}$/'],
            'Ctl2'       => ['required', 'regex:/^\d{4}$/'],
            'Ctl3'       => ['required', 'regex:/^\d{4}$/'],
            'Ctl4'       => ['required', 'regex:/^\d{4}$/'],
            'CustId'     => ['required', 'regex:/^\d{11,14}$/'],
            'RelMoreInd' => ['nullable', 'in:M'],  // only "M" is meaningful for paging
            'ToEntKey'   => ['nullable', 'string'],// cursor for next page
        ]);

        if ($validator->fails()) {
            return response()->view('party-rel-table', [
                'header'   => ['ProcessMessage' => 'Validation failed: '.$validator->errors()->first()],
                'summary'  => [
                    'Ctl1' => $request->input('Ctl1', ''), 'Ctl2' => $request->input('Ctl2', ''),
                    'Ctl3' => $request->input('Ctl3', ''), 'Ctl4' => $request->input('Ctl4', ''),
                    'CustId' => $request->input('CustId', ''),
                    'RestOfRelatedKey' => '',
                    'RelMoreInd' => '',
                ],
                'accounts' => [],
                'nextCursor' => '',
                'raw'      => ['validationErrors' => $validator->errors()->toArray()],
            ], 422);
        }

        $v = $validator->validated();

        // Build payload (single page)
        $partyRelRq = [
            "Ctl1"             => $v['Ctl1'],
            "Ctl2"             => $v['Ctl2'],
            "Ctl3"             => $v['Ctl3'],
            "Ctl4"             => $v['Ctl4'],
            "CustId"           => $v['CustId'],
            "RestOfRelatedKey" => "",
            "RelMoreInd"       => $v['RelMoreInd'] ?? "", 
            "LastName"         => "",
            "CustTypCd"        => "",
            "ToEntKey"         => $v['ToEntKey'] ?? "", 
        ];

        $payload = [
            "TPCIOperation" => [
                "TSRqHdr"    => $this->commonHeader(),
                "PartyRelRq" => $partyRelRq,
            ],
        ];

        /** @var \App\Http\Controllers\ErrorController $errCtrl */
        $errCtrl = app(\App\Http\Controllers\ErrorController::class);

        // Upstream call
        $response = $errCtrl->callUpstream($this->partyRelUrl, $payload, true, 15);

        if (method_exists($response, 'successful') && !$response->successful()) {
            return response()->view('party-rel-table', [
                'header'   => ['ProcessMessage' => 'Unable to reach PartyRel upstream. HTTP '.$response->status()],
                'summary'  => [
                    'Ctl1' => $v['Ctl1'], 'Ctl2' => $v['Ctl2'], 'Ctl3' => $v['Ctl3'], 'Ctl4' => $v['Ctl4'],
                    'CustId' => $v['CustId'], 'RestOfRelatedKey' => '', 'RelMoreInd' => '',
                ],
                'accounts' => [],
                'nextCursor' => '',
                'raw'      => ['status' => $response->status(), 'body' => $response->body()],
            ], $response->status() ?: 500);
        }

        $data = $response->json();
        if (!is_array($data)) {
            return response()->view('party-rel-table', [
                'header'   => ['ProcessMessage' => 'Invalid JSON response from PartyRel upstream.'],
                'summary'  => [
                    'Ctl1' => $v['Ctl1'], 'Ctl2' => $v['Ctl2'], 'Ctl3' => $v['Ctl3'], 'Ctl4' => $v['Ctl4'],
                    'CustId' => $v['CustId'], 'RestOfRelatedKey' => '', 'RelMoreInd' => '',
                ],
                'accounts' => [],
                'nextCursor' => '',
                'raw'      => ['body' => $response->body()],
            ], 502);
        }

       
        [$tsHdr, $rows] = $errCtrl->parseOperationResponse($data, 'TPCIOperationResponse', 'PartyRelRs');
        if (is_array($rows) && array_keys($rows) !== range(0, count($rows) - 1)) {
            $rows = [$rows];
        }

        $header = [
            'MaxSeverity'    => trim((string)($data['TPCIOperationResponse']['TSRsHdr']['MaxSeverity'] ?? '')),
            'ProcessMessage' => trim((string)($data['TPCIOperationResponse']['TSRsHdr']['ProcessMessage'] ?? '')),
            'NextDay'        => trim((string)($data['TPCIOperationResponse']['TSRsHdr']['NextDay'] ?? '')),
        ];

        
        $summary = [
            'Ctl1' => $v['Ctl1'],
            'Ctl2' => $v['Ctl2'],
            'Ctl3' => $v['Ctl3'],
            'Ctl4' => $v['Ctl4'],
            'CustId' => $v['CustId'],
            'RestOfRelatedKey' => '',
            'RelMoreInd' => '',
        ];

        $displayAccounts = [];
        $nextCursor = '';
        $relMoreIndPage = '';

        foreach ($rows ?? [] as $rs) {
            // populate summary fields from response if present
            $summary['RestOfRelatedKey'] = $rs['RestOfRelatedKey'] ?? $summary['RestOfRelatedKey'];
            $summary['RelMoreInd']       = $rs['RelMoreInd'] ?? $summary['RelMoreInd'];

            $relMoreIndPage = (string)($rs['RelMoreInd'] ?? '');
            $toEntKey = $rs['ToEntKey'] ?? [];

            
            if (is_array($toEntKey) && array_keys($toEntKey) !== range(0, count($toEntKey) - 1)) {
                $toEntKey = [$toEntKey];
            }

            
            foreach ($toEntKey as $acct) {
                $acctAppl = trim((string)($acct['AcctAppl'] ?? ''));
                if ($acctAppl === '') {
                   
                    continue;
                }

                $displayAccounts[] = [
                    'ToAcctKey'    => $acct['ToAcctKey'] ?? '',
                    'AcctType'     => $acct['AcctType'] ?? '',
                    'ApplBalance1' => $acct['ApplBalance1'] ?? 0,
                    
                ];

                
                if (!empty($acct['ToAcctKey'])) {
                    $nextCursor = $acct['ToAcctKey'];
                }
            }
        }

        // If RelMoreInd == "M", provide nextCursor for the button
        if ($relMoreIndPage !== 'M') {
            $nextCursor = ''; // no more pages or end
        }

        // Return partial (HTML)
        return response()->view('party-rel-table', [
            'header'     => $header,
            'summary'    => $summary,       
            'accounts'   => $displayAccounts, 
            'nextCursor' => $nextCursor,    
            'raw'        => $data,          
        ]);
       }
}