<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireAdmin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getPDO();

if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT o.id, o.total, o.status, o.notes, o.created_at,
                u.name AS user_name
         FROM orders o
         JOIN users u ON u.id = o.user_id
         ORDER BY FIELD(o.status, "in_attesa", "in_preparazione", "pronto", "consegnato"), o.created_at DESC
         LIMIT 100'
    );
    $orders = $stmt->fetchAll();

    // Attach items + customizations for each order
    $itemStmt = $pdo->prepare(
        'SELECT oi.id, oi.product_name, oi.quantity, oi.unit_price
         FROM order_items oi WHERE oi.order_id = ? ORDER BY oi.id'
    );
    $custStmt = $pdo->prepare(
        'SELECT type, label, price FROM order_item_customizations WHERE order_item_id = ? ORDER BY id'
    );

    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $items = $itemStmt->fetchAll();

        foreach ($items as &$item) {
            $custStmt->execute([$item['id']]);
            $item['customizations'] = $custStmt->fetchAll();
        }
        $order['items'] = $items;
    }

    echo json_encode($orders);
    exit;
}

if ($method === 'PATCH') {
    $body  = getJsonBody();
    $id    = (int)($body['id'] ?? 0);
    $valid = ['in_attesa', 'in_preparazione', 'pronto', 'consegnato'];

    if (!$id || !in_array($body['status'] ?? '', $valid, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dati non validi']);
        exit;
    }

    $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$body['status'], $id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
