# 🥖 Gestionale Panineria — Contesto Progetto

## Descrizione
Web app completa per la gestione di una panineria. Permette ai clienti di ordinare dal menu con personalizzazioni, e all'admin di gestire prodotti e ordini in tempo reale.

## Stack Tecnologico
- **Frontend**: HTML5 + Tailwind CSS (CDN) + Vanilla JavaScript
- **Backend**: PHP 8.2 con PDO (no framework)
- **Database**: MySQL 8.0
- **Email**: PHPMailer via Gmail SMTP
- **Infrastruttura**: Docker + Docker Compose + Nginx + PHP-FPM

## Struttura Cartelle
```
/
├── .env                        # Credenziali (SMTP, DB)
├── docker-compose.yml
├── start.sh                    # Avvio: bash start.sh
├── docker/
│   ├── php/                    # Dockerfile, composer.json, php.ini
│   └── nginx/default.conf
├── sql/
│   ├── init.sql                # Schema completo + seed dati
│   └── migrate_profile.sql     # Migrazione colonne profilo
└── src/
    ├── index.php               # Login + Registrazione
    ├── menu.php                # Vista cliente
    ├── admin.php               # Pannello admin
    ├── verify.php              # Verifica email post-registrazione
    ├── profile_panel.php       # Componente profilo (incluso in menu/admin)
    ├── db.php                  # Connessione PDO singleton
    ├── auth.php                # Sessioni, ruoli, helper
    ├── mailer.php              # PHPMailer: verifica, ordine pronto, nuovo accesso
    ├── api/
    │   ├── products.php        # CRUD prodotti + ingredienti + extra
    │   ├── orders.php          # Ordini cliente
    │   ├── admin_orders.php    # Gestione ordini admin + email ordine pronto
    │   ├── profile.php         # Profilo: avatar upload, password, notifiche
    │   └── logout.php
    └── js/
        ├── menu.js             # Logica cliente: menu, carrello, ordini
        ├── admin.js            # Logica admin: ordini, prodotti CRUD
        └── profile.js          # Pannello profilo (condiviso menu/admin)
```

## Database — Tabelle Principali
- **users** — id, name, email, password, role (customer/admin), avatar_url, notify_login, notify_order
- **products** — id, name, description, price, category, is_visible, variant_options (JSON)
- **product_ingredients** — ingredienti rimovibili per prodotto
- **product_extras** — extra a pagamento per prodotto
- **orders** — id, user_id, total, status (in_attesa/in_preparazione/pronto/consegnato), notes
- **order_items** — righe ordine con product_name e unit_price snapshot
- **order_item_customizations** — personalizzazioni per riga (type: remove/extra/variant/note)
- **email_verifications** — codici 6 cifre per verifica email alla registrazione

## Ruoli Utente
- **customer** — vede menu, fa ordini, vede storico ordini personali
- **admin** — tutto il sopra + gestione prodotti (CRUD) + gestione ordini + cambio stati

## Funzionalità Implementate
- Registrazione con verifica email (codice 6 cifre, scade 15 min)
- Login con notifica email (disabilitabile dal profilo)
- Menu con filtro categorie, prodotti non disponibili mostrati in grigio
- Personalizzazione ordine: rimozione ingredienti, extra a pagamento, varianti, note libere
- Carrello localStorage con versioning (CART_VERSION = 2)
- Email ordine pronto con scontrino dettagliato (disabilitabile dal profilo)
- Pannello profilo: avatar upload (max 5MB, salvato in /uploads/avatars/), cambio password, preferenze notifiche
- Admin ordini: tab Attivi / Archivio (consegnati), aggiornamento stato in tempo reale, auto-refresh 30s
- Admin prodotti: CRUD con ingredienti, extra, varianti, toggle visibilità

## Convenzioni di Codice
- PHP: `declare(strict_types=1)` su tutti i file, PDO prepared statements ovunque, nessun ORM
- JS: Vanilla ES6+, nessun framework, fetch API per tutte le chiamate
- Tutti i redirect sono **relativi** (es. `menu.php` non `/menu.php`) per compatibilità Codespaces
- Le variabili d'ambiente si leggono con `$_ENV['NOME']` in PHP
- PHPMailer autoload da `/var/www/vendor/autoload.php` (vendor fuori da src/ per evitare conflitti con il volume Docker)

## Avvio su Nuovo Codespace
```bash
bash start.sh
# Apri tab PORTS → globo 🌐 porta 8080
```

## Credenziali Default
- Admin: `admin@panineria.it` / `admin123`
- SMTP: configurato in `.env`

## Cose da Fare / Idee Future
- [ ] Foto prodotti (upload immagine per ogni panino)
- [ ] Gestione categorie dal pannello admin
- [ ] Dashboard statistiche (Chart.js): panino più venduto, fasce orarie, ricavi
- [ ] Gestione scorte (magazzino con scalatura automatica)
- [ ] Ottimizzazione mobile
