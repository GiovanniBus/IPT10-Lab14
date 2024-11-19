<?php

require "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();

$stripe = null;

try {
    
    $api_secret_key = $_ENV['STRIPE_SECRET_KEY'];

    $stripe = new \Stripe\StripeClient($api_secret_key);

} catch (Exception $e) {
    error_log($e->getMessage());
    die('An error occured: ' . $e->getMessage());
}
