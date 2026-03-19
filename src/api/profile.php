<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireLogin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getPDO();
$userId = (int)$_SESSION['user_id'];

// GET — restituisce dati profilo corrente
if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT name, email, avatar_url, notify_login, notify_order FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Aggiorna avatar_url in sessione se cambiato
    if ($user) {
        $_SESSION['user_avatar'] = $user['avatar_url'];
    }

    echo json_encode($user);
    exit;
}

// PATCH — aggiorna preferenze notifiche e/o password
if ($method === 'PATCH') {
    $body = getJsonBody();

    // Cambio password
    if (isset($body['new_password'])) {
        $current = $body['current_password'] ?? '';
        $new     = $body['new_password']     ?? '';
        $confirm = $body['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'La nuova password deve avere almeno 8 caratteri.']);
            exit;
        }
        if ($new !== $confirm) {
            http_response_code(400);
            echo json_encode(['error' => 'Le password non coincidono.']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            http_response_code(400);
            echo json_encode(['error' => 'Password attuale errata.']);
            exit;
        }

        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]), $userId]);

        echo json_encode(['ok' => true, 'message' => 'Password aggiornata!']);
        exit;
    }

    // Preferenze notifiche
    $fields = [];
    $params = [];

    if (isset($body['notify_login'])) {
        $fields[] = 'notify_login = ?';
        $params[] = (int)(bool)$body['notify_login'];
    }
    if (isset($body['notify_order'])) {
        $fields[] = 'notify_order = ?';
        $params[] = (int)(bool)$body['notify_order'];
    }

    if ($fields) {
        $params[] = $userId;
        $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')
            ->execute($params);
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun campo da aggiornare']);
    }
    exit;
}

// POST — upload avatar
if ($method === 'POST') {
    if (empty($_FILES['avatar'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun file ricevuto']);
        exit;
    }

    $file    = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato non supportato. Usa JPG, PNG, GIF o WebP.']);
        exit;
    }
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File troppo grande. Massimo 2MB.']);
        exit;
    }

    $uploadDir = '/var/www/html/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Rimuovi vecchio avatar se esiste
    $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();
    if ($oldAvatar) {
        $oldPath = '/var/www/html' . $oldAvatar;
        if (file_exists($oldPath)) unlink($oldPath);
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Errore salvataggio file.']);
        exit;
    }

    $avatarUrl = '/uploads/avatars/' . $filename;
    $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$avatarUrl, $userId]);
    $_SESSION['user_avatar'] = $avatarUrl;

    echo json_encode(['ok' => true, 'avatar_url' => $avatarUrl]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
