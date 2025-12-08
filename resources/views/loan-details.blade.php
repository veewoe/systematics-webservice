
{{-- loan-details.blade.php --}}
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@else
    {{-- Example: print $details as two-column table --}}
    <table class="table table-sm">
        <tbody>
        @foreach ($details as $label => $value)
            <tr>
                <th style="width: 30%">{{ $label }}</th>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

   
@if (!empty($delinquency))
    <h2 class="h6 mt-4">Delinquency</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Days Past Due</th>
                <th>Boundary</th>
                <th>Cycles Past Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($delinquency as $row)
                <tr>
                    <td>{{ $row['DaysPastDue'] ?? 'N/A' }}</td>
                    <td>{{ $row['Boundary'] ?? 'N/A' }}</td>
                    <td>{{ $row['CyclesPastDue'] ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endif
