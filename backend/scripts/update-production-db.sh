#!/bin/bash

# Script per aggiornare il database di produzione con i campi mancanti
# Uso: ./scripts/update-production-db.sh

echo "====================================="
echo " Update Production Database"
echo "====================================="
echo ""

# Verifica che siamo nella directory backend
if [ ! -f "composer.json" ]; then
    echo "❌ Errore: Esegui questo script dalla directory backend/"
    exit 1
fi

# Backup del file .env corrente (se esiste)
if [ -f ".env" ]; then
    echo "📦 Backup del file .env corrente..."
    cp .env .env.backup
    echo "✓ Backup salvato in .env.backup"
fi

# Copia .env.production in .env
if [ -f ".env.production" ]; then
    echo "📝 Uso della configurazione di produzione..."
    cp .env.production .env
    echo "✓ File .env aggiornato con le credenziali di produzione"
else
    echo "⚠ File .env.production non trovato!"
    echo "Usando il file .env esistente..."
fi

echo ""
echo "🔄 Esecuzione della migrazione per aggiungere i campi mancanti..."
php database/migrations/add_missing_leads_fields.php

MIGRATION_STATUS=$?

# Ripristina il backup se esisteva
if [ -f ".env.backup" ]; then
    echo ""
    echo "🔄 Ripristino del file .env originale..."
    mv .env.backup .env
    echo "✓ File .env ripristinato"
fi

echo ""
if [ $MIGRATION_STATUS -eq 0 ]; then
    echo "✅ Migrazione completata con successo!"
    echo ""
    echo "Il database di produzione è ora aggiornato."
    echo "Prova nuovamente ad inviare un form dalla tua applicazione."
else
    echo "❌ Errore durante la migrazione!"
    exit 1
fi
