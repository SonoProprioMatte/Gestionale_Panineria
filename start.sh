#!/bin/bash

echo "🥖 Avvio Panineria..."
docker compose up -d --build

echo ""
echo "⏳ Aspetto che i container siano pronti..."
sleep 5

echo "📁 Creo cartelle upload..."
docker exec panineria_php mkdir -p /var/www/html/uploads/avatars
docker exec panineria_php mkdir -p /var/www/html/uploads/products

echo ""
echo "✅ Fatto! Apri il tab PORTS e clicca il globo 🌐 sulla porta 8080."
