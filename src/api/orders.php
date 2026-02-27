<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireLogin();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();
$userId = (int)$_SESSION['user_id'];

// GET: fetch user's orders
if ($method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT o.id, o.total, o.status, o.notes, o.created_at,
                GROUP_CONCAT(oi.quantity, "x ", oi.product_name SEPARATOR ", ") AS items
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.user_id = ?
         GROUP BY o.id
         ORDER BY o.created_at DESC
         LIMIT 20'
    );
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// POST: create new order
if ($method === 'POST') {
    $body  = getJsonBody();
    $items = $body['items'] ?? [];
    $notes = trim($body['notes'] ?? '');

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Carrello vuoto']);
        exit;
    }

    // Validate items & compute total
    $ids = array_map(fn($i) => (int)$i['id'], $items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders) AND is_visible = 1");
    $stmt->execute($ids);
    $productMap = [];
    foreach ($stmt->fetchAll() as $p) $productMap[$p['id']] = $p;

    $total = 0;
    $validItems = [];
    foreach ($items as $item) {
        $id  = (int)$item['id'];
        $qty = max(1, (int)($item['qty'] ?? 1));
        if (!isset($productMap[$id])) continue; // skip invisible/deleted products
        $total += $productMap[$id]['price'] * $qty;
        $validItems[] = ['product' => $productMap[$id], 'qty' => $qty];
    }

    if (empty($validItems)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun prodotto valido nel carrello']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, total, notes) VALUES (?,?,?)');
        $stmt->execute([$userId, round($total, 2), $notes]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?,?,?,?,?)'
        );
        foreach ($validItems as $vi) {
            $itemStmt->execute([
                $orderId,
                $vi['product']['id'],
                $vi['product']['name'],
                $vi['qty'],
                $vi['product']['price']
            ]);
        }
        $pdo->commit();
        echo json_encode(['id' => $orderId, 'total' => round($total, 2), 'message' => 'Ordine creato!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Errore creazione ordine']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
