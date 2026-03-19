<?php
// Componente riutilizzabile — includi nelle pagine che richiedono il pannello profilo
// Richiede: auth.php già caricato e sessione attiva
?>

<!-- Pulsante profilo navbar (da inserire nella nav) -->
<!-- Usa id="profile-btn" e onclick="toggleProfile()" -->

<!-- Pannello profilo -->
<div id="profile-panel"
    class="hidden fixed top-16 right-4 z-50 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">

    <!-- Header avatar -->
    <div class="bg-amber-700 px-5 py-4 flex flex-col items-center">
        <div class="relative group cursor-pointer" onclick="triggerAvatarUpload()">
            <div id="pp-avatar" class="w-20 h-20 rounded-full bg-amber-400 flex items-center justify-center text-2xl font-bold text-white border-2 border-white/40">
                ?
            </div>
            <!-- Overlay hover -->
            <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <span class="text-white text-xs font-semibold">Cambia</span>
            </div>
        </div>
        <input type="file" id="pp-avatar-input" accept="image/*" class="hidden">
        <p id="pp-name"  class="mt-2 font-bold text-white text-sm"></p>
        <p id="pp-email" class="text-amber-200 text-xs mt-0.5"></p>
    </div>

    <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">

        <!-- Notifiche -->
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notifiche Email</p>
            <label class="flex items-center justify-between cursor-pointer py-1">
                <span class="text-sm text-gray-700">Avviso nuovo accesso</span>
                <input type="checkbox" id="pp-notify-login" class="w-4 h-4 accent-amber-500">
            </label>
            <label class="flex items-center justify-between cursor-pointer py-1">
                <span class="text-sm text-gray-700">Conferma ordine pronto</span>
                <input type="checkbox" id="pp-notify-order" class="w-4 h-4 accent-amber-500">
            </label>
        </div>

        <hr class="border-gray-100">

        <!-- Cambio password -->
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cambia Password</p>
            <div class="space-y-2">
                <input type="password" id="pp-current-pw" placeholder="Password attuale"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                <input type="password" id="pp-new-pw" placeholder="Nuova password (min. 8 caratteri)"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                <input type="password" id="pp-confirm-pw" placeholder="Conferma nuova password"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                <p id="pp-pw-msg" class="text-xs mt-1"></p>
                <button onclick="changePassword()"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 rounded-lg text-sm transition">
                    Aggiorna Password
                </button>
            </div>
        </div>

        <hr class="border-gray-100">

        <!-- Logout -->
        <a href="api/logout.php"
            class="block w-full text-center text-sm text-red-500 hover:text-red-700 font-semibold py-1 transition">
            Esci dall'account
        </a>
    </div>
</div>
