
<div class="card shadow-sm">
    <div class="card-header bg-info text-white">
        <h4>Stop/Hold Inquiry Details</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                @foreach($details as $key => $value)
                    <tr>
                        <th>{{ $key }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <h5 class="mt-4">Stop/Hold Records</h5>
        @if(!empty($records))
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Sequence</th>
                            <th>Amount</th>
                            <th>Expiration Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $r)
                            <tr>
                                <td>{{ $r['StopHoldSeq'] ?? 'N/A' }}</td>
                                <td>₱{{ number_format($r['StopHoldAmt'] ?? 0, 2) }}</td>
                                <td>
                                    @if(!empty($r['ExpirationDt']))
                                        {{ \Carbon\Carbon::createFromFormat('Ymd', $r['ExpirationDt'])->format('M d, Y') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $r['StopHoldDesc'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">No Stop/Hold records found.</p>
        @endif
    </div>
</div>
