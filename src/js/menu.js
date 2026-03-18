const CART_VERSION = 2;

function loadCart() {
    try {
        const raw = localStorage.getItem('panineria_cart');
        if (!raw) return [];
        const data = JSON.parse(raw);
        if (!data.version || data.version !== CART_VERSION) return [];
        return data.items || [];
    } catch {
        return [];
    }
}

function saveCart() {
    localStorage.setItem('panineria_cart', JSON.stringify({ version: CART_VERSION, items: cart }));
}

let cart = loadCart();
let allProducts = [];

const ACTIVE_BTN   = 'px-3 py-1 bg-amber-500 text-white rounded-full text-sm font-medium';
const INACTIVE_BTN = 'px-3 py-1 bg-white border border-amber-300 text-amber-700 rounded-full text-sm hover:bg-amber-50 transition';


// =============================================
// MENU
// =============================================
async function loadMenu() {
    try {
        const res = await fetch('api/products.php');
        if (!res.ok) throw new Error();
        allProducts = await res.json();
        renderCategories(null);
        renderProducts(allProducts);
    } catch {
        document.getElementById('product-grid').innerHTML =
            '<p class="col-span-full text-center text-red-500">Impossibile caricare il menu. Riprova.</p>';
    }
}

function renderCategories(activeCategory) {
    const el   = document.getElementById('categories');
    const cats = [...new Set(allProducts.map(p => p.category))];
    el.innerHTML = '';

    const all     = document.createElement('button');
    all.textContent = 'Tutti';
    all.className   = activeCategory === null ? ACTIVE_BTN : INACTIVE_BTN;
    all.onclick     = () => { renderCategories(null); renderProducts(allProducts); };
    el.appendChild(all);

    cats.forEach(cat => {
        const btn     = document.createElement('button');
        btn.textContent = cat;
        btn.className   = activeCategory === cat ? ACTIVE_BTN : INACTIVE_BTN;
        btn.onclick     = () => { renderCategories(cat); renderProducts(allProducts.filter(p => p.category === cat)); };
        el.appendChild(btn);
    });
}

