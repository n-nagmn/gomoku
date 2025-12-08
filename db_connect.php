<?php

$allowed_origins = [
    'http://ubuntu.local', 
    'https://your-cloudflare-domain.com'
];

// 現在のリクエストのオリジンを取得
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    // 許可リストにあるオリジンの場合、CORSヘッダーを送信
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Headers: Content-Type"); // POSTリクエストのJSONを許可
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // 許可するメソッド
}

// OPTIONSメソッド（プリフライトリクエスト）への対応
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = 'localhost';
$db   = 'gomoku_db';
$user = 'gomoku_user';
$pass = 'password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // エラーをJSONで返す
     header('Content-Type: application/json');
     http_response_code(500);
     echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
     exit;
}

?>
