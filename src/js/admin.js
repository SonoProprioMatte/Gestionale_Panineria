// =============================================
// SECTION NAVIGATION
// =============================================
function showSection(name) {
    ['orders', 'products'].forEach(s => {
        document.getElementById(`section-${s}`).classList.toggle('hidden', s !== name);
        const tab = document.getElementById(`tab-${s}`);
        tab.className = s === name
            ? 'px-5 py-2 bg-amber-500 text-white rounded-lg font-semibold shadow transition'
            : 'px-5 py-2 bg-white text-gray-600 rounded-lg font-semibold shadow hover:bg-gray-50 transition';
    });
    if (name === 'orders') loadOrders();
    if (name === 'products') loadProducts();
}

// =============================================
// ORDERS MANAGEMENT
// =============================================
async function loadOrders() {
    try {
        const res = await fetch('/api/admin_orders.php');
        const orders = await res.json();
        const el = document.getElementById('orders-container');

        if (!orders.length) {
            el.innerHTML = '<p class="text-gray-400 col-span-full text-center py-8">Nessun ordine attivo.</p>';
            return;
        }

        el.innerHTML = orders.map(o => `
            <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 ${statusBorder(o.status)}">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-bold text-gray-800">Ordine #${o.id}</p>
                        <p class="text-sm text-gray-500">${escHtml(o.user_name)} — ${new Date(o.created_at).toLocaleTimeString('it-IT', {hour:'2-digit',minute:'2-digit'})}</p>
                    </div>
                    <span class="font-bold text-amber-700">€${parseFloat(o.total).toFixed(2)}</span>
                </div>
                ${o.items ? `<p class="text-sm text-gray-600 mb-3 border-t pt-2">${escHtml(o.items)}</p>` : ''}
                ${o.notes ? `<p class="text-xs text-blue-600 mb-3 italic">📝 ${escHtml(o.notes)}</p>` : ''}
                <select onchange="updateOrderStatus(${o.id}, this.value)"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    ${['in_attesa','in_preparazione','pronto','consegnato'].map(s =>
                        `<option value="${s}" ${s === o.status ? 'selected' : ''}>${statusLabel(s)}</option>`
                    ).join('')}
                </select>
            </div>
        `).join('');
    } catch (e) {
        document.getElementById('orders-container').innerHTML = '<p class="text-red-400">Errore caricamento ordini.</p>';
    }
}

async function updateOrderStatus(orderId, status) {
    try {
        const res = await fetch('/api/admin_orders.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: orderId, status })
        });
        if (!res.ok) throw new Error();
        showToast('Stato aggiornato!');
        // Refresh after short delay for UX
        setTimeout(loadOrders, 500);
    } catch {
        showToast('Errore aggiornamento stato', 'error');
    }
}

// =============================================
// PRODUCTS MANAGEMENT
// =============================================
let products = [];

async function loadProducts() {
    try {
        const res = await fetch('/api/products.php?all=1');
        products = await res.json();
        renderProductsTable();
    } catch {
        document.getElementById('products-container').innerHTML = '<p class="text-red-400 p-6">Errore caricamento prodotti.</p>';
    }
}

function renderProductsTable() {
    const el = document.getElementById('products-container');
    el.innerHTML = `
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Prodotto</th>
                    <th class="px-4 py-3 text-left">Categoria</th>
                    <th class="px-4 py-3 text-right">Prezzo</th>
                    <th class="px-4 py-3 text-center">Visibile</th>
                    <th class="px-4 py-3 text-center">Azioni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                ${products.map(p => `
                    <tr class="${p.is_visible ? '' : 'bg-gray-50 opacity-60'}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">${escHtml(p.name)}</p>
                            <p class="text-xs text-gray-400">${escHtml(p.description || '')}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-500">${escHtml(p.category)}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-700">€${parseFloat(p.price).toFixed(2)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="toggleVisibility(${p.id}, ${p.is_visible})"
                                class="relative inline-flex h-6 w-11 rounded-full transition-colors focus:outline-none ${p.is_visible ? 'bg-green-400' : 'bg-gray-300'}"
                                title="${p.is_visible ? 'Clicca per nascondere' : 'Clicca per mostrare'}">
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform mt-0.5 ${p.is_visible ? 'translate-x-5' : 'translate-x-0.5'}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick="openModal(${p.id})"
                                    class="bg-blue-50 hover:bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-medium transition">
                                    ✏️ Modifica
                                </button>
                                <button onclick="deleteProduct(${p.id})"
                                    class="bg-red-50 hover:bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-medium transition">
                                    🗑️ Elimina
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// TOGGLE VISIBILITY — the key feature: one API call to hide/show a product
async function toggleVisibility(id, currentVisible) {
    try {
        const res = await fetch('/api/products.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, is_visible: currentVisible ? 0 : 1 })
        });
        if (!res.ok) throw new Error();
        showToast(currentVisible ? 'Prodotto nascosto' : 'Prodotto visibile');
        loadProducts();
    } catch {
        showToast('Errore aggiornamento visibilità', 'error');
    }
}

async function deleteProduct(id) {
    if (!confirm('Eliminare definitivamente questo prodotto?')) return;
    try {
        const res = await fetch('/api/products.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        if (!res.ok) throw new Error();
        showToast('Prodotto eliminato');
        loadProducts();
    } catch {
        showToast('Errore eliminazione', 'error');
    }
}

// =============================================
// MODAL (Add / Edit Product)
// =============================================
function openModal(id = null) {
    const p = id ? products.find(x => x.id === id) : null;
    document.getElementById('modal-title').textContent = p ? 'Modifica Prodotto' : 'Nuovo Prodotto';
    document.getElementById('product-id').value = p ? p.id : '';
    document.getElementById('product-name').value = p ? p.name : '';
    document.getElementById('product-category').value = p ? p.category : 'Panini';
    document.getElementById('product-description').value = p ? (p.description || '') : '';
    document.getElementById('product-price').value = p ? p.price : '';
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('product-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('product-id').value;
    const body = {
        name: document.getElementById('product-name').value.trim(),
        category: document.getElementById('product-category').value.trim(),
        description: document.getElementById('product-description').value.trim(),
        price: parseFloat(document.getElementById('product-price').value),
    };
    if (id) body.id = parseInt(id);

    try {
        const res = await fetch('/api/products.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!res.ok) throw new Error();
        closeModal();
        showToast(id ? 'Prodotto aggiornato!' : 'Prodotto aggiunto!');
        loadProducts();
    } catch {
        showToast('Errore salvataggio', 'error');
    }
});

// Close modal on backdrop click
document.getElementById('modal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('modal')) closeModal();
});

// =============================================
// UTILITIES
// =============================================
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

function statusLabel(s) {
    return { in_attesa: '⏳ In attesa', in_preparazione: '👨‍🍳 In preparazione', pronto: '✅ Pronto!', consegnato: '🎉 Consegnato' }[s] || s;
}

function statusBorder(s) {
    return { in_attesa: 'border-yellow-400', in_preparazione: 'border-blue-400', pronto: 'border-green-400', consegnato: 'border-gray-300' }[s] || '';
}

// =============================================
// INIT + AUTO-REFRESH ORDERS
// =============================================
loadOrders();
setInterval(loadOrders, 30000); // auto-refresh every 30s
