<?php

$curl_version = curl_version();
echo 'SSL Version: ' . $curl_version['ssl_version'] . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://tlstest.paypal.com/");
curl_setopt($ch, CURLOPT_SSLVERSION, 6);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if(!$response) { print_r(curl_error($ch)); } else { echo $response; }
