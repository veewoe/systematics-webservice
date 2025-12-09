@if (!empty($error))
    <div class="alert alert-danger">
        {{ $error }}
    </div>
@else

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4>Stop/Hold Details</h4>
    </div>

    <div class="card-body">
        
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

        {{-- Stop/Hold List --}}
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
                        <th>Status</th>
                        <th>Actions</th>
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
                            <td>{{ $row['Status'] ?? '—' }}</td>

                            {{-- Delete action --}}
                            <td>   

<form action="{{ route('stopHold.delete') }}" method="POST" class="d-inline"
      data-seq="{{ $row['Seq'] }}"
      onsubmit="return confirm('Delete stop/hold seq ' + this.dataset.seq + '? This cannot be undone.');">
    @csrf

    <input type="hidden" name="acctNo" value="{{ $details['Account ID'] ?? '' }}">
    <input type="hidden" name="sequenceNo" value="{{ $row['Seq'] }}">
    <input type="hidden" name="cbr" value="{{ $details['CBR'] ?? '' }}">
    <input type="hidden" name="cbi" value="{{ $details['CBI'] ?? '' }}">
    <input type="hidden" name="cba" value="{{ $details['CBA'] ?? '' }}">
    <input type="hidden" name="tab" value="stopHold"> 

    <button type="submit" class="btn btn-sm btn-outline-danger">
        Delete
    </button>
</form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="text-center text-muted">No stop/hold records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
