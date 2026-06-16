<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$secret = trim((string) ($paymentConfig['callback_secret'] ?? ''));
$providedSecret = trim((string) ($_GET['secret'] ?? $_POST['secret'] ?? ($_SERVER['HTTP_X_CALLBACK_SECRET'] ?? '')));

if ($secret !== '' && !hash_equals($secret, $providedSecret)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Unauthorized callback secret']);
    exit;
}

$rawBody = (string) file_get_contents('php://input');
$payload = [];

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
if (str_contains($contentType, 'application/json') && $rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (empty($payload)) {
    $payload = $_POST;
}

if (empty($payload)) {
    $payload = $_GET;
}

$normalized = [
    'source' => (string) ($payload['source'] ?? ($payload['gateway'] ?? 'manual')),
    'payment_id' => (string) ($payload['payment_id'] ?? ($payload['razorpay_payment_id'] ?? '')),
    'order_id' => (string) ($payload['order_id'] ?? ($payload['razorpay_order_id'] ?? '')),
    'transaction_ref' => (string) ($payload['transaction_ref'] ?? ($payload['order_id'] ?? '')),
    'amount' => $payload['amount'] ?? 0,
    'amount_in_paise' => $payload['amount_in_paise'] ?? ($payload['amount'] ?? 0),
    'currency' => (string) ($payload['currency'] ?? 'INR'),
    'method' => (string) ($payload['method'] ?? 'UPI'),
    'status' => (string) ($payload['status'] ?? 'pending'),
    'donor_name' => (string) ($payload['donor_name'] ?? ($payload['name'] ?? '')),
    'donor_email' => (string) ($payload['donor_email'] ?? ($payload['email'] ?? '')),
    'user_id' => $payload['user_id'] ?? null,
    'notes' => (string) ($payload['notes'] ?? 'Captured by callback endpoint'),
];

$ok = create_callback_donation($normalized + ['raw_payload' => $payload]);

header('Content-Type: application/json; charset=utf-8');
if ($ok) {
    echo json_encode(['ok' => true, 'message' => 'Donation callback captured']);
} else {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Unable to capture callback payload']);
}
