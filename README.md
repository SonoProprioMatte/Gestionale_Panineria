# 🥖 Gestionale Panineria

> A Dockerized full-stack web app for sandwich shop management — real-time order tracking, menu customization, and automated email notifications.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-latest-009639?logo=nginx&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind-CDN-06B6D4?logo=tailwindcss&logoColor=white)

---

## ✨ Features

### 👤 Customer
- Registration with **email verification** (6-digit code, expires in 15 min)
- Login with role-based access (`customer` / `admin`)
- Browse menu with **category filtering**
- Local cart (via `localStorage`) with optional order notes
- **Personal order history** with live status updates

### 🛠️ Admin
- Full **CRUD** on menu items (add, edit, delete, show/hide)
- Real-time order management panel
- Order lifecycle: `Pending → Preparing → Ready → Delivered`

---

## 🏗️ Tech Stack

| Layer          | Technology                        |
|----------------|-----------------------------------|
| Frontend       | HTML5, Tailwind CSS (CDN), Vanilla JS |
| Backend        | PHP 8.2 with PDO                  |
| Database       | MySQL 8.0                         |
| Email          | PHPMailer via Gmail SMTP          |
| Infrastructure | Docker + Docker Compose + Nginx   |

---

## 🚀 Quick Start

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running

### 1. Clone the repository
```bash
git clone https://github.com/SonoProprioMatte/Gestionale_Panineria.git
cd Gestionale_Panineria
```

### 2. Configure environment variables
```bash
cp .env.example .env
# Edit .env with your SMTP credentials (see SMTP Configuration below)
```

### 3. Start the app
```bash
bash start.sh
```

### 4. Open in browser
```
http://localhost:8080
```

> 💡 **GitHub Codespaces users:** go to the **PORTS** tab and click the 🌐 globe icon on port 8080.

---

## 🔑 Default Credentials

| Role  | Email              | Password  |
|-------|--------------------|-----------|
| Admin | admin@panineria.it | admin123  |

> ⚠️ **Change the admin password before deploying to production.**

---

## 📁 Project Structure

```
Gestionale_Panineria/
├── .env                        # Environment variables (SMTP, DB)
├── .env.example                # Environment template
├── docker-compose.yml          # Container orchestration
├── start.sh                    # Startup script
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── composer.json       # PHPMailer dependency
│   │   └── php.ini
│   └── nginx/
│       └── default.conf
├── sql/
│   └── init.sql                # DB schema + seed data
└── src/
    ├── index.php               # Login / Registration
    ├── menu.php                # Customer view
    ├── admin.php               # Admin panel
    ├── verify.php              # Email verification
    ├── db.php                  # PDO connection
    ├── auth.php                # Session & role management
    ├── mailer.php              # Email via PHPMailer
    ├── api/
    │   ├── products.php        # CRUD products
    │   ├── orders.php          # Customer orders
    │   ├── admin_orders.php    # Admin order management
    │   └── logout.php
    └── js/
        ├── menu.js
        └── admin.js
```

---

## 📧 SMTP Configuration

Edit the `.env` file with your Gmail credentials:

```env
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
MAIL_FROM=your@gmail.com
MAIL_FROM_NAME=PaninoBOT
```

Gmail requires an **App Password** (not your regular password):

1. Go to [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Create a new App Password
3. Paste the 16-character code into `MAIL_PASSWORD`

---

## 🐳 Useful Docker Commands

```bash
# Start containers
bash start.sh

# Stop containers (data preserved)
docker compose stop

# Stop and remove everything including the database
docker compose down -v

# View live logs
docker compose logs -f

# Check container status
docker compose ps
```

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you'd like to change.

---

## 📄 License

This project is open source. Feel free to use it as a base for your own sandwich shop management system 🥪