function renderProducts(products) {
    const grid = document.getElementById('product-grid');
    if (!products.length) {
        grid.innerHTML = '<p class="col-span-full text-center text-gray-400">Nessun prodotto disponibile.</p>';
        return;
    }
    grid.innerHTML = products.map(p => `
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden flex flex-col">
            <div class="bg-amber-100 h-36 flex items-center justify-center text-5xl">${getCategoryEmoji(p.category)}</div>
            <div class="p-4 flex flex-col flex-1">
                <h3 class="font-bold text-gray-800">${escHtml(p.name)}</h3>
                <p class="text-sm text-gray-500 mt-1 flex-1">${escHtml(p.description || '')}</p>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-amber-700 font-bold text-lg">€${parseFloat(p.price).toFixed(2)}</span>
                    <button onclick="openProductModal(${p.id})"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition">
                        + Aggiungi
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function getCategoryEmoji(cat) {
    return { Panini: '🥖', Burger: '🍔', Sandwich: '🥪', Piadine: '🫓', Bevande: '🥤', Sfiziosita: '🍟' }[cat] || '🍽️';
}

// =============================================
// PRODUCT MODAL (personalizzazione)
// =============================================
function openProductModal(productId) {
    const p = allProducts.find(x => x.id === productId);
    if (!p) return;

    const modal = document.getElementById('product-modal');

    // Reset completo del modal prima di popolare
    document.getElementById('pm-ingredients').innerHTML = '';
    document.getElementById('pm-extras').innerHTML      = '';
    document.getElementById('pm-variant-btns').innerHTML = '';
    document.getElementById('pm-ingredients-section').classList.add('hidden');
    document.getElementById('pm-extras-section').classList.add('hidden');
    document.getElementById('pm-variant-section').classList.add('hidden');

    document.getElementById('pm-title').textContent = p.name;
    document.getElementById('pm-desc').textContent  = p.description || '';
    document.getElementById('pm-price').textContent = `€${parseFloat(p.price).toFixed(2)}`;
    document.getElementById('pm-note').value        = '';

    // Ingredienti rimovibili
    const ingSection = document.getElementById('pm-ingredients-section');
    const ingList    = document.getElementById('pm-ingredients');
    if (p.ingredients && p.ingredients.length) {
        ingSection.classList.remove('hidden');
        ingList.innerHTML = p.ingredients.map(ing => `
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="ingredient-check w-4 h-4 accent-amber-500" value="${escHtml(ing)}" checked>
                <span class="text-sm text-gray-700">${escHtml(ing)}</span>
            </label>
        `).join('');
    } else {
        ingSection.classList.add('hidden');
    }

    // Extra a pagamento
    const extSection = document.getElementById('pm-extras-section');
    const extList    = document.getElementById('pm-extras');
    if (p.extras && p.extras.length) {
        extSection.classList.remove('hidden');
        extList.innerHTML = p.extras.map(ex => `
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="extra-check w-4 h-4 accent-amber-500"
                    value="${escHtml(ex.name)}" data-price="${ex.price}">
                <span class="text-sm text-gray-700">${escHtml(ex.name)}</span>
                <span class="text-xs text-amber-600 ml-auto">+€${parseFloat(ex.price).toFixed(2)}</span>
            </label>
        `).join('');
        // Update price on extra change
        extList.querySelectorAll('.extra-check').forEach(cb => cb.addEventListener('change', () => updateModalPrice(p)));
    } else {
        extSection.classList.add('hidden');
    }

    // Variante (es. Naturale/Frizzante)
    const varSection = document.getElementById('pm-variant-section');
    const varBtns    = document.getElementById('pm-variant-btns');
    if (p.variant_options && p.variant_options.length) {
        varSection.classList.remove('hidden');
        varBtns.innerHTML = p.variant_options.map((v, i) => `
            <button type="button" onclick="selectVariant(this)"
                class="variant-btn px-3 py-1.5 rounded-lg text-sm font-medium border transition
                    ${i === 0 ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-300 hover:border-amber-400'}">
                ${escHtml(v)}
            </button>
        `).join('');
    } else {
        varSection.classList.add('hidden');
    }

    updateModalPrice(p);

    // Store product id on confirm button
    document.getElementById('pm-confirm').onclick = () => confirmProductModal(p);

    modal.classList.remove('hidden');
}

function selectVariant(btn) {
    btn.closest('#pm-variant-btns').querySelectorAll('.variant-btn').forEach(b => {
        b.className = 'variant-btn px-3 py-1.5 rounded-lg text-sm font-medium border transition bg-white text-gray-600 border-gray-300 hover:border-amber-400';
    });
    btn.className = 'variant-btn px-3 py-1.5 rounded-lg text-sm font-medium border transition bg-amber-500 text-white border-amber-500';
}

function updateModalPrice(p) {
    const modal = document.getElementById('product-modal');
    let total = parseFloat(p.price);
    modal.querySelectorAll('.extra-check:checked').forEach(cb => {
        total += parseFloat(cb.dataset.price || 0);
    });
    document.getElementById('pm-price').textContent = `€${total.toFixed(2)}`;
}

function confirmProductModal(p) {
    const modal = document.getElementById('product-modal');

    // Collect removed ingredients
    const removed = [];
    modal.querySelectorAll('.ingredient-check').forEach(cb => {
        if (!cb.checked) removed.push(cb.value);
    });

    // Collect extras
    const extras = [];
    modal.querySelectorAll('.extra-check:checked').forEach(cb => {
        extras.push({ name: cb.value, price: parseFloat(cb.dataset.price || 0) });
    });

    // Collect variant — cerca solo dentro il modal
    const variantBtn = modal.querySelector('.variant-btn.bg-amber-500');
    const variant    = variantBtn ? variantBtn.textContent.trim() : '';

    // Collect note
    const note = document.getElementById('pm-note').value.trim();

    // Calculate final price
    let unitPrice = parseFloat(p.price);
    extras.forEach(ex => unitPrice += ex.price);

    const customizations = { removed, extras, variant, note };

    addToCart(p.id, p.name, unitPrice, customizations);
    closeProductModal();
}

function closeProductModal() {
    document.getElementById('product-modal').classList.add('hidden');
}

// Close on backdrop click
document.getElementById('product-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('product-modal')) closeProductModal();
});

// =============================================
// CART
// =============================================
function addToCart(id, name, unitPrice, customizations = {}) {
    // Each customized item is unique — always add as new entry
    const hasCustom = (customizations.removed?.length || customizations.extras?.length ||
                       customizations.variant || customizations.note);

    if (!hasCustom) {
        const existing = cart.find(i => i.id === id && !i.customizations?.removed?.length &&
                                        !i.customizations?.extras?.length &&
                                        !i.customizations?.variant && !i.customizations?.note);
        if (existing) { existing.qty++; saveCart(); renderCart(); showToast(`${name} aggiunto!`); return; }
    }

    cart.push({ id, name, price: unitPrice, qty: 1, customizations });
    saveCart();
    renderCart();
    showToast(`${name} aggiunto al carrello!`);
}

function removeFromCart(idx) {
    cart.splice(idx, 1);
    saveCart();
    renderCart();
}

function changeQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) removeFromCart(idx);
    else { saveCart(); renderCart(); }
}

function renderCart() {
    const totalItems = cart.reduce((s, i) => s + i.qty, 0);
    const totalPrice = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const countEl   = document.getElementById('cart-count');

    countEl.textContent = totalItems;
    countEl.classList.toggle('hidden', totalItems === 0);
    document.getElementById('cart-total').textContent = `€${totalPrice.toFixed(2)}`;

    document.getElementById('cart-items').innerHTML = !cart.length
        ? '<p class="text-gray-400 text-sm text-center py-4">Il carrello è vuoto</p>'
        : cart.map((item, idx) => {
            const c = item.customizations || {};
            const details = [
                ...(c.removed?.map(r => `<span class="text-red-400">− ${escHtml(r)}</span>`) || []),
                ...(c.extras?.map(e => `<span class="text-green-600">+ ${escHtml(e.name)}</span>`) || []),
                ...(c.variant ? [`<span class="text-blue-500">${escHtml(c.variant)}</span>`] : []),
                ...(c.note ? [`<span class="text-gray-400 italic">"${escHtml(c.note)}"</span>`] : []),
            ].join(' ');

            return `
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">${escHtml(item.name)}</p>
                        ${details ? `<p class="text-xs mt-0.5 flex flex-wrap gap-1">${details}</p>` : ''}
                        <p class="text-xs text-amber-700 mt-0.5">€${(item.price * item.qty).toFixed(2)}</p>
                    </div>
                    <div class="flex items-center gap-1 mt-0.5">
                        <button onclick="changeQty(${idx}, -1)" class="w-6 h-6 bg-gray-100 rounded text-gray-600 hover:bg-gray-200">−</button>
                        <span class="w-6 text-center text-sm">${item.qty}</span>
                        <button onclick="changeQty(${idx}, 1)" class="w-6 h-6 bg-gray-100 rounded text-gray-600 hover:bg-gray-200">+</button>
                        <button onclick="removeFromCart(${idx})" class="w-6 h-6 bg-red-100 rounded text-red-500 hover:bg-red-200 ml-1">×</button>
                    </div>
                </div>
            `;
        }).join('');
}

function toggleCart() {
    document.getElementById('cart-sidebar').classList.toggle('hidden');
}

// =============================================
// ORDER
// =============================================
async function submitOrder() {
    if (!cart.length) { showToast('Il carrello è vuoto!', 'error'); return; }
    const btn = document.getElementById('btn-order');
    btn.disabled = true;
    btn.textContent = 'Invio in corso...';
    try {
        const res  = await fetch('api/orders.php', {
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
        showToast('Ordine inviato! 🎉');
        loadOrders();
    } catch (e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Invia Ordine';
    }
}

// =============================================
// ORDERS LIST
// =============================================
async function loadOrders() {
    try {
        const orders = await fetch('api/orders.php').then(r => r.json());
        const el     = document.getElementById('orders-list');
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
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusBadge(o.status)}">${statusLabel(o.status)}</span>
                </div>
                <p class="text-amber-700 font-bold mt-2">Totale: €${parseFloat(o.total).toFixed(2)}</p>
                ${o.items ? `<p class="text-xs text-gray-400 mt-1">${o.items}</p>` : ''}
            </div>
        `).join('');
    } catch {
        document.getElementById('orders-list').innerHTML = '<p class="text-red-400 text-sm">Errore nel caricamento ordini.</p>';
    }
}

// =============================================
// UTILS
// =============================================
function statusLabel(s) {
    return { in_attesa: 'In attesa', in_preparazione: 'In preparazione', pronto: '✅ Pronto!', consegnato: 'Consegnato' }[s] || s;
}

function statusBadge(s) {
    return { in_attesa: 'bg-yellow-100 text-yellow-700', in_preparazione: 'bg-blue-100 text-blue-700', pronto: 'bg-green-100 text-green-700', consegnato: 'bg-gray-100 text-gray-600' }[s] || '';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.className = `fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg text-white font-medium ${type === 'error' ? 'bg-red-500' : 'bg-green-600'}`;
    toast.textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

renderCart();
loadMenu();
loadOrders();
