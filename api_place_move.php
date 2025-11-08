<?php
// /var/www/html/gomoku/api_place_move.php

include 'db_connect.php';

// 1. クライアントからのJSON入力を受け取る
$input = json_decode(file_get_contents('php://input'), true);

$game_id = $input['game_id'] ?? null;
$player_id = $input['player_id'] ?? null;
$x = $input['x'] ?? null;
$y = $input['y'] ?? null;

if (!$game_id || !$player_id || $x === null || $y === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// 2. DBから現在のゲーム状態を取得
$stmt = $pdo->prepare("SELECT * FROM games WHERE game_id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

// 3. バリデーション (検証)
if (!$game || $game['status'] !== 'playing') {
    http_response_code(403);
    echo json_encode(['error' => 'Game is not in playing state']);
    exit;
}
if ($game['current_turn_id'] !== $player_id) {
    http_response_code(403);
    echo json_encode(['error' => 'It is not your turn']);
    exit;
}

// 4. マスが空いているか検証
$stmt = $pdo->prepare("SELECT 1 FROM moves WHERE game_id = ? AND x_coord = ? AND y_coord = ?");
$stmt->execute([$game_id, $x, $y]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Cell is already occupied']);
    exit;
}

// 5. 着手をDBに保存 (★状態の更新)
$stmt = $pdo->prepare("INSERT INTO moves (game_id, player_id, x_coord, y_coord) VALUES (?, ?, ?, ?)");
$stmt->execute([$game_id, $player_id, $x, $y]);

// 6. 勝利判定ロジック
$is_win = check_for_win($pdo, $game_id, $player_id, $x, $y);

if ($is_win) {
    // 6a. 勝利した場合
    $stmt = $pdo->prepare("UPDATE games SET status = 'finished', winner_id = ? WHERE game_id = ?");
    $stmt->execute([$player_id, $game_id]);
    echo json_encode(['status' => 'win', 'message' => 'You win!']);
} else {
    // 6b. ゲーム続行の場合 (ターンを交代)
    $other_player_id = ($player_id == $game['player_1_id']) ? $game['player_2_id'] : $game['player_1_id'];
    $stmt = $pdo->prepare("UPDATE games SET current_turn_id = ? WHERE game_id = ?");
    $stmt->execute([$other_player_id, $game_id]);
    echo json_encode(['status' => 'success']);
}

exit;


// ------------------------------------
// 勝利判定関数 (ロジックの核心)
// ------------------------------------
function check_for_win($pdo, $game_id, $player_id, $last_x, $last_y) {
    $stmt = $pdo->prepare("SELECT x_coord, y_coord FROM moves WHERE game_id = ? AND player_id = ?");
    $stmt->execute([$game_id, $player_id]);
    $moves = $stmt->fetchAll();
    
    // 高速アクセスのために、[y][x] 形式のセットを作成
    $board = [];
    foreach ($moves as $move) {
        $board[$move['y_coord']][$move['x_coord']] = true;
    }

    $directions = [
        [1, 0],  // 水平
        [0, 1],  // 垂直
        [1, 1],  // 斜め (右下)
        [1, -1]  // 斜め (右上)
    ];

    foreach ($directions as $dir) {
        $count = 1; // 今置いた石
        
        // 方向 1 (例: 右)
        for ($i = 1; $i < 5; $i++) {
            $x = $last_x + $dir[0] * $i;
            $y = $last_y + $dir[1] * $i;
            if (isset($board[$y][$x])) {
                $count++;
            } else {
                break;
            }
        }
        
        // 方向 2 (例: 左)
        for ($i = 1; $i < 5; $i++) {
            $x = $last_x - $dir[0] * $i;
            $y = $last_y - $dir[1] * $i;
            if (isset($board[$y][$x])) {
                $count++;
            } else {
                break;
            }
        }
        
        if ($count >= 5) {
            return true;
        }
    }
    
    return false;
}
?>
