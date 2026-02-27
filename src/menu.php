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

<!-- Navbar -->
<nav class="bg-amber-700 text-white px-6 py-4 flex items-center justify-between shadow-md">
    <h1 class="text-xl font-bold">🥖 Panineria</h1>
    <div class="flex items-center gap-4">
        <span class="text-sm">Ciao, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
        <button onclick="toggleCart()"
            class="relative bg-amber-500 hover:bg-amber-400 px-3 py-1.5 rounded-lg text-sm font-semibold transition">
            🛒 Carrello
            <span id="cart-count"
                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
        </button>
        <a href="/api/logout.php" class="text-sm hover:text-amber-200 transition">Esci</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto px-4 py-8 flex gap-6">

    <!-- Menu -->
    <main class="flex-1">
        <h2 class="text-2xl font-bold text-amber-800 mb-6">Il Nostro Menu</h2>

        <!-- Category filter -->
        <div id="categories" class="flex flex-wrap gap-2 mb-6"></div>

        <!-- Product grid -->
        <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="col-span-full text-center py-12 text-gray-400">Caricamento menu...</div>
        </div>

        <!-- Past orders -->
        <section class="mt-12">
            <h2 class="text-xl font-bold text-amber-800 mb-4">I Tuoi Ordini</h2>
            <div id="orders-list" class="space-y-3">
                <p class="text-gray-400 text-sm">Caricamento ordini...</p>
            </div>
        </section>
    </main>

    <!-- Cart Sidebar -->
    <aside id="cart-sidebar"
        class="hidden w-80 bg-white rounded-2xl shadow-lg p-5 h-fit sticky top-6">
        <h3 class="text-lg font-bold text-amber-800 mb-4">🛒 Il Tuo Carrello</h3>
        <div id="cart-items" class="space-y-3 mb-4 max-h-80 overflow-y-auto"></div>
        <div class="border-t pt-3">
            <div class="flex justify-between font-bold text-lg mb-3">
                <span>Totale</span>
                <span id="cart-total">€0.00</span>
            </div>
            <textarea id="order-notes" placeholder="Note per l'ordine (opzionale)..."
                class="w-full border border-gray-200 rounded-lg p-2 text-sm mb-3 resize-none" rows="2"></textarea>
            <button onclick="submitOrder()"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition disabled:opacity-50"
                id="btn-order">
                Invia Ordine
            </button>
        </div>
        <button onclick="toggleCart()" class="mt-3 w-full text-sm text-gray-400 hover:text-gray-600">Chiudi</button>
    </aside>
</div>

<!-- Toast notifications -->
<div id="toast" class="fixed bottom-6 right-6 hidden"></div>

<script src="/js/menu.js"></script>
</body>
</html>
