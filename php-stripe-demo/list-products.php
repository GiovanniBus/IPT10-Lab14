<?php

require 'init.php'; // Assuming 'init.php' sets up your Stripe connection

// Fetch the list of products
$products = $stripe->products->all();

foreach ($products as $product) {
    // Display Product ID and Name
    echo "<div style='margin-bottom: 20px;'>"; // Container for each product
    echo "<h3>" . htmlspecialchars($product->name) . "</h3>"; // Product name
    
    // Display the image, if available, with consistent size
    $image = array_pop($product->images); // Get the first image, if available
    if ($image) {
        echo "<div style='margin-bottom: 10px;'>";
        echo "<img src='" . htmlspecialchars($image) . "' alt='" . htmlspecialchars($product->name) . "' style='width: 150px; height: 150px; object-fit: cover;'>";
        echo "</div>";
    } else {
        echo "No image available.<br>";
    }

    // Retrieve the price of the product
    if ($product->default_price) {
        $price = $stripe->prices->retrieve($product->default_price);

        echo "<p><strong>Price:</strong> " . strtoupper($price->currency) . " ";
        echo number_format($price->unit_amount / 100, 2); // Convert cents to dollars
        echo "</p>";
    } else {
        echo "Price not available.<br>";
    }

    echo "<hr>"; // Separator between products
    echo "</div>"; // Close product container
}
?>
