<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🥖 Menu — Panineria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-amber-50 min-h-screen">

<nav class="bg-amber-700 text-white px-6 py-4 flex items-center justify-between shadow-md relative">
    <h1 class="text-xl font-bold">🥖 Panineria</h1>
    <div class="flex items-center gap-3">
        <span class="text-sm hidden sm:block">Ciao, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
        <button onclick="toggleCart()"
            class="relative bg-amber-500 hover:bg-amber-400 px-3 py-1.5 rounded-lg text-sm font-semibold transition">
            🛒 Carrello
            <span id="cart-count"
                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
        </button>
        <!-- Bottone profilo -->
        <button id="profile-btn" onclick="toggleProfile()"
            class="w-9 h-9 rounded-full overflow-hidden border-2 border-white/40 hover:border-white transition flex items-center justify-center bg-amber-500">
        </button>
    </div>
</nav>

<?php include __DIR__ . '/profile_panel.php'; ?>

<div class="max-w-6xl mx-auto px-4 py-8 flex gap-6">
    <main class="flex-1">
        <h2 class="text-2xl font-bold text-amber-800 mb-6">Il Nostro Menu</h2>
        <div id="categories" class="flex flex-wrap gap-2 mb-6"></div>
        <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="col-span-full text-center py-12 text-gray-400">Caricamento menu...</div>
        </div>
        <section class="mt-12">
            <h2 class="text-xl font-bold text-amber-800 mb-4">I Tuoi Ordini</h2>
            <div id="orders-list" class="space-y-3">
                <p class="text-gray-400 text-sm">Caricamento ordini...</p>
            </div>
        </section>
    </main>

    <aside id="cart-sidebar" class="hidden w-80 bg-white rounded-2xl shadow-lg p-5 h-fit sticky top-6">
        <h3 class="text-lg font-bold text-amber-800 mb-4">🛒 Il Tuo Carrello</h3>
        <div id="cart-items" class="space-y-3 mb-4 max-h-80 overflow-y-auto"></div>
        <div class="border-t pt-3">
            <div class="flex justify-between font-bold text-lg mb-3">
                <span>Totale</span>
                <span id="cart-total">€0.00</span>
            </div>
            <textarea id="order-notes" placeholder="Note generali sull'ordine (opzionale)..."
                class="w-full border border-gray-200 rounded-lg p-2 text-sm mb-3 resize-none" rows="2"></textarea>
            <button onclick="submitOrder()" id="btn-order"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition disabled:opacity-50">
                Invia Ordine
            </button>
        </div>
        <button onclick="toggleCart()" class="mt-3 w-full text-sm text-gray-400 hover:text-gray-600">Chiudi</button>
    </aside>
</div>

<!-- Product Modal -->
<div id="product-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-start mb-1">
                <h3 id="pm-title" class="text-xl font-bold text-gray-800"></h3>
                <button onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none ml-4">×</button>
            </div>
            <p id="pm-desc" class="text-sm text-gray-500 mb-4"></p>

            <div id="pm-ingredients-section" class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Ingredienti — deseleziona per rimuovere:</p>
                <div id="pm-ingredients" class="space-y-2 bg-gray-50 rounded-lg p-3"></div>
            </div>

            <div id="pm-extras-section" class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Extra:</p>
                <div id="pm-extras" class="space-y-2 bg-gray-50 rounded-lg p-3"></div>
            </div>

            <div id="pm-variant-section" class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Variante:</p>
                <div id="pm-variant-btns" class="flex gap-2 flex-wrap"></div>
            </div>

            <div class="mb-5">
                <p class="text-sm font-semibold text-gray-700 mb-2">Altre richieste:</p>
                <textarea id="pm-note" rows="2" placeholder="Es: senza sale, ben cotto..."
                    class="w-full border border-gray-200 rounded-lg p-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea>
            </div>

            <div class="flex items-center justify-between border-t pt-4">
                <span class="text-lg font-bold text-amber-700" id="pm-price"></span>
                <button id="pm-confirm"
                    class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-2 rounded-xl transition">
                    Aggiungi al carrello
                </button>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="fixed bottom-6 right-6 hidden"></div>
<script src="js/menu.js"></script>
<script src="js/profile.js"></script>
</body>
</html>
