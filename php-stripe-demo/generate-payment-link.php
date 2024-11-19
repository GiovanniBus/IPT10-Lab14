<?php

require 'init.php';

// Handle AJAX requests for payment link generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Ensure that at least one product is selected
    if (isset($data['product_ids']) && count($data['product_ids']) > 0) {
        try {
            $line_items = [];

            // Create line items for the selected products
            foreach ($data['product_ids'] as $price_id) {
                array_push($line_items, [
                    'price' => $price_id,
                    'quantity' => 1,
                ]);
            }

            // Generate a payment link
            $payment_link = $stripe->paymentLinks->create([
                'line_items' => $line_items,
            ]);

            // Respond with the payment link URL
            echo json_encode(['url' => $payment_link->url]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No products selected.']);
    }
    exit;
}

// Fetch Products for the UI
$products = $stripe->products->all(['limit' => 100]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Payment Link</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
        button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #6772e5;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
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
        .results {
            margin-top: 20px;
        }
        .results a {
            color: #6772e5;
            text-decoration: none;
        }
        .results a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Payment Link</h1>

        <!-- List of products with checkboxes -->
        <h2>Select Products</h2>
        <div id="products" class="product-list">
            <?php foreach ($products->data as $product): ?>
                <label>
                    <input type="checkbox" value="<?= $product->default_price; ?>">
                    <?= htmlspecialchars($product->name); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Button to generate payment link -->
        <button id="generate-payment-link">Generate Payment Link</button>

        <!-- Redirect or display result -->
        <div class="results">
            <h3 id="payment-link-container"></h3>
        </div>
    </div>

    <script>
        document.getElementById('generate-payment-link').addEventListener('click', () => {
            const selectedProducts = Array.from(
                document.querySelectorAll('#products input:checked')
            ).map(checkbox => checkbox.value);

            if (selectedProducts.length === 0) {
                alert('Please select at least one product.');
                return;
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_ids: selectedProducts }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Redirect to payment link
                window.location.href = data.url;
            })
            .catch(err => console.error(err));
        });
    </script>
</body>
</html>
