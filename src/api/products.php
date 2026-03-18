<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

startSecureSession();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getPDO();

// --- Helpers ---

function attachCustomizations(PDO $pdo, array $products): array {
    if (empty($products)) return $products;

    $ids = array_column($products, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $ingredients = $pdo->prepare("SELECT product_id, name FROM product_ingredients WHERE product_id IN ($in) ORDER BY id");
    $ingredients->execute($ids);
    $extras = $pdo->prepare("SELECT product_id, name, price FROM product_extras WHERE product_id IN ($in) ORDER BY id");
    $extras->execute($ids);

    $ingMap = [];
    foreach ($ingredients->fetchAll() as $r) {
        $ingMap[$r['product_id']][] = $r['name'];
    }
    $extMap = [];
    foreach ($extras->fetchAll() as $r) {
        $extMap[$r['product_id']][] = ['name' => $r['name'], 'price' => (float)$r['price']];
    }

    foreach ($products as &$p) {
        $p['ingredients']     = $ingMap[$p['id']] ?? [];
        $p['extras']          = $extMap[$p['id']] ?? [];
        $p['variant_options'] = $p['variant_options'] ? json_decode($p['variant_options'], true) : [];
    }

    return $products;
}

function saveCustomizations(PDO $pdo, int $productId, array $ingredients, array $extras, ?string $variantOptions): void {
    $pdo->prepare('DELETE FROM product_ingredients WHERE product_id = ?')->execute([$productId]);
    $pdo->prepare('DELETE FROM product_extras WHERE product_id = ?')->execute([$productId]);

    if ($ingredients) {
        $stmt = $pdo->prepare('INSERT INTO product_ingredients (product_id, name) VALUES (?,?)');
        foreach ($ingredients as $ing) {
            $name = trim($ing);
            if ($name) $stmt->execute([$productId, $name]);
        }
    }

    if ($extras) {
        $stmt = $pdo->prepare('INSERT INTO product_extras (product_id, name, price) VALUES (?,?,?)');
        foreach ($extras as $ex) {
            $name  = trim($ex['name'] ?? '');
            $price = (float)($ex['price'] ?? 0);
            if ($name) $stmt->execute([$productId, $name, $price]);
        }
    }

    $pdo->prepare('UPDATE products SET variant_options = ? WHERE id = ?')
        ->execute([$variantOptions, $productId]);
}

// --- GET ---
if ($method === 'GET') {
    $sql      = (isset($_GET['all']) && isAdmin())
        ? 'SELECT * FROM products ORDER BY category, name'
        : 'SELECT * FROM products WHERE is_visible = 1 ORDER BY category, name';
    $products = $pdo->query($sql)->fetchAll();
    echo json_encode(attachCustomizations($pdo, $products));
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit;
}

$body = getJsonBody();

// --- POST ---
if ($method === 'POST') {
    $name  = trim($body['name'] ?? '');
    $price = (float)($body['price'] ?? 0);
    if (!$name || $price <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Nome e prezzo obbligatori']);
        exit;
    }

    $variantOptions = !empty($body['variant_options'])
        ? json_encode(array_filter(array_map('trim', $body['variant_options'])))
        : null;

    $pdo->prepare('INSERT INTO products (name, description, price, category, variant_options) VALUES (?,?,?,?,?)')
        ->execute([$name, trim($body['description'] ?? ''), $price, trim($body['category'] ?? 'Panini'), $variantOptions]);
    $id = (int)$pdo->lastInsertId();

    saveCustomizations($pdo, $id, $body['ingredients'] ?? [], $body['extras'] ?? [], $variantOptions);
    echo json_encode(['id' => $id]);
    exit;
}

// --- PUT ---
if ($method === 'PUT') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID mancante']); exit; }

    $variantOptions = !empty($body['variant_options'])
        ? json_encode(array_filter(array_map('trim', $body['variant_options'])))
        : null;

    $pdo->prepare('UPDATE products SET name=?, description=?, price=?, category=?, variant_options=? WHERE id=?')
        ->execute([trim($body['name'] ?? ''), trim($body['description'] ?? ''), (float)($body['price'] ?? 0), trim($body['category'] ?? 'Panini'), $variantOptions, $id]);

    saveCustomizations($pdo, $id, $body['ingredients'] ?? [], $body['extras'] ?? [], $variantOptions);
    echo json_encode(['ok' => true]);
    exit;
}

// --- PATCH (toggle visibilità) ---
if ($method === 'PATCH') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID mancante']); exit; }
    $pdo->prepare('UPDATE products SET is_visible=? WHERE id=?')
        ->execute([(int)($body['is_visible'] ?? 0), $id]);
    echo json_encode(['ok' => true]);
    exit;
}

// --- DELETE ---
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID mancante']); exit; }
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito']);
