# 🥖 Panineria Web App

Full-stack web application for a sandwich shop, containerized with Docker.

## Stack

- **Frontend**: HTML5 + Tailwind CSS (CDN) + Vanilla JS
- **Backend**: PHP 8.2 (PDO)
- **Database**: MySQL 8.0
- **Web Server**: Nginx + PHP-FPM
- **Infra**: Docker & Docker Compose

## Avvio rapido

```bash
# 1. Clona / scarica il progetto
cd panineria-app

# 2. Avvia tutti i container
docker compose up -d --build

# 3. Apri il browser
# http://localhost:8080

# Admin di default: admin@panineria.it / password  ← CAMBIA IN PRODUZIONE!
```

## Struttura

```
panineria-app/
├── docker-compose.yml
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── nginx/
│       └── default.conf       ← Aggiunto rispetto alla struttura originale
├── src/
│   ├── index.php              (Login / Registrazione)
│   ├── menu.php               (Vista cliente)
│   ├── admin.php              (Dashboard admin)
│   ├── db.php                 (Connessione PDO)
│   ├── auth.php               (Helper sessioni/ruoli) ← Aggiunto
│   ├── api/
│   │   ├── products.php       (CRUD prodotti)
│   │   ├── orders.php         (Ordini cliente)
│   │   ├── admin_orders.php   (Gestione ordini admin)
│   │   └── logout.php
│   └── js/
│       ├── menu.js
│       └── admin.js
└── sql/
    └── init.sql
```

## Modifiche alla struttura originale

| Aggiunta | Motivo |
|---|---|
| `docker/nginx/default.conf` | Serve Nginx separato da PHP-FPM (best practice, performance) |
| `src/auth.php` | Helper riutilizzabile per sessioni, ruoli, redirect (evita codice duplicato) |
| `src/api/admin_orders.php` | Separato da `orders.php` per rispettare il principio di responsabilità singola e semplificare i controlli RBAC |

## Sicurezza implementata

- `password_hash(PASSWORD_BCRYPT, cost:12)` + `password_verify`
- PDO con prepared statements (zero SQL injection)
- `session_regenerate_id(true)` dopo il login
- `cookie_httponly`, `use_strict_mode`, `cookie_samesite=Lax`
- Controllo ruolo su ogni endpoint API (non solo la pagina admin.php)
- Validazione server-side di tutti gli input

## Credenziali demo

| Ruolo | Email | Password |
|---|---|---|
| Admin | admin@panineria.it | password |

> ⚠️ **Attenzione**: Cambia tutte le password e le variabili d'ambiente prima del deploy in produzione!
