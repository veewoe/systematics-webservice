
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4>Hold All Add – Result</h4>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

@if (!empty($details['Process Message']))
    <div style="padding: 10px; border-radius: 5px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
        ✅ {{ $details['Process Message'] }}
    </div>
@else
    <div style="padding: 10px; border-radius: 5px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
        ❌ An error occured.
    </div>
@endif


        
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
                //<th>Program</th>
            </tr>
        </thead>
        <tbody>
            @forelse($messages as $m)
                <tr>
                    <td>{{ $m['Code'] ?? '' }}</td>
                    <td>
                        <span class="badge
                            @if(($m['Severity'] ?? '') === 'A') bg-danger
                            @elseif(($m['Severity'] ?? '') === 'E') bg-warning text-dark
                            @elseif(($m['Severity'] ?? '') === 'I') bg-info text-dark
                            @else bg-secondary
                            @endif">
                            {{ $m['Severity'] ?? '' }}
                        </span>
                    </td>
                    <td>{{ $m['Text'] ?? '' }}</td>
                    <td>{{ $m['Account'] ?? '' }}</td>
                   //<td>{{ $m['Program'] ?? '' }}</td>
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
                <pre class="bg-light p-3 rounded">{{ is_array($raw) ? json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $raw }}</pre>
            </details>
        @endif
    </div>
</div>
