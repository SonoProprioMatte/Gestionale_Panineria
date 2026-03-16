// ── Navigazione ────────────────────────────────────────────────────────────
function showSection(name) {
    ['orders', 'products'].forEach(s => {
        document.getElementById(`section-${s}`).classList.toggle('hidden', s !== name);
        document.getElementById(`tab-${s}`).className = s === name
            ? 'px-5 py-2 bg-amber-500 text-white rounded-lg font-semibold shadow transition'
            : 'px-5 py-2 bg-white text-gray-600 rounded-lg font-semibold shadow hover:bg-gray-50 transition';
    });
    if (name === 'orders') loadOrders();
    if (name === 'products') loadProducts();
}

// ── Ordini ─────────────────────────────────────────────────────────────────
async function loadOrders() {
    const el = document.getElementById('orders-container');
    try {
        const orders = await fetch('api/admin_orders.php').then(r => r.json());
        if (!orders.length) {
            el.innerHTML = '<p class="text-gray-400 col-span-full text-center py-8">Nessun ordine.</p>';
            return;
        }
        el.innerHTML = orders.map(o => `
            <div class="bg-white rounded-xl p-4 shadow-sm border-l-4 ${statusBorder(o.status)}">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-bold text-gray-800">Ordine #${o.id}</p>
                        <p class="text-sm text-gray-500">${esc(o.user_name)} — ${new Date(o.created_at).toLocaleTimeString('it-IT', {hour:'2-digit', minute:'2-digit'})}</p>
                    </div>
                    <span class="font-bold text-amber-700">€${parseFloat(o.total).toFixed(2)}</span>
                </div>
                ${o.items ? `<p class="text-sm text-gray-600 mb-3 border-t pt-2">${esc(o.items)}</p>` : ''}
                ${o.notes ? `<p class="text-xs text-blue-600 mb-3 italic">📝 ${esc(o.notes)}</p>` : ''}
                <select onchange="updateOrderStatus(${o.id}, this.value)"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    ${['in_attesa','in_preparazione','pronto','consegnato'].map(s =>
                        `<option value="${s}" ${s === o.status ? 'selected' : ''}>${statusLabel(s)}</option>`
                    ).join('')}
                </select>
            </div>
        `).join('');
    } catch {
        el.innerHTML = '<p class="text-red-400">Errore caricamento ordini.</p>';
    }
}

async function updateOrderStatus(orderId, status) {
    try {
        const res = await fetch('api/admin_orders.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: orderId, status })
        });
        if (!res.ok) throw new Error();
        toast('Stato aggiornato!');
        setTimeout(loadOrders, 500);
    } catch {
        toast('Errore aggiornamento stato', 'error');
    }
}

// ── Prodotti ───────────────────────────────────────────────────────────────
let products = [];

async function loadProducts() {
    try {
        products = await fetch('api/products.php?all=1').then(r => r.json());
        renderProductsTable();
    } catch {
        document.getElementById('products-container').innerHTML = '<p class="text-red-400 p-6">Errore caricamento prodotti.</p>';
    }
}

function renderProductsTable() {
    document.getElementById('products-container').innerHTML = `
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
                            <p class="font-medium text-gray-800">${esc(p.name)}</p>
                            <p class="text-xs text-gray-400">${esc(p.description || '')}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-500">${esc(p.category)}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-700">€${parseFloat(p.price).toFixed(2)}</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="toggleVisibility(${p.id}, ${p.is_visible})"
                                class="relative inline-flex h-6 w-11 rounded-full transition-colors focus:outline-none ${p.is_visible ? 'bg-green-400' : 'bg-gray-300'}">
                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform mt-0.5 ${p.is_visible ? 'translate-x-5' : 'translate-x-0.5'}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick="openModal(${p.id})" class="bg-blue-50 hover:bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs font-medium transition">✏️ Modifica</button>
                                <button onclick="deleteProduct(${p.id})" class="bg-red-50 hover:bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-medium transition">🗑️ Elimina</button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

async function toggleVisibility(id, current) {
    try {
        await fetch('api/products.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, is_visible: current ? 0 : 1 })
        });
        toast(current ? 'Prodotto nascosto' : 'Prodotto visibile');
        loadProducts();
    } catch {
        toast('Errore', 'error');
    }
}

async function deleteProduct(id) {
    if (!confirm('Eliminare definitivamente questo prodotto?')) return;
    try {
        await fetch('api/products.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        toast('Prodotto eliminato');
        loadProducts();
    } catch {
        toast('Errore eliminazione', 'error');
    }
}

// ── Modal ──────────────────────────────────────────────────────────────────
function openModal(id = null) {
    const p = id ? products.find(x => x.id === id) : null;
    document.getElementById('modal-title').textContent   = p ? 'Modifica Prodotto' : 'Nuovo Prodotto';
    document.getElementById('product-id').value          = p?.id ?? '';
    document.getElementById('product-name').value        = p?.name ?? '';
    document.getElementById('product-category').value    = p?.category ?? 'Panini';
    document.getElementById('product-description').value = p?.description ?? '';
    document.getElementById('product-price').value       = p?.price ?? '';
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('product-form').addEventListener('submit', async e => {
    e.preventDefault();
    const id   = document.getElementById('product-id').value;
    const body = {
        name:        document.getElementById('product-name').value.trim(),
        category:    document.getElementById('product-category').value.trim(),
        description: document.getElementById('product-description').value.trim(),
        price:       parseFloat(document.getElementById('product-price').value),
    };
    if (id) body.id = parseInt(id);
    try {
        const res = await fetch('api/products.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!res.ok) throw new Error();
        closeModal();
        toast(id ? 'Prodotto aggiornato!' : 'Prodotto aggiunto!');
        loadProducts();
    } catch {
        toast('Errore salvataggio', 'error');
    }
});

document.getElementById('modal').addEventListener('click', e => {
    if (e.target === document.getElementById('modal')) closeModal();
});

// ── Utility ────────────────────────────────────────────────────────────────
function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toast(msg, type = 'success') {
    const el = document.getElementById('toast');
    el.className = `fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg text-white font-medium ${type === 'error' ? 'bg-red-500' : 'bg-green-600'}`;
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3000);
}

function statusLabel(s) {
    return { in_attesa: '⏳ In attesa', in_preparazione: '👨‍🍳 In preparazione', pronto: '✅ Pronto!', consegnato: '🎉 Consegnato' }[s] ?? s;
}

function statusBorder(s) {
    return { in_attesa: 'border-yellow-400', in_preparazione: 'border-blue-400', pronto: 'border-green-400', consegnato: 'border-gray-300' }[s] ?? '';
}

// ── Init ───────────────────────────────────────────────────────────────────
loadOrders();
setInterval(loadOrders, 30000);
