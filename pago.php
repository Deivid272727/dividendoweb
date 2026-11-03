<?php
// Datos de la API
$clientId = 'AfYcHqjYvqK82B1Q302uuZg0LqEj7Omy2AG93jqbbNzjgemfJnQMBhNV8YsH270OuZphgqnW-WrTobcb';
$secret = 'EOjpSEpfFgfBOUcVcQE8e3tvXhH8MoTQYGFPe0gxjhlJmEP-aCbTDyuhmJauAI4RiGMMxgDzYeUp1GVI';

// Obtén token de acceso
$auth = base64_encode("$clientId:$secret");
$ch = curl_init('https://api.sandbox.paypal.com/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Basic $auth",
  "Accept: application/json",
  "Accept-Language: en_US",
]);
$response = json_decode(curl_exec($ch));
$accessToken = $response->access_token;

// Crear la transacción
$paymentData = [
  "intent" => "sale",
  "redirect_urls" => [
    "return_url" => "http://tu-sitio.com/resultado.php",
    "cancel_url" => "http://tu-sitio.com/cancelado.php",
  ],
  "payer" => [
    "payment_method" => "paypal",
  ],
  "transactions" => [
    [
      "amount" => [
        "total" => "10.00",
        "currency" => "USD",
      ],
      "description" => "Compra de ejemplo",
    ],
  ],
];

$ch = curl_init('https://api.sandbox.paypal.com/v1/payments/payment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "Authorization: Bearer $accessToken",
]);

$result = json_decode(curl_exec($ch));
$approvalLink = null;

foreach ($result->links as $link) {
  if ($link->rel === 'approval_url') {
    $approvalLink = $link->href;
    break;
  }
}

// Redirige al usuario a PayPal
header("Location: $approvalLink");
exit;
?>