<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ErrorController extends Controller
{
    /**
     * Call the upstream service.
     * - If $preferGetFirst=true: try GET (with JSON body) first, then fallback to POST.
     * - If $preferGetFirst=false: POST only.
     */
    public function callUpstream(string $url, array $payload, bool $preferGetFirst = true, int $timeout = 10)
    {
        if ($preferGetFirst) {
            // Try GET (with JSON body) first
            $response = Http::timeout($timeout)
                ->retry(2, 200)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody(json_encode($payload), 'application/json')
                ->get($url);

            if ($response->successful()) {
                return $response;
            }

            // Fallback: POST
            return Http::timeout($timeout)
                ->retry(2, 200)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);
        }

        // POST-only path
        return Http::timeout($timeout)
            ->retry(2, 200)
            ->asJson()
            ->post($url, $payload);
    }

    
    public function parseOperationResponse(array $data, string $opResponseKey, string $rowsKey): array
    {
        $opRes = $data[$opResponseKey] ?? [];
        $tsHdr = $opRes['TSRsHdr'] ?? [];
        $rows  = $opRes[$rowsKey] ?? [];
        return [$tsHdr, $rows];
    }

   
    public function upstreamErrorMessage(?array $tsHdr, bool $rowsPresent): ?string
    {
        $tsHdr    = $tsHdr ?? [];
        $severity = strtoupper(trim((string)($tsHdr['MaxSeverity'] ?? '')));

        if ($severity === 'E' || !$rowsPresent) {
            $statusList = $tsHdr['TrnStatus'] ?? [];
            $status     = is_array($statusList) ? ($statusList[0] ?? []) : [];
            $msgCode    = trim((string)($status['MsgCode'] ?? 'UNKNOWN'));
            $msgText    = trim((string)($status['MsgText'] ?? 'Upstream error'));

            // Prefer ProcessMessage if provided
            $processMsg = trim((string)($tsHdr['ProcessMessage'] ?? ''));
            $display    = $processMsg ?: $msgText;

        return "<h3>Error Code: {$msgCode}</h3><b>{$msgText}: <br>Severity: {$tsHdr['MaxSeverity']}</b>";
        }

        return null;
    }

    
public function missingFieldsMessage(array $fields): ?string
{
    // Trim and check each field
    foreach ($fields as $key => $value) {
        if (trim((string)$value) === '') {
            // If any field is empty, return a formatted HTML message
            return "<h3>Error</h3><b>Please fill out all the required fields.</b>";
        }
    }
    return null; // All fields are filled
}

    public function redirectWithError(string $message, array $context = []): RedirectResponse
    {
        return redirect()->back()->withErrors($message)->withInput();
    }

    public function jsonError(string $message, int $status = 400, array $context = []): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'message' => $message,
            'context' => $context,
        ], $status);
    }

   
    public function handleUpstreamForView(
        string $url,
        array $payload,
        string $opResponseKey,
        string $rowsKey,
        bool $preferGetFirst = true,
        int $timeout = 10
    ) {
        $response = $this->callUpstream($url, $payload, $preferGetFirst, $timeout);

        if (method_exists($response, 'successful') && !$response->successful()) {
            return $this->redirectWithError('Unable to reach upstream service. Please try again later.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            return $this->redirectWithError('Invalid response format from upstream.');
        }

        [$tsHdr, $rows] = $this->parseOperationResponse($data, $opResponseKey, $rowsKey);
        $rowsPresent = is_array($rows) && count($rows) > 0;

        if ($err = $this->upstreamErrorMessage($tsHdr, $rowsPresent)) {
            return $this->redirectWithError($err);
        }

        return ['tsHdr' => $tsHdr, 'rows' => $rows, 'raw' => $data];
    }

   
    public function handleUpstreamForJson(
        string $url,
        array $payload,
        string $opResponseKey,
        string $rowsKey,
        bool $preferGetFirst = true,
        int $timeout = 10
    ) {
        $response = $this->callUpstream($url, $payload, $preferGetFirst, $timeout);

        if (method_exists($response, 'successful') && !$response->successful()) {
            return $this->jsonError('Unable to reach upstream service. Please try again later.', $response->status());
        }

        $data = $response->json();
        if (!is_array($data)) {
            return $this->jsonError('Invalid response format from upstream.', 502);
        }

        [$tsHdr, $rows] = $this->parseOperationResponse($data, $opResponseKey, $rowsKey);
        $rowsPresent = is_array($rows) && count($rows) > 0;

        if ($err = $this->upstreamErrorMessage($tsHdr, $rowsPresent)) {
            return $this->jsonError($err, 422);
        }

        return ['tsHdr' => $tsHdr, 'rows' => $rows, 'raw' => $data];
    }
}
