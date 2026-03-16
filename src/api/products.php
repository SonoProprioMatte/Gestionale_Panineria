<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

startSecureSession();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getPDO();

if ($method === 'GET') {
    $sql  = isAdmin() && isset($_GET['all'])
        ? 'SELECT * FROM products ORDER BY category, name'
        : 'SELECT * FROM products WHERE is_visible = 1 ORDER BY category, name';
    echo json_encode($pdo->query($sql)->fetchAll());
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit;
}

$body = getJsonBody();

if ($method === 'POST') {
    $name  = trim($body['name'] ?? '');
    $price = (float)($body['price'] ?? 0);
    if (!$name || $price <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Nome e prezzo obbligatori']);
        exit;
    }
    $pdo->prepare('INSERT INTO products (name, description, price, category) VALUES (?,?,?,?)')
        ->execute([$name, trim($body['description'] ?? ''), $price, trim($body['category'] ?? 'Panini')]);
    echo json_encode(['id' => $pdo->lastInsertId()]);
    exit;
}

$id = (int)($body['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID mancante']);
    exit;
}

if ($method === 'PUT') {
    $pdo->prepare('UPDATE products SET name=?, description=?, price=?, category=? WHERE id=?')
        ->execute([trim($body['name'] ?? ''), trim($body['description'] ?? ''), (float)($body['price'] ?? 0), trim($body['category'] ?? 'Panini'), $id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'PATCH') {
    $pdo->prepare('UPDATE products SET is_visible=? WHERE id=?')
        ->execute([(int)($body['is_visible'] ?? 0), $id]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
