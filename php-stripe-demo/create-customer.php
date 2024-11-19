<?php

require 'init.php'; // Assuming your Stripe initialization is in init.php

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $customer_id = $_POST['customer_id'];
    $product_ids = $_POST['product_ids'] ?? []; // Optional, default to empty array

    if (empty($customer_id) || empty($product_ids)) {
        // Redirect back with an error message if inputs are missing
        header('Location: generate-invoice.html?status=error&message=' . urlencode('Customer and Products are required.'));
        exit();
    }

    try {
        // Create an invoice for the customer
        $invoice = $stripe->invoices->create([
            'customer' => $customer_id,
        ]);

        // Attach each product as a line item to the invoice
        foreach ($product_ids as $price_id) {
            $stripe->invoiceItems->create([
                'customer' => $customer_id,
                'price' => $price_id,
                'invoice' => $invoice->id,
            ]);
        }

        // Finalize the invoice
        $stripe->invoices->finalizeInvoice($invoice->id);

        // Retrieve finalized invoice details
        $invoice = $stripe->invoices->retrieve($invoice->id);

        // Redirect back to the form with a success message and invoice details
        header('Location: generate-invoice.html?status=success&hosted_invoice_url=' . urlencode($invoice->hosted_invoice_url) . '&invoice_pdf=' . urlencode($invoice->invoice_pdf));
        exit();

    } catch (\Stripe\Exception\ApiErrorException $e) {
        // Redirect back to the form with an error message
        header('Location: generate-invoice.html?status=error&message=' . urlencode($e->getMessage()));
        exit();
    }
}
