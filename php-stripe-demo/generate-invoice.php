<?php

require 'init.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Generate Invoice Logic
    if (isset($data['customer_id'], $data['product_ids'])) {
        $customer_id = $data['customer_id'];
        $product_ids = $data['product_ids'];

        try {
            // Create a new invoice
            $invoice = $stripe->invoices->create([
                'customer' => $customer_id,
            ]);

            // Add selected products as line items
            foreach ($product_ids as $price_id) {
                $stripe->invoiceItems->create([
                    'customer' => $customer_id,
                    'price' => $price_id,
                    'invoice' => $invoice->id,
                ]);
            }

            // Finalize and retrieve the invoice
            $stripe->invoices->finalizeInvoice($invoice->id);
            $invoice = $stripe->invoices->retrieve($invoice->id);

            // Respond with invoice details
            echo json_encode([
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
                'invoice_pdf' => $invoice->invoice_pdf,
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    exit;
}

// Fetch Customers and Products (For Initial Page Load)
$customers = $stripe->customers->all(['limit' => 100]);
$products = $stripe->products->all(['limit' => 100]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f6f9fc;
            color: #333;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        h1 {
            color: #6772e5;
        }
        select, input[type="checkbox"], button {
            margin: 10px 0;
            padding: 10px;
            width: 100%;
            font-size: 16px;
        }
        button {
            background-color: #6772e5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #5469d4;
        }
        .product-list label {
            display: block;
            margin-bottom: 5px;
            text-align: left;
        }
        a {
            color: #6772e5;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .results {
            margin-top: 20px;
        }
        .results a {
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Stripe Invoice</h1>

        <!-- Dropdown to select customers -->
        <label for="customers">Select Customer:</label>
        <select id="customers">
            <option value="" disabled selected>-- Select a Customer --</option>
            <?php foreach ($customers->data as $customer): ?>
                <option value="<?= $customer->id ?>">
                    <?= htmlspecialchars($customer->name ?? $customer->email) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- List of products with checkboxes -->
        <h2>Products</h2>
        <div class="product-list" id="products">
            <?php foreach ($products->data as $product): ?>
                <label>
                    <input type="checkbox" value="<?= $product->default_price ?>">
                    <?= htmlspecialchars($product->name) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Button to generate invoice -->
        <button id="generate-invoice">Generate Invoice</button>

        <!-- Results -->
        <div class="results">
            <h3>Invoice Details</h3>
            <div id="hosted-invoice-url"></div>
            <div id="invoice-pdf"></div>
        </div>
    </div>

    <script>
        document.getElementById('generate-invoice').addEventListener('click', () => {
            const customerId = document.getElementById('customers').value;
            const selectedProducts = Array.from(
                document.querySelectorAll('#products input:checked')
            ).map(checkbox => checkbox.value);

            if (!customerId) {
                alert('Please select a customer.');
                return;
            }

            if (selectedProducts.length === 0) {
                alert('Please select at least one product.');
                return;
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ customer_id: customerId, product_ids: selectedProducts })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                document.getElementById('hosted-invoice-url').innerHTML =
                    `<a href="${data.hosted_invoice_url}" target="_blank">Pay Invoice</a>`;
                document.getElementById('invoice-pdf').innerHTML =
                    `<a href="${data.invoice_pdf}" target="_blank">Download Invoice PDF</a>`;
            })
            .catch(err => console.error(err));
        });
    </script>
</body>
</html>
