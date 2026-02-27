<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireAdmin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// GET: all orders (non-consegnati in cima)
if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT o.id, o.total, o.status, o.notes, o.created_at,
                u.name AS user_name,
                GROUP_CONCAT(oi.quantity, "x ", oi.product_name ORDER BY oi.id SEPARATOR ", ") AS items
         FROM orders o
         JOIN users u ON u.id = o.user_id
         LEFT JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id
         ORDER BY FIELD(o.status, "in_attesa", "in_preparazione", "pronto", "consegnato"), o.created_at DESC
         LIMIT 100'
    );
    echo json_encode($stmt->fetchAll());
    exit;
}

// PATCH: update order status
if ($method === 'PATCH') {
    $body   = getJsonBody();
    $id     = (int)($body['id'] ?? 0);
    $status = $body['status'] ?? '';
    $valid  = ['in_attesa', 'in_preparazione', 'pronto', 'consegnato'];

    if (!$id || !in_array($status, $valid, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dati non validi']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
    $stmt->execute([$status, $id]);
    echo json_encode(['message' => 'Stato aggiornato']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
