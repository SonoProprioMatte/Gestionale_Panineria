// =============================================
// CART STATE (stored in memory / localStorage)
// =============================================
let cart = JSON.parse(localStorage.getItem('panineria_cart') || '[]');
let allProducts = [];

function saveCart() {
    localStorage.setItem('panineria_cart', JSON.stringify(cart));
}

// =============================================
// LOAD PRODUCTS FROM API
// =============================================
async function loadMenu() {
    try {
        const res = await fetch('/api/products.php');
        if (!res.ok) throw new Error('Errore server');
        allProducts = await res.json();
        renderCategories();
        renderProducts(allProducts);
    } catch (e) {
        document.getElementById('product-grid').innerHTML =
            '<p class="col-span-full text-center text-red-500">Impossibile caricare il menu. Riprova.</p>';
    }
}

function renderCategories() {
    const cats = [...new Set(allProducts.map(p => p.category))];
    const el = document.getElementById('categories');
    el.innerHTML = `<button onclick="renderProducts(allProducts)"
        class="px-3 py-1 bg-amber-500 text-white rounded-full text-sm font-medium">Tutti</button>`;
    cats.forEach(cat => {
        el.innerHTML += `<button onclick="filterByCategory('${cat}')"
            class="px-3 py-1 bg-white border border-amber-300 text-amber-700 rounded-full text-sm hover:bg-amber-50 transition">${cat}</button>`;
    });
}

function filterByCategory(cat) {
    renderProducts(allProducts.filter(p => p.category === cat));
}

function renderProducts(products) {
    const grid = document.getElementById('product-grid');
    if (!products.length) {
        grid.innerHTML = '<p class="col-span-full text-center text-gray-400">Nessun prodotto disponibile.</p>';
        return;
    }
    grid.innerHTML = products.map(p => `
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden flex flex-col">
            <div class="bg-amber-100 h-36 flex items-center justify-center text-5xl">
                ${getCategoryEmoji(p.category)}
            </div>
            <div class="p-4 flex flex-col flex-1">
                <h3 class="font-bold text-gray-800">${escHtml(p.name)}</h3>
                <p class="text-sm text-gray-500 mt-1 flex-1">${escHtml(p.description || '')}</p>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-amber-700 font-bold text-lg">€${parseFloat(p.price).toFixed(2)}</span>
                    <button onclick="addToCart(${p.id}, '${escHtml(p.name)}', ${p.price})"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition">
                        + Aggiungi
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function getCategoryEmoji(cat) {
    const map = { 'Panini': '🥖', 'Burger': '🍔', 'Sandwich': '🥪', 'Piadine': '🫓', 'Bevande': '🥤', 'Sfiziosità': '🍟' };
    return map[cat] || '🍽️';
}

// =============================================
// CART FUNCTIONS
// =============================================
function addToCart(id, name, price) {
    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price: parseFloat(price), qty: 1 });
    }
    saveCart();
    renderCart();
    showToast(`${name} aggiunto al carrello!`);
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    saveCart();
    renderCart();
}

function changeQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) removeFromCart(id);
    else { saveCart(); renderCart(); }
}

function renderCart() {
    const container = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const countEl = document.getElementById('cart-count');

    const totalItems = cart.reduce((s, i) => s + i.qty, 0);
    const totalPrice = cart.reduce((s, i) => s + i.price * i.qty, 0);

    countEl.textContent = totalItems;
    countEl.classList.toggle('hidden', totalItems === 0);
    totalEl.textContent = `€${totalPrice.toFixed(2)}`;

    if (!cart.length) {
        container.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">Il carrello è vuoto</p>';
        return;
    }

    container.innerHTML = cart.map(item => `
        <div class="flex items-center justify-between gap-2">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">${escHtml(item.name)}</p>
                <p class="text-xs text-amber-700">€${(item.price * item.qty).toFixed(2)}</p>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="changeQty(${item.id}, -1)" class="w-6 h-6 bg-gray-100 rounded text-gray-600 hover:bg-gray-200">−</button>
                <span class="w-6 text-center text-sm">${item.qty}</span>
                <button onclick="changeQty(${item.id}, 1)" class="w-6 h-6 bg-gray-100 rounded text-gray-600 hover:bg-gray-200">+</button>
                <button onclick="removeFromCart(${item.id})" class="w-6 h-6 bg-red-100 rounded text-red-500 hover:bg-red-200 ml-1">×</button>
            </div>
        </div>
    `).join('');
}

function toggleCart() {
    document.getElementById('cart-sidebar').classList.toggle('hidden');
}

// =============================================
// SUBMIT ORDER
// =============================================
async function submitOrder() {
    if (!cart.length) { showToast('Il carrello è vuoto!', 'error'); return; }

    const btn = document.getElementById('btn-order');
    btn.disabled = true;
    btn.textContent = 'Invio in corso...';

    try {
        const res = await fetch('/api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: cart,
                notes: document.getElementById('order-notes').value.trim()
            })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Errore');

        cart = [];
        saveCart();
        renderCart();
        document.getElementById('cart-sidebar').classList.add('hidden');
        showToast('Ordine inviato! Tienitelo pronto 🎉', 'success');
        loadOrders();
    } catch (e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Invia Ordine';
    }
}

// =============================================
// PAST ORDERS
// =============================================
async function loadOrders() {
    try {
        const res = await fetch('/api/orders.php');
        const orders = await res.json();
        const el = document.getElementById('orders-list');

        if (!orders.length) {
            el.innerHTML = '<p class="text-gray-400 text-sm">Nessun ordine effettuato ancora.</p>';
            return;
        }

        el.innerHTML = orders.map(o => `
            <div class="bg-white rounded-xl p-4 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-gray-800">Ordine #${o.id}</p>
                        <p class="text-sm text-gray-500">${new Date(o.created_at).toLocaleString('it-IT')}</p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusBadge(o.status)}">
                        ${statusLabel(o.status)}
                    </span>
                </div>
                <p class="text-amber-700 font-bold mt-2">Totale: €${parseFloat(o.total).toFixed(2)}</p>
                ${o.items ? `<p class="text-xs text-gray-400 mt-1">${o.items}</p>` : ''}
            </div>
        `).join('');
    } catch (e) {
        document.getElementById('orders-list').innerHTML = '<p class="text-red-400 text-sm">Errore nel caricamento ordini.</p>';
    }
}

function statusLabel(s) {
    return { in_attesa: 'In attesa', in_preparazione: 'In preparazione', pronto: '✅ Pronto!', consegnato: 'Consegnato' }[s] || s;
}

function statusBadge(s) {
    return { in_attesa: 'bg-yellow-100 text-yellow-700', in_preparazione: 'bg-blue-100 text-blue-700', pronto: 'bg-green-100 text-green-700', consegnato: 'bg-gray-100 text-gray-600' }[s] || '';
}

// =============================================
// UTILITIES
// =============================================
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.className = `fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg text-white font-medium transition-all ${type === 'error' ? 'bg-red-500' : 'bg-green-600'}`;
    toast.textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

// =============================================
// INIT
// =============================================
renderCart();
loadMenu();
loadOrders();
