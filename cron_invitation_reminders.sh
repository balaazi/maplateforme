#!/bin/bash

# Script de rappels automatiques pour les invitations EventHub
# Ce script doit être exécuté via cron pour envoyer automatiquement les rappels

# Configuration
PROJECT_DIR="/c/xampp/htdocs/new/maplateforme"
LOG_FILE="/c/xampp/htdocs/new/maplateforme/var/log/invitation_reminders.log"
PHP_BIN="/c/xampp/php/php.exe"

# Créer le répertoire de logs s'il n'existe pas
mkdir -p "$(dirname "$LOG_FILE")"

# Fonction de logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Changer vers le répertoire du projet
cd "$PROJECT_DIR" || {
    log_message "ERREUR: Impossible de se déplacer vers le répertoire du projet: $PROJECT_DIR"
    exit 1
}

log_message "=== Début du traitement des rappels d'invitations ==="

# Envoyer les rappels 24h avant
log_message "Envoi des rappels 24h avant..."
"$PHP_BIN" bin/console app:send-invitation-reminders --reminder-type=24h >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then
    log_message "Rappels 24h envoyés avec succès"
else
    log_message "ERREUR lors de l'envoi des rappels 24h"
fi

# Envoyer les rappels 1h avant
log_message "Envoi des rappels 1h avant..."
"$PHP_BIN" bin/console app:send-invitation-reminders --reminder-type=1h >> "$LOG_FILE" 2>&1
if [ $? -eq 0 ]; then
    log_message "Rappels 1h envoyés avec succès"
else
    log_message "ERREUR lors de l'envoi des rappels 1h"
fi

log_message "=== Fin du traitement des rappels d'invitations ==="

# Nettoyer les anciens logs (garder seulement les 30 derniers jours)
find "$(dirname "$LOG_FILE")" -name "*.log" -mtime +30 -delete 2>/dev/null

exit 0
