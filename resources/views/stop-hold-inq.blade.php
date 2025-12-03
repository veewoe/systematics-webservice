
<!-- stop-hold-inq.blade.php -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4>Stop/Hold Details</h4>
    </div>

    <div class="card-body">
        {{-- TSRsHdr quick status (Process Message, Severity, NextDay) --}}
        @isset($tsHdr)
            <div class="mb-3">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="badge bg-info text-dark">
                        Status: {{ $tsHdr['ProcessMessage'] ?? '—' }}
                    </span>
                    @if(!empty($tsHdr['Severity']))
                        <span class="badge bg-secondary">
                            Severity: {{ $tsHdr['Severity'] }}
                        </span>
                    @endif
                    @if(!empty($tsHdr['NextDay']))
                        <span class="badge bg-light border">
                            Next Day: {{ $tsHdr['NextDay'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endisset

        {{-- Summary table --}}
        <div class="table-responsive">
            <table class="table table-bordered mb-4">
                @forelse($details as $key => $value)
                    <tr>
                        <th style="width: 25%;">{{ $key }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">No summary details available.</td>
                    </tr>
                @endforelse
            </table>
        </div>

        <h5 class="mt-2">Stop/Hold List</h5>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Seq</th>
                        <th>Type</th>
                        <th>SubType</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Entry Date</th>
                        <th>Issue Date</th>
                        <th>Expiration Date</th>
                        <th>Exp Days</th>
                        <th>Exp Remaining</th>
                        <th>Start Check #</th>
                        <th>End Check #</th>
                        <th>Waive Fee</th>
                        <th>Initiated By</th>
                        <th>Branch</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        <tr>
                            <td>{{ $row['Seq'] }}</td>
                            <td>{{ $row['Type'] }}</td>
                            <td>{{ $row['SubType'] }}</td>
                            <td>{{ $row['Currency'] }}</td>
                            <td>{{ $row['Amount'] }}</td>
                            <td>{{ $row['Entry Date'] }}</td>
                            <td>{{ $row['Issue Date'] }}</td>
                            <td>{{ $row['Expiration Date'] }}</td>
                            <td>{{ $row['Exp Days'] }}</td>
                            <td>{{ $row['Exp Remaining'] }}</td>
                            <td>{{ $row['Start Check #'] }}</td>
                            <td>{{ $row['End Check #'] }}</td>
                            <td>{{ $row['Waive Fee'] }}</td>
                            <td>{{ $row['Initiated By'] }}</td>
                            <td>{{ $row['Branch'] }}</td>
                            <td>{{ $row['Description'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="text-center text-muted">No stop/hold records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Optional: Transaction Status Messages (bottom card) --}}
        @isset($tsMsgs)
            <h5 class="mt-4">Transaction Status Messages</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
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
                        @forelse($tsMsgs as $m)
                            <tr>
                                <td>{{ $m['Code'] }}</td>
                                <td>{{ $m['Severity'] }}</td>
                                <td>{{ $m['Text'] }}</td>
                                <td>{{ $m['Account'] }}</td>
                                <td>{{ $m['Program'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No status messages.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endisset
    </div>
</div>
