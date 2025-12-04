

  @if(!empty($error))
    <div class="alert alert-danger">
      {{ $error }}
  @else
    <div class="loan-details-title">Loan Details</div>

    @if(!empty($details) && is_array($details))
      <table class="loan-table">
        <tbody>
          @foreach($details as $key => $value)
            <tr>
              <th>{{ $key }}</th>
              <td>{{ $value ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="error-banner">No loan details returned.</div>
    @endif

    {{-- Delinquency Information --}}
    @if(!empty($delinquency) && is_array($delinquency))
      <div class="loan-details-subtitle">Delinquency Information</div>
      <table class="loan-table loan-table--grid">
        <thead>
          <tr>
            <th style="width: 33%;">Days Past Due</th>
            <th style="width: 33%;">Boundary</th>
            <th style="width: 34%;">Cycles Past Due</th>
          </tr>
        </thead>
        <tbody>
          @foreach($delinquency as $d)
            <tr>
              <td>{{ data_get($d, 'DaysPastDueCounter', '—') }}</td>
              <td>{{ data_get($d, 'DaysPastDueBoundary', '—') }}</td>
              <td>{{ data_get($d, 'CyclesPastDueCounter', '—') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endif
</div>

