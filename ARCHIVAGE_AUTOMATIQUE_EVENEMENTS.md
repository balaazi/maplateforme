# Système d'Archivage Automatique des Événements

## Vue d'ensemble

Le système d'archivage automatique des événements permet de masquer automatiquement les événements dont la date est dépassée depuis plus d'un jour. Cela permet de garder la liste des événements propre et de ne montrer que les événements pertinents.

## Fonctionnement

### Critères d'archivage
- Un événement est automatiquement archivé si sa date de fin (date de début + durée) est dépassée depuis plus d'un jour
- Seuls les événements non archivés (`archive = false`) sont affichés par défaut dans la liste

### Archivage en temps réel
- L'archivage automatique se déclenche à chaque consultation de la liste des événements
- Un message informatif s'affiche si des événements ont été archivés automatiquement
- L'archivage ne se déclenche que lors de l'affichage des événements actifs (pas lors de la consultation des archives)

### Archivage programmé
- Une commande Symfony permet d'archiver manuellement : `php bin/console app:archive-expired-events`
- Un script batch est disponible pour l'exécution automatique : `auto_archive_events.bat`
- Configuration recommandée : exécution quotidienne à 02:00 via le Planificateur de tâches Windows

## Interface utilisateur

### Bouton d'archives
- Un bouton "Voir les archivés" permet d'afficher les événements archivés
- Le bouton affiche le nombre d'événements archivés entre parenthèses
- En mode archives, le bouton devient "Voir les actifs"

### Affichage des événements
- Par défaut : seuls les événements non archivés sont affichés
- Les événements archivés conservent toutes leurs données et peuvent être consultés
- Les actions (modifier, annuler) ne sont disponibles que sur les événements actifs

## Configuration technique

### Base de données
- Champ `archive` (boolean) dans la table `event`
- Valeur par défaut : `false`
- Index recommandé sur `(archive, created_by)` pour optimiser les requêtes

### Repository
- `findEventsForUser()` : retourne les événements actifs d'un utilisateur
- `findArchivedEventsForUser()` : retourne les événements archivés d'un utilisateur
- `findExpiredEvents()` : retourne les événements expirés non archivés

### Service
- `AutoArchiveService` : gère l'archivage automatique
- `checkAndArchiveCompletedEvents()` : méthode principale d'archivage
- Logs détaillés pour le monitoring

### Contrôleur
- Archivage automatique dans `EventController::list()`
- Comptage des événements archivés pour l'affichage
- Messages flash pour informer l'utilisateur

## Monitoring et maintenance

### Logs
- Tous les événements archivés sont loggés avec leurs détails
- Logs disponibles dans `var/log/` (fichier Symfony)
- Format : `Événement archivé automatiquement - ID: X, Titre: Y, Date: Z`

### Vérification
```bash
# Tester l'archivage manuellement
php bin/console app:archive-expired-events

# Vérifier les événements archivés en base
SELECT COUNT(*) FROM event WHERE archive = true;

# Vérifier les événements expirés non archivés
SELECT COUNT(*) FROM event WHERE archive = false AND DATE_ADD(date_heure, INTERVAL duree MINUTE) < DATE_SUB(NOW(), INTERVAL 1 DAY);
```

### Dépannage
- Vérifier les permissions de la base de données
- Contrôler les logs Symfony pour les erreurs
- Tester la commande manuellement en cas de problème

## Avantages

1. **Performance** : Moins d'événements à charger et afficher
2. **Clarté** : Interface plus propre avec seulement les événements pertinents
3. **Automatisation** : Pas d'intervention manuelle nécessaire
4. **Flexibilité** : Accès aux archives quand nécessaire
5. **Traçabilité** : Logs complets des opérations d'archivage

## Sécurité

- Seuls les créateurs d'événements peuvent voir leurs propres événements (actifs et archivés)
- Les administrateurs ont accès à tous les événements
- Les données archivées ne sont jamais supprimées, seulement masquées
- Pas de modification possible sur les événements archivés 