# 🥖 Gestionale Panineria

Web app completa per la gestione di una panineria — menu, ordini, pannello admin e registrazione con verifica email.

## Stack

| Layer | Tecnologia |
|---|---|
| Frontend | HTML5, Tailwind CSS (CDN), Vanilla JS |
| Backend | PHP 8.2 con PDO |
| Database | MySQL 8.0 |
| Email | PHPMailer via Gmail SMTP |
| Infrastruttura | Docker + Docker Compose + Nginx |

## Avvio rapido

### Requisiti
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installato e avviato

### 1. Clona il repository
```bash
git clone https://github.com/SonoProprioMatte/Gestionale_Panineria.git
cd Gestionale_Panineria
```

### 2. Avvia
```bash
bash start.sh
```

### 3. Apri il browser
```
http://localhost:8080
```

> Su **GitHub Codespaces**: vai nel tab **PORTS** e clicca il globo 🌐 sulla porta 8080.

---

## Credenziali default

| Ruolo | Email | Password |
|---|---|---|
| Admin | admin@panineria.it | admin123 |

> ⚠️ Cambia la password admin prima di andare in produzione.

---

## Struttura del progetto

```
Gestionale_Panineria/
├── .env                        # Variabili d'ambiente (SMTP, DB)
├── docker-compose.yml          # Orchestrazione container
├── start.sh                    # Script di avvio
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── composer.json       # PHPMailer
│   │   └── php.ini
│   └── nginx/
│       └── default.conf
├── sql/
│   └── init.sql                # Schema DB + dati di esempio
└── src/
    ├── index.php               # Login / Registrazione
    ├── menu.php                # Vista cliente
    ├── admin.php               # Pannello admin
    ├── verify.php              # Verifica email
    ├── db.php                  # Connessione PDO
    ├── auth.php                # Gestione sessioni e ruoli
    ├── mailer.php              # Invio email con PHPMailer
    ├── api/
    │   ├── products.php        # CRUD prodotti
    │   ├── orders.php          # Ordini cliente
    │   ├── admin_orders.php    # Gestione ordini admin
    │   └── logout.php
    └── js/
        ├── menu.js
        └── admin.js
```

---

## Funzionalità

**Cliente**
- Registrazione con verifica email (codice a 6 cifre, scade in 15 minuti)
- Login con ruoli `customer` e `admin`
- Visualizzazione menu con filtro per categoria
- Carrello locale (localStorage)
- Invio ordini con note opzionali
- Storico ordini personali con stato in tempo reale

**Admin**
- Gestione menu: aggiungi, modifica, elimina, nascondi/mostra prodotti
- Gestione ordini: visualizzazione in tempo reale con aggiornamento stato
- Stati ordine: In attesa → In preparazione → Pronto → Consegnato

---

## Comandi utili

```bash
# Avvia i container
bash start.sh

# Ferma i container (dati conservati)
docker compose stop

# Ferma e cancella tutto incluso il database
docker compose down -v

# Vedi i log
docker compose logs -f

# Controlla lo stato dei container
docker compose ps

# Controlla lo stato di Git
git status
```

---

## Configurazione SMTP

Le credenziali sono nel file `.env`. Per cambiare account email modifica queste righe:

```env
MAIL_USERNAME=tuaemail@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
MAIL_FROM=tuaemail@gmail.com
MAIL_FROM_NAME=PaninoBOT
```

Per Gmail è necessaria una **App Password** (non la password normale):
1. Vai su [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Crea una nuova App Password
3. Incolla il codice a 16 caratteri in `MAIL_PASSWORD`
