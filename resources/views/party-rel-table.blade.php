
{{-- resources/views/party-rel-table.blade.php --}}

@php
  // Expected from controller:
  // $header: ['ProcessMessage', 'MaxSeverity', 'NextDay']
  // $summary: ['Ctl1','Ctl2','Ctl3','Ctl4','CustId','RestOfRelatedKey','RelMoreInd']
  // $accounts: [ ['ToAcctKey','AcctType','ApplBalance1'], ... ]
  // $nextCursor: string (next ToEntKey when RelMoreInd == 'M')
  // $raw: raw JSON (optional)
  $header     = $header     ?? ['ProcessMessage' => '', 'MaxSeverity' => '', 'NextDay' => ''];
  $summary    = $summary    ?? ['Ctl1'=>'', 'Ctl2'=>'', 'Ctl3'=>'', 'Ctl4'=>'', 'CustId'=>'', 'RestOfRelatedKey'=>'', 'RelMoreInd'=>''];
  $accounts   = $accounts   ?? [];
  $nextCursor = $nextCursor ?? '';
  $raw        = $raw        ?? null;
@endphp

{{-- Status message --}}
@if(!empty(trim($header['ProcessMessage'] ?? '')))
  <div class="mb-2 small text-muted">{{ trim($header['ProcessMessage']) }}</div>
@endif

{{-- Summary fields requested --}}
<div class="border rounded p-2 mb-3">
  <div class="row g-2">
    <div class="col-sm-6"><strong>Ctl1:</strong> {{ $summary['Ctl1'] }}</div>
    <div class="col-sm-6"><strong>Ctl2:</strong> {{ $summary['Ctl2'] }}</div>
    <div class="col-sm-6"><strong>Ctl3:</strong> {{ $summary['Ctl3'] }}</div>
    <div class="col-sm-6"><strong>Ctl4:</strong> {{ $summary['Ctl4'] }}</div>
    <div class="col-sm-6"><strong>Customer ID:</strong> <span class="font-monospace">{{ $summary['CustId'] }}</span></div>
    <div class="col-sm-6"><strong>Relationship More Indicator:</strong> {{ $summary['RelMoreInd'] }}</div>
    <div class="col-12"><strong>Rest Of Related Key:</strong> <span class="font-monospace">{{ $summary['RestOfRelatedKey'] }}</span></div>
  </div>
</div>

{{-- Accounts table (only AcctAppl != '' were included by the controller) --}}
@if(empty($accounts))
  <div class="alert alert-info">No accounts found.</div>
@else
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle" id="partyRelTable">
      <thead>
        <tr>
          <th>To Account Key</th>
          <th>Account Type</th>
          <th class="text-end">Applied Balance 1</th>
        </tr>
      </thead>
      <tbody id="partyRelTbody">
        @foreach($accounts as $a)
          <tr>
            <td class="font-monospace">{{ $a['ToAcctKey'] ?? '' }}</td>
            <td>{{ $a['AcctType'] ?? '' }}</td>
            <td class="text-end">{{ isset($a['ApplBalance1']) ? number_format((float)$a['ApplBalance1'], 2) : '0.00' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

{{-- Load more (only if RelMoreInd == "M" and we have a cursor) --}}
@if(($summary['RelMoreInd'] ?? '') === 'M' && !empty($nextCursor))
  <div class="mt-3">
    <button type="button" class="btn btn-outline-primary btn-sm"
            onclick="partyRelLoadMore('{{ $nextCursor }}')">
      Load more accounts
    </button>
  </div>

  <script>
    // Adds/sets hidden inputs in the existing Party Rel form and resubmits it via your sendRequest
    function partyRelLoadMore(nextCursor) {
      const form = document.getElementById('partyRelForm');
      if (!form) return;

      // Ensure hidden inputs exist and set their values
      let relMore = form.querySelector('input[name="RelMoreInd"]');
      if (!relMore) {
        relMore = document.createElement('input');
        relMore.type = 'hidden';
        relMore.name = 'RelMoreInd';
        form.appendChild(relMore);
      }
      relMore.value = 'M';

      let toEntKey = form.querySelector('input[name="ToEntKey"]');
      if (!toEntKey) {
        toEntKey = document.createElement('input');
        toEntKey.type = 'hidden';
        toEntKey.name = 'ToEntKey';
        form.appendChild(toEntKey);
      }
      toEntKey.value = nextCursor;

      // Use your existing fetch helper to update the response area
      sendRequest('/party-rel/store', 'partyRelForm');
    }
  </script>
@endif

{{-- Raw JSON (collapsible) --}}
@if(!is_null($raw))
  <details class="mt-3">
    <summary class="small">Raw JSON (received)</summary>
    <pre class="mt-2 bg-light p-2 border rounded" style="white-space: pre-wrap;">
{{ json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
    </pre>
  </details>
@endif
