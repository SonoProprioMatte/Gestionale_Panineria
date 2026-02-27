<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

startSecureSession();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// GET: fetch products (admin sees all, users only visible)
if ($method === 'GET') {
    $showAll = isset($_GET['all']) && isAdmin();
    $sql = $showAll
        ? 'SELECT * FROM products ORDER BY category, name'
        : 'SELECT * FROM products WHERE is_visible = 1 ORDER BY category, name';
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());
    exit;
}

// All mutation methods require admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit;
}

$body = getJsonBody();

// POST: create product
if ($method === 'POST') {
    $name  = trim($body['name'] ?? '');
    $price = (float)($body['price'] ?? 0);
    if (!$name || $price <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Nome e prezzo obbligatori']);
        exit;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO products (name, description, price, category) VALUES (?,?,?,?)'
    );
    $stmt->execute([
        $name,
        trim($body['description'] ?? ''),
        $price,
        trim($body['category'] ?? 'Panini')
    ]);
    echo json_encode(['id' => $pdo->lastInsertId(), 'message' => 'Prodotto creato']);
    exit;
}

// PUT: update product
if ($method === 'PUT') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID mancante']); exit; }
    $stmt = $pdo->prepare(
        'UPDATE products SET name=?, description=?, price=?, category=? WHERE id=?'
    );
    $stmt->execute([
        trim($body['name'] ?? ''),
        trim($body['description'] ?? ''),
        (float)($body['price'] ?? 0),
        trim($body['category'] ?? 'Panini'),
        $id
    ]);
    echo json_encode(['message' => 'Prodotto aggiornato']);
    exit;
}

// PATCH: toggle visibility
if ($method === 'PATCH') {
    $id         = (int)($body['id'] ?? 0);
    $is_visible = (int)($body['is_visible'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID mancante']); exit; }
    $stmt = $pdo->prepare('UPDATE products SET is_visible=? WHERE id=?');
    $stmt->execute([$is_visible, $id]);
    echo json_encode(['message' => 'Visibilità aggiornata']);
    exit;
}

// DELETE: remove product
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID mancante']); exit; }
    $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Prodotto eliminato']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
