<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🥖 Admin — Panineria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<nav class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between shadow">
    <h1 class="text-xl font-bold">🥖 Pannello Admin</h1>
    <div class="flex items-center gap-4">
        <span class="text-sm text-gray-300">Benvenuto, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
        <a href="/api/logout.php" class="text-sm text-gray-400 hover:text-white transition">Esci</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Tabs -->
    <div class="flex gap-3 mb-6">
        <button onclick="showSection('orders')" id="tab-orders"
            class="px-5 py-2 bg-amber-500 text-white rounded-lg font-semibold shadow transition">
            📋 Ordini
        </button>
        <button onclick="showSection('products')" id="tab-products"
            class="px-5 py-2 bg-white text-gray-600 rounded-lg font-semibold shadow hover:bg-gray-50 transition">
            🍔 Menu
        </button>
    </div>

    <!-- ORDERS SECTION -->
    <section id="section-orders">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Ordini in Tempo Reale</h2>
            <button onclick="loadOrders()" class="text-sm text-amber-600 hover:text-amber-700">↻ Aggiorna</button>
        </div>
        <div id="orders-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <p class="text-gray-400">Caricamento...</p>
        </div>
    </section>

    <!-- PRODUCTS SECTION -->
    <section id="section-products" class="hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">Gestione Menu</h2>
            <button onclick="openModal()" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                + Nuovo Prodotto
            </button>
        </div>
        <div id="products-container" class="bg-white rounded-xl shadow overflow-hidden">
            <p class="text-gray-400 p-6">Caricamento...</p>
        </div>
    </section>
</div>

<!-- Product Modal -->
<div id="modal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 id="modal-title" class="text-lg font-bold text-gray-800 mb-4">Nuovo Prodotto</h3>
        <form id="product-form" class="space-y-3">
            <input type="hidden" id="product-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                <input type="text" id="product-name" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <input type="text" id="product-category" value="Panini"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                <textarea id="product-description" rows="2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prezzo (€) *</label>
                <input type="number" id="product-price" step="0.50" min="0" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition">
                    Salva
                </button>
                <button type="button" onclick="closeModal()"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 rounded-lg transition">
                    Annulla
                </button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="fixed bottom-6 right-6 hidden"></div>

<script src="/js/admin.js"></script>
</body>
</html>
