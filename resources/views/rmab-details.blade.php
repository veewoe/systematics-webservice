@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@else
 
    {{-- Summary (header/status) --}}
    <h2 class="h6 mt-2">Summary</h2>
    <table class="table table-sm">
        <tbody>
        @foreach ([
            'Status'        => data_get($summary, 'Status', 'N/A'),
            'MaxSeverity'   => data_get($summary, 'MaxSeverity', 'N/A'),
            'NextDay'       => data_get($summary, 'NextDay', 'N/A'),
            'Total Accounts'=> data_get($summary, 'TotalAccounts', 0),
        ] as $label => $value)
            <tr>
                <th style="width: 30%">{{ $label }}</th>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
 
    {{-- Accounts table --}}
    @if (!empty($accounts))
        <h2 class="h6 mt-4">Customer Account Relations</h2>
        <table class="table table-bordered table-striped table-sm">
            <thead>
            <tr>
                <th style="white-space:nowrap;">Account ID</th>
                <th style="white-space:nowrap;">CTL 1/CTL 2/CTL 3/CTL 4</th>
                <th style="white-space:nowrap;">Application</th>
                <th style="white-space:nowrap;">Type</th>
                <th style="white-space:nowrap;">Product</th>
                <th style="white-space:nowrap;">Status</th>
                <th style="white-space:nowrap;">Ownership / Legal / Primary</th>
                <th style="white-space:nowrap;">Balances</th>
                <th style="white-space:nowrap;">Dates</th>
                <th style="white-space:nowrap;">Internet Banking</th>
                <th style="white-space:nowrap;">Currency</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($accounts as $a)
                <tr>
                    <td>{{ $a['Account ID'] ?? 'N/A' }}</td>
                    <td>{{ $a['Ctl1/Ctl2/Ctl3/Ctl4'] ?? 'N/A' }}</td>
                    <td>{{ $a['Application'] ?? 'N/A' }}</td>
                    <td>{{ $a['Account Type'] ?? 'N/A' }}</td>
                    <td>
                        {{ $a['Product Code'] ?? 'N/A' }}
                        @if (!empty($a['Product Description'])) / {{ $a['Product Description'] }} @endif
                    </td>
                    <td>{{ $a['Status'] ?? 'N/A' }}</td>
                    <td>{{ $a['Primary/Legal/Owner'] ?? 'N/A' }}</td>              
<td>
    <strong>Avail:</strong> {{ $a['Available Balance'] ?? '₱0.00' }}<br>
    <strong>Curr:</strong> {{ $a['Current Balance'] ?? '₱0.00' }}<br>
    <strong>Avg:</strong> {{ $a['Average Balance'] ?? '₱0.00' }}<br>
    <strong>Prn:</strong> {{ $a['Principal Balance'] ?? '₱0.00' }}<br>
    <strong>Past Due:</strong> {{ $a['Past Due Amount'] ?? '₱0.00' }}<br>
    <strong>Payment:</strong> {{ $a['Payment Amount'] ?? '₱0.00' }}<br>
    <strong>Credit Limit:</strong> {{ $a['Credit Limit'] ?? '₱0.00' }}<br>
    <strong>Avail Credit:</strong> {{ $a['Available Credit'] ?? ($a['Available Credit'] ?? $a['Available Credit'] ?? '') }}
</td>
<td>
    <strong>Opened:</strong> {{ $a['Opened Date'] ?? 'N/A' }}<br>
    <strong>Next Due:</strong> {{ $a['Next Due Date'] ?? 'N/A' }}<br>
    <strong>Added:</strong> {{ $a['Added Date'] ?? 'N/A' }}
</td>
 
                    <td>{{ $a['Internet Banking'] ?? 'N/A' }}</td>
                    <td>{{ $a['Currency'] ?? 'N/A' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-info">No accounts found.</div>
    @endif
 
@endif
 