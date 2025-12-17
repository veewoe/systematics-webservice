
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Systematics API POC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

      <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 
  <!-- Custom styles -->
  <style>
    /* Tabs: inactive black text / white bg; active white text / black bg */
    .nav-tabs .nav-link {
      color: #000;
      background-color: #fff;
      border-color: #dee2e6 #dee2e6 #fff;
    }
    .nav-tabs .nav-link:hover,
    .nav-tabs .nav-link:focus {
      color: #000;
      background-color: #f8f9fa;
    }
    .nav-tabs .nav-link.active,
    .nav-tabs .nav-item.show .nav-link {
      color: #fff;
      background-color: #000;
      border-color: #000 #000 #fff;
    }
 
    /* Content panel look */
    .tab-content {
      border: 1px solid #dee2e6;
      border-top: none;
      padding: 1rem;
      background-color: #fff;
    }
 
    /* Black action buttons */
    .btn-black {
      color: #fff !important;
      background-color: #000 !important;
      border-color: #000 !important;
    }
    .btn-black:hover,
    .btn-black:focus {
      color: #fff !important;
      background-color: #222 !important;
      border-color: #222 !important;
    }
 
    /* Input improvements */
    .form-label {
      font-weight: 600;
    }
    .form-control {
      border-radius: 0.5rem;     /* softer corners */
      padding: 0.6rem 0.75rem;   /* slightly larger touch area */
    }
 
    /* Helper: show small hint text */
    .form-text {
      color: #6c757d;
    }
    .container {
        width: 80%;
        max-width: none; /* remove Bootstrap's max-width limit */
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
      <button class="nav-link" id="rmab-tab" data-bs-toggle="tab" data-bs-target="#rmab" type="button" role="tab">
        RMAB
      </button>
      </li>
      
      <!-- Tab Navigation Item -->
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="party-rel-tab" data-bs-toggle="tab" data-bs-target="#party-rel-pane"
                type="button" role="tab" aria-controls="party-rel-pane" aria-selected="false">
          Party Rel
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
                <button type="button" class="btn btn-black" onclick="sendRequest('/loans-inq','loanForm')">Submit</button>
            </form>
        </div>

        <!-- Stop/Hold Inquiry -->
        <div class="tab-pane fade" id="stopinq" role="tabpanel" aria-labelledby="stopinq-tab">
            <form id="stopInqForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <button type="button" class="btn btn-black" onclick="sendRequest('/stop-hold-inq','stopInqForm')">Submit</button>
            </form>
        </div>

        <!-- Hold Amount Add -->
        <div class="tab-pane fade" id="stopadd" role="tabpanel" aria-labelledby="stopadd-tab">
            <form id="holdAmountForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4" required>
                <input type="text" class="form-control mb-2" name="AcctId" placeholder="Account ID" required>
                <input type="text" class="form-control mb-2" name="StopHoldAmt" placeholder="Hold Amount">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-black" onclick="sendRequest('/hold-amount-add','holdAmountForm')">
                        Add Hold Amount
                    </button>
                    <button type="button" class="btn btn-black" onclick="sendRequest('/hold-all-add','holdAmountForm')">
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

        <!-- RMAB Inquiry (compact) -->
    <div class="tab-pane fade" id="rmab" role="tabpanel" aria-labelledby="rmab-tab">
      <form id="rmabForm">@csrf
        <label class="form-label" for="rmabCtl2">Ctl2</label>
        <input id="rmabCtl2" type="number" inputmode="numeric" class="form-control mb-2 only-digits" name="Ctl2" placeholder="e.g., 0000" required>
 
        <label class="form-label" for="rmabCtl3">Ctl3</label>
        <input id="rmabCtl3" type="number" inputmode="numeric" class="form-control mb-2 only-digits" name="Ctl3" placeholder="e.g., 0000" required>
 
        <label class="form-label" for="rmabCtl4">Ctl4</label>
        <input id="rmabCtl4" type="number" inputmode="numeric" class="form-control mb-2 only-digits" name="Ctl4" placeholder="e.g., 0000" required>
 
        <label class="form-label" for="rmabCustId">Customer ID</label>
        <input id="rmabCustId" type="number" inputmode="numeric" class="form-control mb-3 only-digits" name="CustId" placeholder="e.g., 3597673" required>
 
        <button type="button" class="btn btn-black" onclick="sendRequest('/rmab/inquiry','rmabForm')">Submit</button>
      </form>
    </div>

    <!-- Party Rel -->
        
        <div class="tab-pane fade" id="party-rel-pane" role="tabpanel" aria-labelledby="party-rel-tab">
          <form id="partyRelForm">@csrf
                <input type="text" class="form-control mb-2" name="Ctl1" placeholder="Ctl1 (e.g., 0000)" required>
                <input type="text" class="form-control mb-2" name="Ctl2" placeholder="Ctl2 (e.g., 0001)" required>
                <input type="text" class="form-control mb-2" name="Ctl3" placeholder="Ctl3 (e.g., 0000)" required>
                <input type="text" class="form-control mb-2" name="Ctl4" placeholder="Ctl4 (e.g., 1084)" required>
                <input type="text" class="form-control mb-2" name="CustId" placeholder="Customer ID (e.g., 00000001006051)" required>
                <button type="button" class="btn btn-dark mt-2" onclick="sendRequest('/party-rel/store','partyRelForm')">
                      Submit Party Rel
                </button>

            </form>
        </div>

  </div>

    <h3 class="response-title">Response:</h3>
    <div id="response"></div>

    <script>
    async function sendRequest(url, formId) {
        const form = document.getElementById(formId);
        const formData = new FormData(form);

  // Debug: log what we're sending
  console.log('Submitting entries:');
  for (const [k, v] of formData.entries()) {
    console.log(k, v);
  }

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
