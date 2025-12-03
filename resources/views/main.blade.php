<!DOCTYPE html>
<html>
<head>
    <title>Systematics API POC</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre { background: #f8f9fa; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body class="container mt-4">
    <h2 class="mb-4">Systematics API POC</h2>

    <!-- Navigation -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="loan-tab" data-bs-toggle="tab" data-bs-target="#loan" type="button" role="tab">Loans Inquiry</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stopinq-tab" data-bs-toggle="tab" data-bs-target="#stopinq" type="button" role="tab">Stop/Hold Inquiry</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stopadd-tab" data-bs-toggle="tab" data-bs-target="#stopadd" type="button" role="tab">Hold Amount Add</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="holddelete-tab" data-bs-toggle="tab" data-bs-target="#holddelete" type="button" role="tab">Hold Delete</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stopall-tab" data-bs-toggle="tab" data-bs-target="#stopall" type="button" role="tab">Hold All Add</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3" id="myTabContent">
        <!-- Loans Inquiry -->
        <div class="tab-pane fade show active" id="loan" role="tabpanel">
            <form id="loanForm">@csrf
                <input class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/loans-inq','loanForm')">Submit</button>
            </form>
        </div>

        <!-- Stop/Hold Inquiry -->
        <div class="tab-pane fade" id="stopinq" role="tabpanel">
            <form id="stopInqForm">@csrf
                <input class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/stop-hold-inq','stopInqForm')">Submit</button>
            </form>
        </div>

        <!-- Hold Amount Add -->
        <div class="tab-pane fade" id="stopadd" role="tabpanel">
            <form id="holdAmountForm">@csrf
                <input class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <input class="form-control mb-2" name="StopHoldAmt" placeholder="Hold Amount" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/hold-amount-add','holdAmountForm')">Add Hold Amount</button>
            </form>
        </div>

        <!-- Hold Delete -->
        <div class="tab-pane fade" id="holddelete" role="tabpanel">
            <form id="holdDeleteForm">@csrf
                <input class="form-control mb-2" name="Ctl2" placeholder="Ctl2 (e.g., 0001)" required>
                <input class="form-control mb-2" name="Ctl3" placeholder="Ctl3 (e.g., 0000)" required>
                <input class="form-control mb-2" name="Ctl4" placeholder="Ctl4 (e.g., 1084)" required>
                <input class="form-control mb-2" name="AcctId" placeholder="Account ID (e.g., 0000070001524)" required>
                <input class="form-control mb-2" name="StopHoldSeq" placeholder="Sequence Number (e.g., 09005)" required>
                <button type="button" class="btn btn-danger" onclick="sendRequest('/hold-delete','holdDeleteForm')">Delete Hold</button>
            </form>
        </div>

        <!-- Hold All Add -->
        <div class="tab-pane fade" id="stopall" role="tabpanel">
            <form id="stopAllForm">@csrf
                <input class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/stop-hold-all-add','stopAllForm')">Submit</button>
            </form>
        </div>
    </div>

    <h3 class="mt-4">Response:</h3>
    <div id="response" class="mt-3"></div>

    <script>
    function sendRequest(url, formId) {
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        document.getElementById('response').innerHTML = '<div class="text-muted">Loading...</div>';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('response').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('response').textContent = `Error: ${err.message}`;
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
