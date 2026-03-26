<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireAdmin();
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$pdo     = getPDO();
$baseDir = '/var/www/html/uploads/products/';
$baseUrl = '/uploads/products/';

if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

// GET — lista tutte le immagini in libreria
if ($method === 'GET') {
    $files  = glob($baseDir . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];
    $images = [];
    foreach ($files as $file) {
        $filename = basename($file);
        $images[] = [
            'filename' => $filename,
            'url'      => $baseUrl . $filename,
            'modified' => filemtime($file),
        ];
    }
    usort($images, fn($a, $b) => $b['modified'] - $a['modified']);
    echo json_encode($images);
    exit;
}

// POST — upload nuova immagine
if ($method === 'POST') {
    if (empty($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun file ricevuto']);
        exit;
    }

    $file    = $_FILES['image'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Formato non supportato. Usa JPG, PNG, GIF o WebP.']);
        exit;
    }
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File troppo grande. Massimo 5MB.']);
        exit;
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'product_' . uniqid() . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $baseDir . $filename)) {
        http_response_code(500);
        echo json_encode(['error' => 'Errore salvataggio file.']);
        exit;
    }

    echo json_encode(['ok' => true, 'filename' => $filename, 'url' => $baseUrl . $filename]);
    exit;
}

// DELETE — elimina immagine dalla libreria
if ($method === 'DELETE') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = basename($body['filename'] ?? '');

    if (!$filename) {
        http_response_code(400);
        echo json_encode(['error' => 'Filename mancante']);
        exit;
    }

    $path = $baseDir . $filename;
    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode(['error' => 'File non trovato']);
        exit;
    }

    $pdo->prepare("UPDATE products SET image_url = NULL WHERE image_url = ?")
        ->execute([$baseUrl . $filename]);

    unlink($path);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
