<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'menu.php'));
    exit;
}

// Must have a pending verification in session
if (empty($_SESSION['verify_email'])) {
    header('Location: index.php');
    exit;
}

$email  = $_SESSION['verify_email'];
$name   = $_SESSION['verify_name'] ?? '';
$error  = '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Verify code ---
    if ($action === 'verify') {
        $input = trim(str_replace(' ', '', $_POST['code'] ?? ''));
        $pdo   = getPDO();

        $stmt = $pdo->prepare(
            'SELECT * FROM email_verifications
             WHERE email = ? AND expires_at > NOW() AND attempts < 5
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'Codice scaduto o troppi tentativi. Richiedi un nuovo codice.';
        } elseif ($row['code'] !== $input) {
            // Increment attempts
            $pdo->prepare('UPDATE email_verifications SET attempts = attempts + 1 WHERE id = ?')
                ->execute([$row['id']]);
            $remaining = 4 - (int)$row['attempts'];
            $error = "Codice errato. Tentativi rimanenti: {$remaining}.";
        } else {
            // Code correct — create the user
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare(
                    'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "customer")'
                );
                $ins->execute([$row['name'], $email, $row['password_hash']]);
                $userId = (int)$pdo->lastInsertId();

                // Clean up verification rows for this email
                $pdo->prepare('DELETE FROM email_verifications WHERE email = ?')->execute([$email]);

                $pdo->commit();

                // Log the user in
                session_regenerate_id(true);
                $_SESSION['user_id']   = $userId;
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_role'] = 'customer';
                unset($_SESSION['verify_email'], $_SESSION['verify_name']);

                header('Location: menu.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Errore durante la creazione dell\'account. Riprova.';
            }
        }
    }

    // --- Resend code ---
    if ($action === 'resend') {
        $pdo = getPDO();

        // Rate limit: max 3 sends per email in 10 minutes
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM email_verifications
             WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)'
        );
        $countStmt->execute([$email]);
        if ((int)$countStmt->fetchColumn() >= 3) {
            $error = 'Troppi codici richiesti. Attendi qualche minuto e riprova.';
        } else {
            $code    = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Delete previous codes for this email
            $pdo->prepare('DELETE FROM email_verifications WHERE email = ?')->execute([$email]);

            // We need the password hash — fetch from latest row before deletion is already done.
            // Instead: store hash temporarily in session (set during registration attempt)
            $hash = $_SESSION['verify_hash'] ?? '';
            if (!$hash) {
                $error = 'Sessione scaduta. Ricomincia la registrazione.';
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO email_verifications (email, name, password_hash, code, expires_at)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $ins->execute([$email, $name, $hash, $code, $expires]);

                $result = sendVerificationEmail($email, $name, $code);
                if ($result === true) {
                    $notice = 'Nuovo codice inviato! Controlla la tua email.';
                } else {
                    $error = 'Errore invio email: ' . htmlspecialchars($result);
                }
            }
        }
    }
}

// Mask email for display: mar***@gmail.com
$parts     = explode('@', $email);
$masked    = substr($parts[0], 0, 3) . '***@' . ($parts[1] ?? '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🥖 Verifica Email — Panineria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-amber-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-amber-800">🥖 Panineria</h1>
            <p class="text-amber-600 mt-1">Verifica la tua email</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">📬</span>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Controlla la tua email</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Abbiamo inviato un codice a 6 cifre a<br>
                    <strong class="text-amber-700"><?= htmlspecialchars($masked) ?></strong>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($notice): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                <?= htmlspecialchars($notice) ?>
            </div>
            <?php endif; ?>

            <!-- Code input form -->
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="verify">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 text-center">
                        Inserisci il codice di verifica
                    </label>
                    <!-- 6 separate digit inputs for UX -->
                    <div class="flex gap-2 justify-center" id="code-inputs">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                            class="digit-input w-11 h-14 text-center text-2xl font-bold border-2 border-gray-200 rounded-xl focus:border-amber-400 focus:outline-none transition"
                            autocomplete="off">
                        <?php endfor; ?>
                    </div>
                    <!-- Hidden input that collects the full code -->
                    <input type="hidden" name="code" id="code-value">
                </div>

                <button type="submit" id="btn-verify"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl transition disabled:opacity-40"
                    disabled>
                    Verifica Account
                </button>
            </form>

            <!-- Resend -->
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-400 mb-2">Non hai ricevuto l'email?</p>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" id="btn-resend"
                        class="text-amber-600 hover:text-amber-700 font-semibold text-sm transition">
                        Invia di nuovo
                    </button>
                </form>
            </div>

            <!-- Back to register -->
            <div class="mt-4 text-center">
                <a href="index.php" class="text-xs text-gray-400 hover:text-gray-600">
                    ← Torna alla registrazione
                </a>
            </div>

            <!-- Countdown timer -->
            <p class="text-center text-xs text-gray-400 mt-4">
                Il codice scade tra <span id="countdown" class="font-semibold text-amber-600">15:00</span>
            </p>
        </div>
    </div>

    <script>
    // --- Digit inputs logic ---
    const inputs  = document.querySelectorAll('.digit-input');
    const hidden  = document.getElementById('code-value');
    const btnVerify = document.getElementById('btn-verify');

    inputs.forEach((inp, idx) => {
        inp.addEventListener('input', (e) => {
            // Allow only digits
            inp.value = inp.value.replace(/\D/g, '').slice(-1);
            if (inp.value && idx < inputs.length - 1) inputs[idx + 1].focus();
            syncCode();
        });
        inp.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !inp.value && idx > 0) {
                inputs[idx - 1].focus();
                inputs[idx - 1].value = '';
                syncCode();
            }
        });
        inp.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            [...text.slice(0, 6)].forEach((ch, i) => {
                if (inputs[i]) inputs[i].value = ch;
            });
            const last = Math.min(text.length, 5);
            inputs[last].focus();
            syncCode();
        });
    });

    function syncCode() {
        const code = [...inputs].map(i => i.value).join('');
        hidden.value = code;
        btnVerify.disabled = code.length < 6;
    }

    // Auto-focus first input
    inputs[0].focus();

    // --- Countdown timer (15 min) ---
    let seconds = 15 * 60;
    const countdownEl = document.getElementById('countdown');
    const timer = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(timer);
            countdownEl.textContent = 'scaduto';
            countdownEl.classList.add('text-red-500');
            btnVerify.disabled = true;
            return;
        }
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        countdownEl.textContent = `${m}:${s}`;
    }, 1000);

    // --- Resend cooldown (60s) ---
    const btnResend = document.getElementById('btn-resend');
    let cooldown = 0;
    function startCooldown(s = 60) {
        cooldown = s;
        btnResend.disabled = true;
        const interval = setInterval(() => {
            cooldown--;
            btnResend.textContent = `Invia di nuovo (${cooldown}s)`;
            if (cooldown <= 0) {
                clearInterval(interval);
                btnResend.disabled = false;
                btnResend.textContent = 'Invia di nuovo';
            }
        }, 1000);
    }

    btnResend.closest('form').addEventListener('submit', () => startCooldown(60));

    <?php if ($notice): ?>
    startCooldown(60);
    <?php endif; ?>
    </script>
</body>
</html>
