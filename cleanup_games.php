<?php
// cleanup_games.php (Cronで実行する)

include 'db_connect.php'; // DB接続設定を読み込む

try {
    // 1. 1時間以上「待機中」のゲームを削除
    // (1時間経っても P2 が参加しなかったゲーム)
    $sql1 = "DELETE FROM games 
             WHERE status = 'waiting' 
             AND created_at < (NOW() - INTERVAL 1 HOUR)";
             
    $deleted_waiting = $pdo->exec($sql1);
    

    // 2. 2時間以上「プレイ中」のまま放置されたゲームを検索
    // (最後の着手から2時間以上経過したゲーム)
    $sql_find_abandoned = "SELECT g.game_id 
                           FROM games g
                           JOIN (
                               SELECT game_id, MAX(created_at) as last_move_time
                               FROM moves
                               GROUP BY game_id
                           ) m ON g.game_id = m.game_id
                           WHERE g.status = 'playing' 
                           AND m.last_move_time < (NOW() - INTERVAL 2 HOUR)";

    $stmt = $pdo->query($sql_find_abandoned);
    $abandoned_games = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($abandoned_games) > 0) {
        // 3. 放置されたゲームに関連する「着手データ」を削除
        $in_clause = implode(',', array_fill(0, count($abandoned_games), '?'));
        
        $sql_delete_moves = "DELETE FROM moves WHERE game_id IN ($in_clause)";
        $pdo->prepare($sql_delete_moves)->execute($abandoned_games);
        
        // 4. 放置された「ゲーム本体」を削除
        $sql_delete_games = "DELETE FROM games WHERE game_id IN ($in_clause)";
        $pdo->prepare($sql_delete_games)->execute($abandoned_games);
    }
    
    // (ログを残す)
    echo "Cleanup complete. Deleted $deleted_waiting waiting games.";

} catch (PDOException $e) {
    // (エラーログを残す)
    error_log("Gomoku cleanup failed: " . $e->getMessage());
}
?>
