<?php
// /var/www/html/gomoku/api_find_game.php

include 'db_connect.php';

// 1. クライアントから player_id を受け取る (JSON
$input = json_decode(file_get_contents('php://input'), true);
$player_id = $input['my_player_id'] ?? null;

if (!$player_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Player ID is required']);
    exit;
}

// 2. 待機中(waiting)のゲームを探す (P1が自分でないことも確認)
$stmt = $pdo->prepare("SELECT * FROM games WHERE status = 'waiting' AND player_1_id != ? LIMIT 1");
$stmt->execute([$player_id]);
$waiting_game = $stmt->fetch();

if ($waiting_game) {
    // 3. 待機中のゲームが見つかった場合 (自分がP2として参加)
    $game_id = $waiting_game['game_id'];
    $player_1_id = $waiting_game['player_1_id'];
    $player_2_id = $player_id;

    // ゲームを 'playing' 状態に更新し、P2と最初のターンを設定
    $stmt = $pdo->prepare("UPDATE games 
                           SET player_2_id = ?, status = 'playing', current_turn_id = ? 
                           WHERE game_id = ?");
    $stmt->execute([$player_2_id, $player_1_id, $game_id]);
    
    echo json_encode([
        'game_id' => $game_id,
        'role' => 'player_2' // あなたはP2(白)です
    ]);

} else {
    // 4. 待機中のゲームがない場合 (自分がP1として新規作成)
    $player_1_id = $player_id;
    
    // (念のため、自分がP1の待機ゲームを既に作っていないか確認)
    $stmt = $pdo->prepare("SELECT * FROM games WHERE status = 'waiting' AND player_1_id = ?");
    $stmt->execute([$player_id]);
    $my_game = $stmt->fetch();

    if ($my_game) {
        // 既に待機ゲームがある
        echo json_encode([
            'game_id' => $my_game['game_id'],
            'role' => 'player_1'
        ]);
    } else {
        // 新規作成
        $stmt = $pdo->prepare("INSERT INTO games (player_1_id, status) VALUES (?, 'waiting')");
        $stmt->execute([$player_1_id]);
        $game_id = $pdo->lastInsertId();
        
        echo json_encode([
            'game_id' => $game_id,
            'role' => 'player_1' // あなたはP1(黒)です
        ]);
    }
}
?>
