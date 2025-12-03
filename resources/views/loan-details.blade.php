
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4>Loan Details</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            @foreach($details as $key => $value)
                <tr>
                    <th>{{ $key }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>

        <h5 class="mt-4">Delinquency Information</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Days Past Due</th>
                    <th>Boundary</th>
                    <th>Cycles Past Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($delinquency as $d)
                    <tr>
                        <td>{{ $d['DaysPastDueCounter'] }}</td>
                        <td>{{ $d['DaysPastDueBoundary'] }}</td>
                        <td>{{ $d['CyclesPastDueCounter'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
