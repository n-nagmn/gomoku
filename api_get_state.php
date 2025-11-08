<?php
// /var/www/html/gomoku/api_get_state.php

include 'db_connect.php';

$game_id = $_GET['game_id'] ?? null;

if (!$game_id) {
    http_response_code(400);
    echo json_encode(['error' => 'game_id is required']);
    exit;
}

// 1. ゲームの基本情報を取得
$stmt = $pdo->prepare("SELECT * FROM games WHERE game_id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    http_response_code(404);
    echo json_encode(['error' => 'Game not found']);
    exit;
}

// 2. このゲームの全ての着手を取得
$stmt = $pdo->prepare("SELECT player_id, x_coord, y_coord FROM moves WHERE game_id = ? ORDER BY move_id ASC");
$stmt->execute([$game_id]);
$moves = $stmt->fetchAll();

// 3. 状態をJSONで返す
echo json_encode([
    'game_id' => $game['game_id'],
    'status' => $game['status'],
    'player_1_id' => $game['player_1_id'],
    'player_2_id' => $game['player_2_id'],
    'current_turn_id' => $game['current_turn_id'],
    'winner_id' => $game['winner_id'],
    'moves' => $moves
]);
?>
