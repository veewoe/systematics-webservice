
<!-- hold-amount-add.blade.php -->

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4>Hold Amount – Add Result</h4>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        
        <!-- Summary Section -->
        <h5 class="mb-3">Summary</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                @forelse($details as $key => $value)
                    <tr>
                        <th style="width: 30%;">{{ $key }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">No summary details available.</td>
                    </tr>
                @endforelse
            </table>
        </div>
        
        <!-- Transaction Status Messages -->
        <h5 class="mt-4">Transaction Status Messages</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Severity</th>
                        <th>Text</th>
                        <th>Account</th>
                        <th>Program</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(array_slice($messages, -1) as $m)
                        <tr>
                            <td>{{ $m['Code'] }}</td>
                            <td>
                                <span class="badge
                                    @if($m['Severity'] === 'A') bg-danger
                                    @elseif($m['Severity'] === 'E') bg-warning text-dark
                                    @elseif($m['Severity'] === 'I') bg-info text-dark
                                    @else bg-secondary
                                    @endif">
                                    {{ $m['Severity'] }}
                                </span>
                            </td>
                            <td>{{ $m['Text'] }}</td>
                            <td>{{ $m['Account'] }}</td>
                            <td>{{ $m['Program'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No status messages returned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Raw Response for Debugging -->
        @if(!empty($raw))
            <details class="mt-3">
                <summary class="text-muted">Raw Response (Debug)</summary>
                <pre class="bg-light p-3 rounded">{{ json_encode($raw, JSON_PRETTY_PRINT) }}</pre>
            </details>
        @endif

    </div>
</div>
