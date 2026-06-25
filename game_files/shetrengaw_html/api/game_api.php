<?php
// SHETRENGAW — game_api.php
// Handles three actions via the 'action' parameter:
//
//   GET  game_api.php?action=state&id=SHTR-XXXX
//        Returns full current board state
//
//   POST game_api.php?action=move&id=SHTR-XXXX
//        Body: { board_state: "..." }
//        Updates board state, returns new last_updated timestamp
//
//   GET  game_api.php?action=poll&id=SHTR-XXXX&since=1234567890
//        Returns { changed: true/false, last_updated: ... }
//        Used for polling — client checks every 2 seconds

require_once 'config.php';

$action  = $_GET['action'] ?? '';
$game_id = $_GET['id'] ?? '';

if (!$game_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing game id']);
    exit;
}

$pdo = get_db();

// ── Validate game exists ───────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM games WHERE game_id = ?');
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    http_response_code(404);
    echo json_encode(['error' => 'Game not found']);
    exit;
}

// ── GET STATE ─────────────────────────────────────────
if ($action === 'state') {
    echo json_encode([
        'success'      => true,
        'game_id'      => $game['game_id'],
        'board_state'  => $game['board_state'],
        'last_updated' => (int)$game['last_updated'],
        'status'       => $game['status'],
    ]);
    exit;
}

// ── POST MOVE ─────────────────────────────────────────
if ($action === 'move') {
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

    $now    = round(microtime(true) * 1000);
    $status = isset($input['status']) ? $input['status'] : 'active';

    $stmt = $pdo->prepare(
        'UPDATE games SET board_state = ?, last_updated = ?, status = ? WHERE game_id = ?'
    );
    $stmt->execute([$input['board_state'], $now, $status, $game_id]);

    echo json_encode([
        'success'      => true,
        'last_updated' => $now,
    ]);
    exit;
}

// ── POLL ─────────────────────────────────────────────
if ($action === 'poll') {
    $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
    $changed = ((int)$game['last_updated']) > $since;

    echo json_encode([
        'success'      => true,
        'changed'      => $changed,
        'last_updated' => (int)$game['last_updated'],
        'status'       => $game['status'],
    ]);
    exit;
}

// ── UNKNOWN ACTION ────────────────────────────────────
http_response_code(400);
echo json_encode(['error' => 'Unknown action: ' . $action]);
