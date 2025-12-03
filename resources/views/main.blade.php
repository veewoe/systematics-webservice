
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Systematics API POC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Visual style for JSON/HTML response blocks */
        pre {
            background: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: .25rem;
            white-space: pre-wrap;       
            word-break: break-word;
        }
        .tab-pane {
            padding-bottom: .5rem;
        }
        .tab-pane form > :last-child {
            margin-bottom: 0 !important;
        }
        .response-title {
            margin-top: .75rem;
        }
        #response {
            margin-top: .5rem;            
        }
    </style>
</head>
<body class="container mt-4">
    <h2 class="mb-3">Systematics API POC</h2>

    <!-- Navigation -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="loan-tab" data-bs-toggle="tab" data-bs-target="#loan" type="button" role="tab">
                Loans Inquiry
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stopinq-tab" data-bs-toggle="tab" data-bs-target="#stopinq" type="button" role="tab">
                Stop/Hold Inquiry
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stopadd-tab" data-bs-toggle="tab" data-bs-target="#stopadd" type="button" role="tab">
                Hold Amount Add
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="holddelete-tab" data-bs-toggle="tab" data-bs-target="#holddelete" type="button" role="tab">
                Hold Delete
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stopall-tab" data-bs-toggle="tab" data-bs-target="#stopall" type="button" role="tab">
                Hold All Add
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="myTabContent">
        <!-- Loans Inquiry -->
        <div class="tab-pane fade show active" id="loan" role="tabpanel" aria-labelledby="loan-tab">
            <form id="loanForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/loans-inq','loanForm')">Submit</button>
            </form>
        </div>

        <!-- Stop/Hold Inquiry -->
        <div class="tab-pane fade" id="stopinq" role="tabpanel" aria-labelledby="stopinq-tab">
            <form id="stopInqForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/stop-hold-inq','stopInqForm')">Submit</button>
            </form>
        </div>

        <!-- Hold Amount Add -->
        <div class="tab-pane fade" id="stopadd" role="tabpanel" aria-labelledby="stopadd-tab">
            <form id="holdAmountForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <input type="text" class="form-control mb-2" name="StopHoldAmt" placeholder="Hold Amount" required>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="sendRequest('/hold-amount-add','holdAmountForm')">
                        Add Hold Amount
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="sendRequest('/stop-hold-all-add','holdAmountForm')">
                        Hold All Add
                    </button>
                </div>
            </form>
        </div>

        <!-- Hold Delete -->
        <div class="tab-pane fade" id="holddelete" role="tabpanel" aria-labelledby="holddelete-tab">
            <form id="holdDeleteForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2 (e.g., 0001)" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3 (e.g., 0000)" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4 (e.g., 1084)" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID (e.g., 0000070001524)" required>
                <input type="text" class="form-control mb-2" name="StopHoldSeq" placeholder="Sequence Number (e.g., 09005)" required>
                <button type="button" class="btn btn-danger" onclick="sendRequest('/hold-delete','holdDeleteForm')">Delete Hold</button>
            </form>
        </div>

        <!-- Hold All Add -->
        <div class="tab-pane fade" id="stopall" role="tabpanel" aria-labelledby="stopall-tab">
            <form id="stopAllForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-primary" onclick="sendRequest('/stop-hold-all-add','stopAllForm')">Submit</button>
            </form>
        </div>
    </div>

    <h3 class="response-title">Response:</h3>
    <div id="response"></div>

    <script>
    async function sendRequest(url, formId) {
        const form = document.getElementById(formId);
        const formData = new FormData(form);

        // Laravel CSRF token
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            formData.append('_token', csrf.getAttribute('content'));
        }

        const responseEl = document.getElementById('response');
        responseEl.innerHTML = '<div class="text-muted">Loading...</div>';

        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const contentType = res.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const json = await res.json();
                responseEl.innerHTML = '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
            } else {
                const html = await res.text();
                responseEl.innerHTML = '<div class="border rounded p-2 bg-light">' + html + '</div>';
            }
        } catch (err) {
            responseEl.textContent = `Error: ${err.message}`;
        }
    }
    </script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
