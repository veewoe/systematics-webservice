<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StopHoldDeleteController extends Controller
{
    private $stopHoldUrl = 'http://172.22.242.21:18000/REST/WIIRSTH/?ActionCD=D';

    public function deleteStopHold(Request $request)
    {
        // Trim first so whitespace-only won't pass
        $request->merge([
            'AcctId'      => trim((string) $request->input('AcctId', '')),
            'StopHoldSeq' => trim((string) $request->input('StopHoldSeq', '')),
            'Ctl1'        => trim((string) $request->input('Ctl1', '')),
            'Ctl2'        => trim((string) $request->input('Ctl2', '')),
            'Ctl3'        => trim((string) $request->input('Ctl3', '')),
            'Ctl4'        => trim((string) $request->input('Ctl4', '')),
        ]);

        $validated = $request->validate([
            'AcctId'      => ['required', 'string', 'max:32'],
            'StopHoldSeq' => ['required', 'string', 'max:20'],
            'Ctl1'        => ['required', 'string', 'max:10', 'regex:/^\S+$/'],
            'Ctl2'        => ['required', 'string', 'max:10', 'regex:/^\S+$/'],
            'Ctl3'        => ['required', 'string', 'max:10', 'regex:/^\S+$/'],
            'Ctl4'        => ['required', 'string', 'max:10', 'regex:/^\S+$/'],
        ], [
            'Ctl1.required' => 'Ctl1 is required.',
            'Ctl2.required' => 'Ctl2 is required.',
            'Ctl3.required' => 'Ctl3 is required.',
            'Ctl4.required' => 'Ctl4 is required.',
            'Ctl1.regex'    => 'Ctl1 must not contain spaces or be blank.',
            'Ctl2.regex'    => 'Ctl2 must not contain spaces or be blank.',
            'Ctl3.regex'    => 'Ctl3 must not contain spaces or be blank.',
            'Ctl4.regex'    => 'Ctl4 must not contain spaces or be blank.',
        ]);

        $tsRqHdr = [
            "MessageFormat"    => "",
            "EmployeeId"       => "WI000001",
            "LanguageCd"       => "EN",
            "ApplCode"         => "TS",
            "FuncSecCode"      => "I",
            "SourceCode"       => "",
            "EffectiveDate"    => now()->toIso8601String(),
            "TransTime"        => now()->toIso8601String(),
            "SuperOverride"    => "",
            "TellerOverride"   => "",
            "PhysicalLocation" => "",
            "Rebid"            => "N",
            "Reentry"          => "N",
            "Correction"       => "N",
            "Training"         => "N",
        ];

        // No defaults: take exactly what the user provided (already trimmed)
        $ctl1 = $validated['Ctl1'];
        $ctl2 = $validated['Ctl2'];
        $ctl3 = $validated['Ctl3'];
        $ctl4 = $validated['Ctl4'];

        $base = [
            "TSRqHdr"     => $tsRqHdr,
            "Ctl1"        => $ctl1,
            "Ctl2"        => $ctl2,
            "Ctl3"        => $ctl3,
            "Ctl4"        => $ctl4,
            "AcctId"      => $validated['AcctId'],
            "StopHoldSeq" => $validated['StopHoldSeq'],
        ];

        $payload = ["WIIRSTHOperation" => $base];

        try {
            $response = $this->callUpstream($this->stopHoldUrl, $payload, false);

            if (!$response->successful()) {
                return back()->withErrors([
                    'api' => "Delete failed (HTTP {$response->status()})."
                ]);
            }

            $data = $response->json();
            [$tsHdr, $rows] = $this->parseOperationResponse(
                $data,
                'WIIRSTHOperationResponse',
                'WIIRSTHRs'
            );

            $outcome = $this->upstreamOutcome($tsHdr);
            if ($outcome['kind'] === 'error') {
                return back()->withErrors(['api' => "{$outcome['code']}: {$outcome['text']}"]);
            }

            $msg = $outcome['message'] ?: ($outcome['text'] ?: "Stop/Hold seq {$validated['StopHoldSeq']} deleted.");
            return back()->with('status', $msg);

        } catch (\Throwable $e) {
            Log::error('StopHold delete exception', ['message' => $e->getMessage()]);
            return back()->withErrors(['api' => 'Unexpected error while deleting Stop/Hold.']);
        }
    }
}
