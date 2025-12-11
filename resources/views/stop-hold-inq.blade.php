{{-- Success flash after delete --}}
@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" aria-live="polite">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
 
{{-- Error from API/inquiry/delete --}}
@error('api')
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" aria-live="polite">
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@enderror


@if (!empty($error))
    <div class="alert alert-danger">
        {!! $error !!}
    </div>
@else
<div id="stopHoldSection">
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4>Stop/Hold Details</h4>
    </div>

    <div class="card-body">
        

      @if (!empty($details['Transaction Status']))
    <div style="padding: 10px; border-radius: 5px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
        ✅ {{ $details['Transaction Status'] }}
    </div>
@else
    <div style="padding: 10px; border-radius: 5px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
        ❌ An error occured.
    </div>
@endif


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
                      
                        <th>Issue Date</th>
                        <th>Expiration Date</th>
                        <th>Days Remaining</th>
                        <th>Waive Fee</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                        @php
                            // Safe helpers
                            $seq = $row['Seq'] ?? '';
                            $currency = $row['Currency'] ?? '';
                            $amount = is_numeric($row['Amount'] ?? null) ? number_format((float)$row['Amount'], 2) : ($row['Amount'] ?? '');
                            $entryDate = $row['Entry Date'] ? \Carbon\Carbon::createFromFormat('Ymd', $row['Entry Date'])->format('M d, Y') : '—';
                            $issueDate = $row['Issue Date'] ? \Carbon\Carbon::createFromFormat('Ymd', $row['Issue Date'])->format('M d, Y') : '—';
                            $expDate = (preg_match('/^\d{8}$/', $row['Expiration Date']) && \Carbon\Carbon::createFromFormat('Ymd', $row['Expiration Date'])->isValid())? \Carbon\Carbon::createFromFormat('Ymd', $row['Expiration Date'])->format('M d, Y'): '—';
                            $expRemaining = ($row['Issue Date'] && $row['Expiration Date'])? \Carbon\Carbon::createFromFormat('Ymd', $row['Issue Date'])->diffInDays(\Carbon\Carbon::createFromFormat('Ymd', $row['Expiration Date']), false): '—';
                            $waiveFee = isset($row['Waive Fee']) ? ($row['Waive Fee'] ? 'Yes' : 'No') : '—';
                            $status = (empty($row['Status'])) ? 'In Place' : $row['Status'];
                            $deletable = in_array($status, ['Pending', 'Active', 'In Place'], true);
                        @endphp
 
                        <tr>
                            <td>{{ e($seq) }}</td>
                            <td>{{ e($row['Type'] ?? '—') }}</td>
                            <td>{{ e($row['SubType'] ?? '—') }}</td>
                            <td>{{ e($currency) }}</td>
                            <td>{{ $currency ? $currency.' ' : '' }}{{ e($amount) }}</td>
                            <<td>{{ e($issueDate) }}</td>
                            <td>{{ e($expDate) }}</td>
                            <td>{{ e($expRemaining) }}</td>
                            <td>
                                <span class="badge {{ $waiveFee === 'Yes' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $waiveFee }}
                                </span>
                            </td>
                            <td>{{ e($row['Description'] ?? '—') }}</td>
                            <td>
                                <span class="badge
                                    @switch($status)
                                        @case('Active') bg-primary @break
                                        @case('Deleted Today') bg-warning text-dark @break
                                        @case('Expired') bg-dark @break
                                        @case('In Place') bg-primary @break
                                        @default bg-warning text-dark
                                    @endswitch
                                ">{{ e($status ?: '—') }}</span>
                            </td>
 
                            {{-- Delete action --}}
                           
@php
    $status = $row['Status'] ?? '';
    // Disable only when status is exactly "Deleted Today"
    $disableDelete = trim($status) === 'DELETED TODAY';
@endphp
 
<td>
   
<form action="{{ route('stopHold.delete') }}" method="POST" class="d-inline"
      data-seq="{{ $row['Seq'] }}"
      onsubmit="return confirm('Delete stop/hold seq ' + this.dataset.seq + '? This cannot be undone.');">
    @csrf
 
    {{-- Use capitalized keys to match the controller --}}
    <input type="hidden" name="AcctId" value="{{ e($details['Account ID'] ?? '') }}">
    <input type="hidden" name="StopHoldSeq" value="{{ e($row['Seq'] ?? '') }}">
 
    {{-- Control codes: make sure you have all of them available --}}
    <input type="hidden" name="Ctl1" value="{{ e($details['Ctl1'] ?? $details['CBR'] ?? '') }}">
    <input type="hidden" name="Ctl2" value="{{ e($details['Ctl2'] ?? $details['CBI'] ?? '') }}">
    <input type="hidden" name="Ctl3" value="{{ e($details['Ctl3'] ?? $details['CBA'] ?? '') }}">
    <input type="hidden" name="Ctl4" value="{{ e($details['Ctl4'] ?? $details['CBU'] ?? '') }}">
 
    <button type="submit"
            class="btn btn-sm btn-outline-danger"
            {{ (strcasecmp(trim($row['Status'] ?? ''), 'Deleted Today') === 0) ? 'disabled' : '' }}
            aria-disabled="{{ (strcasecmp(trim($row['Status'] ?? ''), 'Deleted Today') === 0) ? 'true' : 'false' }}"
            title="{{ (strcasecmp(trim($row['Status'] ?? ''), 'Deleted Today') === 0) ? 'This record was deleted today and cannot be deleted again.' : 'Delete this stop/hold' }}">
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
</div>



@if(!empty($status))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        alert(@json($status));
    });
</script>
@endif

