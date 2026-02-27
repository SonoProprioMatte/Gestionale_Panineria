<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startSecureSession();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ' . ($user['role'] === 'admin' ? '/admin.php' : '/menu.php'));
                exit;
            }
            $error = 'Email o password errati.';
        }

    } elseif ($action === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name && $email && strlen($password) >= 8) {
            $pdo = getPDO();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = 'Email già registrata.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "customer")');
                $stmt->execute([$name, $email, $hash]);

                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'customer';

                header('Location: /menu.php');
                exit;
            }
        } else {
            $error = 'Compila tutti i campi. La password deve avere almeno 8 caratteri.';
        }
    }
}

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/admin.php' : '/menu.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🥖 Panineria — Accedi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-amber-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-amber-800">🥖 Panineria</h1>
            <p class="text-amber-600 mt-1">I panini più buoni della città</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="flex border-b border-gray-200" id="tabs">
                <button onclick="showTab('login')" id="tab-login"
                    class="flex-1 py-3 font-semibold text-amber-700 border-b-2 border-amber-500 bg-amber-50 transition">
                    Accedi
                </button>
                <button onclick="showTab('register')" id="tab-register"
                    class="flex-1 py-3 font-semibold text-gray-500 hover:text-amber-700 transition">
                    Registrati
                </button>
            </div>

            <!-- Login Form -->
            <form id="form-login" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition">
                    Entra
                </button>
                <p class="text-xs text-center text-gray-400">Demo admin: admin@panineria.it / password</p>
            </form>

            <!-- Register Form -->
            <form id="form-register" method="POST" class="p-6 space-y-4 hidden">
                <input type="hidden" name="action" value="register">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                    <input type="text" name="name" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password (min. 8 caratteri)</label>
                    <input type="password" name="password" minlength="8" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition">
                    Crea Account
                </button>
            </form>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.getElementById('form-login').classList.toggle('hidden', tab !== 'login');
        document.getElementById('form-register').classList.toggle('hidden', tab !== 'register');
        document.getElementById('tab-login').className = tab === 'login'
            ? 'flex-1 py-3 font-semibold text-amber-700 border-b-2 border-amber-500 bg-amber-50 transition'
            : 'flex-1 py-3 font-semibold text-gray-500 hover:text-amber-700 transition';
        document.getElementById('tab-register').className = tab === 'register'
            ? 'flex-1 py-3 font-semibold text-amber-700 border-b-2 border-amber-500 bg-amber-50 transition'
            : 'flex-1 py-3 font-semibold text-gray-500 hover:text-amber-700 transition';
    }
    </script>
</body>
</html>
