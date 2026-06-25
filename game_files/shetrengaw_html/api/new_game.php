<?php
// SHETRENGAW — new_game.php
// Creates a new game and returns the game ID
// POST /shetrengaw/api/new_game.php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['board_state'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing board_state']);
    exit;
}

// Generate a short readable game ID: SHTR-XXXX
function generate_game_id($pdo) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No 0/O/1/I confusion
    $max_attempts = 10;
    for ($i = 0; $i < $max_attempts; $i++) {
        $code = 'SHTR-';
        for ($j = 0; $j < 4; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        // Check uniqueness
        $stmt = $pdo->prepare('SELECT id FROM games WHERE game_id = ?');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    // Fallback: longer code
    return 'SHTR-' . strtoupper(bin2hex(random_bytes(4)));
}

$pdo     = get_db();
$game_id = generate_game_id($pdo);
$now     = round(microtime(true) * 1000); // milliseconds

$stmt = $pdo->prepare(
    'INSERT INTO games (game_id, board_state, last_updated) VALUES (?, ?, ?)'
);
$stmt->execute([$game_id, $input['board_state'], $now]);

echo json_encode([
    'success' => true,
    'game_id' => $game_id,
    'last_updated' => $now,
]);
