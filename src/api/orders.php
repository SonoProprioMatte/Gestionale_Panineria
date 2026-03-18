<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireLogin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getPDO();
$userId = (int)$_SESSION['user_id'];

// --- GET ---
if ($method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT o.id, o.total, o.status, o.notes, o.created_at,
                GROUP_CONCAT(oi.quantity, "x ", oi.product_name ORDER BY oi.id SEPARATOR ", ") AS items
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.user_id = ?
         GROUP BY o.id
         ORDER BY o.created_at DESC
         LIMIT 20'
    );
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    // Attach customizations summary per order
    foreach ($orders as &$order) {
        $custStmt = $pdo->prepare(
            'SELECT oic.type, oic.label, oic.price
             FROM order_item_customizations oic
             JOIN order_items oi ON oi.id = oic.order_item_id
             WHERE oi.order_id = ?'
        );
        $custStmt->execute([$order['id']]);
        $order['customizations'] = $custStmt->fetchAll();
    }

    echo json_encode($orders);
    exit;
}

// --- POST ---
if ($method === 'POST') {
    $body  = getJsonBody();
    $items = $body['items'] ?? [];

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Carrello vuoto']);
        exit;
    }

    $ids  = array_map(fn($i) => (int)$i['id'], $items);
    $stmt = $pdo->prepare('SELECT id, name, price FROM products WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') AND is_visible = 1');
    $stmt->execute($ids);
    $productMap = array_column($stmt->fetchAll(), null, 'id');

    $total      = 0;
    $validItems = [];
    foreach ($items as $item) {
        $id  = (int)$item['id'];
        $qty = max(1, (int)($item['qty'] ?? 1));
        if (!isset($productMap[$id])) continue;

        // Base price
        $itemPrice = (float)$productMap[$id]['price'];

        // Add extras price
        $extras = $item['customizations']['extras'] ?? [];
        foreach ($extras as $ex) {
            $itemPrice += (float)($ex['price'] ?? 0);
        }

        $total       += $itemPrice * $qty;
        $validItems[] = [
            'product'        => $productMap[$id],
            'qty'            => $qty,
            'unit_price'     => $itemPrice,
            'customizations' => $item['customizations'] ?? [],
        ];
    }

    if (empty($validItems)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun prodotto valido']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO orders (user_id, total, notes) VALUES (?,?,?)')
            ->execute([$userId, round($total, 2), trim($body['notes'] ?? '')]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?,?,?,?,?)');
        $custStmt = $pdo->prepare('INSERT INTO order_item_customizations (order_item_id, type, label, price) VALUES (?,?,?,?)');

        foreach ($validItems as $vi) {
            $itemStmt->execute([$orderId, $vi['product']['id'], $vi['product']['name'], $vi['qty'], $vi['unit_price']]);
            $orderItemId = (int)$pdo->lastInsertId();

            $c = $vi['customizations'];

            // Removed ingredients
            foreach ($c['removed'] ?? [] as $r) {
                $custStmt->execute([$orderItemId, 'remove', $r, 0]);
            }
            // Extras
            foreach ($c['extras'] ?? [] as $ex) {
                $custStmt->execute([$orderItemId, 'extra', $ex['name'], (float)($ex['price'] ?? 0)]);
            }
            // Variant
            if (!empty($c['variant'])) {
                $custStmt->execute([$orderItemId, 'variant', $c['variant'], 0]);
            }
            // Note
            if (!empty($c['note'])) {
                $custStmt->execute([$orderItemId, 'note', $c['note'], 0]);
            }
        }

        $pdo->commit();
        echo json_encode(['id' => $orderId, 'total' => round($total, 2)]);
    } catch (Exception) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Errore creazione ordine']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
