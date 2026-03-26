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

<nav class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between shadow relative">
    <h1 class="text-xl font-bold">🥖 Pannello Admin</h1>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-300 hidden sm:block">Benvenuto, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
        <!-- Bottone profilo -->
        <button id="profile-btn" onclick="toggleProfile()"
            class="w-9 h-9 rounded-full overflow-hidden border-2 border-white/20 hover:border-white/60 transition flex items-center justify-center bg-gray-700">
        </button>
    </div>
</nav>

<?php include __DIR__ . '/profile_panel.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
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

    <section id="section-orders">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-bold text-gray-800">Ordini</h2>
                <div class="flex gap-2 ml-4">
                    <button id="orders-tab-active" onclick="showOrdersTab('active')"
                        class="px-4 py-1.5 rounded-lg text-sm font-semibold bg-amber-500 text-white transition">
                        Attivi
                    </button>
                    <button id="orders-tab-archive" onclick="showOrdersTab('archive')"
                        class="px-4 py-1.5 rounded-lg text-sm font-semibold bg-white text-gray-500 hover:bg-gray-50 transition border border-gray-200">
                        Archivio
                    </button>
                </div>
            </div>
            <button onclick="showOrdersTab(document.getElementById('orders-tab-active').classList.contains('bg-amber-500') ? 'active' : 'archive')"
                class="text-sm text-amber-600 hover:text-amber-700">↻ Aggiorna</button>
        </div>
        <div id="orders-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <p class="text-gray-400">Caricamento...</p>
        </div>
    </section>

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

<!-- Modal prodotto -->
<div id="modal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <h3 id="modal-title" class="text-lg font-bold text-gray-800 mb-4">Nuovo Prodotto</h3>
            <form id="product-form" class="space-y-4">
                <input type="hidden" id="product-id">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prezzo (€) *</label>
                        <input type="number" id="product-price" step="0.50" min="0" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                        <textarea id="product-description" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none resize-none"></textarea>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Ingredienti rimovibili</label>
                        <button type="button" onclick="addIngredient()"
                            class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 px-2 py-1 rounded font-medium transition">+ Aggiungi</button>
                    </div>
                    <div id="ingredients-list" class="space-y-2"></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Extra a pagamento</label>
                        <button type="button" onclick="addExtra()"
                            class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 px-2 py-1 rounded font-medium transition">+ Aggiungi</button>
                    </div>
                    <div id="extras-list" class="space-y-2"></div>
                    <p class="text-xs text-gray-400 mt-1">Nome extra + prezzo aggiuntivo (0 = gratuito)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Opzioni variante <span class="text-gray-400 font-normal">(es: Naturale, Frizzante)</span>
                    </label>
                    <input type="text" id="product-variants" placeholder="Opzione1, Opzione2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Separale con virgola. Lascia vuoto se non necessario.</p>
                </div>

                <!-- Immagine prodotto -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700">Immagine prodotto</label>
                        <label class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 px-2 py-1 rounded font-medium transition cursor-pointer">
                            + Carica nuova
                            <input type="file" id="img-upload-input" accept="image/*" class="hidden" onchange="uploadProductImage()">
                        </label>
                    </div>
                    <!-- Anteprima immagine corrente -->
                    <div id="img-current-preview" class="mb-2 bg-gray-50 rounded-lg min-h-10">
                        <p class="text-xs text-gray-400 text-center py-4">Nessuna immagine</p>
                    </div>
                    <button type="button" id="img-remove-btn" onclick="removeCurrentImage()"
                        class="hidden text-xs text-red-500 hover:text-red-700 mb-2">
                        × Rimuovi immagine
                    </button>
                    <!-- Libreria immagini -->
                    <p class="text-xs text-gray-500 mb-1">Oppure scegli dalla libreria:</p>
                    <div id="img-library-grid" class="grid grid-cols-4 gap-1.5 max-h-32 overflow-y-auto">
                        <p class="text-xs text-gray-400 col-span-full text-center py-2">Caricamento...</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-lg transition">Salva</button>
                    <button type="button" onclick="closeModal()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 rounded-lg transition">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="toast" class="fixed bottom-6 right-6 hidden"></div>
<script src="js/admin.js"></script>
<script src="js/profile.js"></script>
</body>
</html>
