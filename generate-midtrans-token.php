<?php
header('Content-Type: application/json'); // PASTIKAN HEADER ADA DI AWAL

require __DIR__ . '/../vendor/autoload.php'; // Pastikan PHPMailer sudah diinstal via Composer

\Midtrans\Config::$serverKey = 'YOUR_SERVER_KEY';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Ambil data dari request JSON
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Log data masuk untuk debugging
file_put_contents('debug_log.txt', "Raw Data: " . $rawData . PHP_EOL, FILE_APPEND);
file_put_contents('debug_log.txt', "Decoded Data: " . json_encode($data) . PHP_EOL, FILE_APPEND);
file_put_contents('debug_log.txt', "🔹 FINAL Params ke Midtrans: " . json_encode($params) . PHP_EOL, FILE_APPEND);
error_log("🔹 Decoded Data di Backend: " . json_encode($data));


// Validasi input
if (!isset($data['total_payment']) || !is_numeric($data['total_payment'])) {
    echo json_encode(["error" => "Invalid total_payment"]);
    exit;
}

$desain = isset($data['desain']) ? $data['desain'] : 'Tanpa Desain';
$domain = isset($data['domain']) ? $data['domain'] : 'Tanpa Domain';

$deskripsi_produk = 'Paket ' . ucfirst($data['paket']) . ' - ' . ucfirst($desain) . ' - ' . $domain;
// Persiapkan parameter transaksi
$params = [
    'transaction_details' => [
        'order_id' => 'INV-' . time() . '-' . rand(1000, 9999),
        'gross_amount' => (int) $data['total_payment']
    ],
    'customer_details' => [
        'first_name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone']
    ],
    'item_details' => [
        [
            'id' => 'PKT-' . strtoupper($data['paket']) . '-' . time(), // ID unik paket
            'price' => (int) $data['total_payment'], // Harga dari total_payment
            'quantity' => 1, // Selalu 1
            'name' => 'Paket ' . ucfirst($data['paket']) . ' - ' . ucfirst($data['desain']) . ' - ' . $data['domain'] // Gabungan Paket, Desain, dan Domain
        ]
    ]
];


// Log parameter transaksi sebelum request ke Midtrans
file_put_contents('debug_log.txt', "Params: " . json_encode($params) . PHP_EOL, FILE_APPEND);

try {
    // Kirim request ke Midtrans
    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
    // Log sukses
    file_put_contents('debug_log.txt', "✅ Snap Token: " . $snapToken . PHP_EOL, FILE_APPEND);
    
    // Kirim response JSON
    echo json_encode(["token" => $snapToken, "order_id" => $params['transaction_details']['order_id']]);
} catch (Exception $e) {
    // Log error
    file_put_contents('debug_log.txt', "❌ Midtrans Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // Kirim error response JSON
    echo json_encode(["error" => $e->getMessage()]);
}
error_log("✅ Deskripsi Produk: " . $deskripsi_produk);
exit;
