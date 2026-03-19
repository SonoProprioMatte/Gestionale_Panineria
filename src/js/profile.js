// =============================================
// PROFILE PANEL — condiviso tra menu e admin
// =============================================

let profileData = null;

async function loadProfile() {
    try {
        const res = await fetch('api/profile.php');
        profileData = await res.json();
        renderProfileBtn();
    } catch {
        console.error('Errore caricamento profilo');
    }
}

function getAvatarHtml(avatarUrl, size = 'w-9 h-9', textSize = 'text-sm') {
    if (avatarUrl) {
        return `<img src="${avatarUrl}" alt="Avatar" class="${size} rounded-full object-cover border-2 border-white/40">`;
    }
    const initial = (profileData?.name || '?')[0].toUpperCase();
    return `<div class="${size} rounded-full bg-amber-400 flex items-center justify-center ${textSize} font-bold text-white border-2 border-white/40">${initial}</div>`;
}

function renderProfileBtn() {
    const btn = document.getElementById('profile-btn');
    if (!btn || !profileData) return;
    btn.innerHTML = getAvatarHtml(profileData.avatar_url);
}

function toggleProfile() {
    const panel = document.getElementById('profile-panel');
    if (panel.classList.contains('hidden')) {
        openProfile();
    } else {
        closeProfile();
    }
}

function openProfile() {
    const panel = document.getElementById('profile-panel');
    if (!profileData) return;

    // Avatar grande
    document.getElementById('pp-avatar').innerHTML = getAvatarHtml(profileData.avatar_url, 'w-20 h-20', 'text-2xl');

    // Info
    document.getElementById('pp-name').textContent  = profileData.name;
    document.getElementById('pp-email').textContent = profileData.email;

    // Notifiche
    document.getElementById('pp-notify-login').checked = !!profileData.notify_login;
    document.getElementById('pp-notify-order').checked = !!profileData.notify_order;

    // Reset form password
    document.getElementById('pp-current-pw').value  = '';
    document.getElementById('pp-new-pw').value      = '';
    document.getElementById('pp-confirm-pw').value  = '';
    document.getElementById('pp-pw-msg').textContent = '';
    document.getElementById('pp-pw-msg').className   = 'text-xs mt-1';

    panel.classList.remove('hidden');

    // Chiudi cliccando fuori
    setTimeout(() => {
        document.addEventListener('click', closePanelOnOutsideClick);
    }, 10);
}

function closeProfile() {
    document.getElementById('profile-panel').classList.add('hidden');
    document.removeEventListener('click', closePanelOnOutsideClick);
}

function closePanelOnOutsideClick(e) {
    const panel = document.getElementById('profile-panel');
    const btn   = document.getElementById('profile-btn');
    if (!panel.contains(e.target) && !btn.contains(e.target)) {
        closeProfile();
    }
}

// Upload avatar al click/hover
function triggerAvatarUpload() {
    document.getElementById('pp-avatar-input').click();
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('pp-avatar-input');
    if (!input) return;

    input.addEventListener('change', async () => {
        if (!input.files[0]) return;
        const form = new FormData();
        form.append('avatar', input.files[0]);
        try {
            const res  = await fetch('api/profile.php', { method: 'POST', body: form });
            const data = await res.json();
            if (!res.ok) { showProfileMsg(data.error, 'error'); return; }
            profileData.avatar_url = data.avatar_url;
            renderProfileBtn();
            document.getElementById('pp-avatar').innerHTML = getAvatarHtml(data.avatar_url, 'w-20 h-20', 'text-2xl');
            showProfileMsg('Foto aggiornata!');
        } catch {
            showProfileMsg('Errore upload', 'error');
        }
    });

    // Salva notifiche al cambio toggle
    ['pp-notify-login', 'pp-notify-order'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', saveNotifications);
    });
});

async function saveNotifications() {
    const notifyLogin = document.getElementById('pp-notify-login').checked;
    const notifyOrder = document.getElementById('pp-notify-order').checked;
    try {
        await fetch('api/profile.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notify_login: notifyLogin, notify_order: notifyOrder })
        });
        profileData.notify_login = notifyLogin;
        profileData.notify_order = notifyOrder;
        showProfileMsg('Preferenze salvate!');
    } catch {
        showProfileMsg('Errore salvataggio', 'error');
    }
}

async function changePassword() {
    const current = document.getElementById('pp-current-pw').value;
    const newPw   = document.getElementById('pp-new-pw').value;
    const confirm = document.getElementById('pp-confirm-pw').value;
    const msg     = document.getElementById('pp-pw-msg');

    if (!current || !newPw || !confirm) {
        msg.textContent = 'Compila tutti i campi.';
        msg.className   = 'text-xs mt-1 text-red-500';
        return;
    }
    if (newPw !== confirm) {
        msg.textContent = 'Le password non coincidono.';
        msg.className   = 'text-xs mt-1 text-red-500';
        return;
    }
    if (newPw.length < 8) {
        msg.textContent = 'Minimo 8 caratteri.';
        msg.className   = 'text-xs mt-1 text-red-500';
        return;
    }

    try {
        const res  = await fetch('api/profile.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ current_password: current, new_password: newPw, confirm_password: confirm })
        });
        const data = await res.json();
        if (!res.ok) {
            msg.textContent = data.error;
            msg.className   = 'text-xs mt-1 text-red-500';
            return;
        }
        msg.textContent = '✓ Password aggiornata!';
        msg.className   = 'text-xs mt-1 text-green-600';
        document.getElementById('pp-current-pw').value = '';
        document.getElementById('pp-new-pw').value     = '';
        document.getElementById('pp-confirm-pw').value = '';
    } catch {
        msg.textContent = 'Errore di rete.';
        msg.className   = 'text-xs mt-1 text-red-500';
    }
}

function showProfileMsg(msg, type = 'success') {
    // Usa il toast globale se disponibile
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.className = `fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg text-white font-medium ${type === 'error' ? 'bg-red-500' : 'bg-green-600'}`;
    toast.textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

loadProfile();
