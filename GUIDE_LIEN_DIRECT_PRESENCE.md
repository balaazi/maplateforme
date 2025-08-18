
# Guide : Lien Direct vers la Page de Présence

## 🎯 Objectif
Permettre aux utilisateurs d'ouvrir directement la page de présence d'un événement depuis le calendrier et la liste des événements.

## ✨ Fonctionnalités Ajoutées

### 1. Menu Contextuel dans le Calendrier
- **Clic sur un événement** → Menu contextuel avec 2 options :
  - 👁️ **Voir l'événement** : Redirige vers la page détaillée de l'événement
  - ✅ **Marquer la présence** : Ouvre directement la page de présence

### 2. Bouton Direct dans la Liste des Événements
- **Bouton "Marquer la présence"** ajouté dans la liste des événements
- Style distinctif avec dégradé orange
- Redirection directe vers `/event/{id}/presence`

## 🔧 Implémentation Technique

### API Modifiée
**Fichier :** `src/Controller/Api/EventApiController.php`

```php
// Ajout de l'URL de présence dans les données de l'événement
$eventData['presenceUrl'] = $this->generateUrl('event_presence', ['id' => $event->getId()]);
```

### Calendrier Modifié
**Fichier :** `templates/calendar/index.html.twig`

- Fonction `showEventContextMenu()` ajoutée
- Menu contextuel avec animations CSS
- Gestion des clics et fermeture automatique

### Liste des Événements Modifiée
**Fichier :** `templates/event/list.html.twig`

- Bouton "Marquer la présence" ajouté
- Style CSS personnalisé pour le bouton

## 🎨 Styles CSS

### Bouton de Présence
```css
.action-btn.presence {
    background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
    color: white;
}
```

### Menu Contextuel
- Animation d'entrée : `menuSlideIn`
- Effets de survol avec dégradé bleu
- Ombres et bordures arrondies

## 🚀 Utilisation

### Depuis le Calendrier
1. Cliquez sur un événement dans le calendrier
2. Menu contextuel s'affiche
3. Choisissez "Marquer la présence"
4. Redirection directe vers la page de présence

### Depuis la Liste des Événements
1. Allez dans la liste des événements
2. Trouvez l'événement souhaité
3. Cliquez sur le bouton orange "Marquer la présence"
4. Redirection directe vers la page de présence

## 🔗 URLs Générées

### Format de l'URL de Présence
```
http://127.0.0.1:8000/event/{id}/presence
```

**Exemple :**
```
http://127.0.0.1:8000/event/209/presence
```

### Route Symfony
```php
#[Route('/event/{id}/presence', name: 'event_presence', methods: ['GET'])]
```

## 🧪 Test de la Fonctionnalité

### Script de Test
Un script `test_presence_api.php` est fourni pour vérifier que l'API retourne bien l'URL de présence.

```bash
php test_presence_api.php
```

### Vérifications
- ✅ L'API retourne `presenceUrl` dans les données
- ✅ Le menu contextuel s'affiche au clic sur un événement
- ✅ Le bouton de présence est visible dans la liste
- ✅ Les redirections fonctionnent correctement

## 🔒 Sécurité

### Contrôles d'Accès
- Seuls les utilisateurs connectés peuvent accéder à l'API
- Vérification des permissions par rôle utilisateur
- Filtrage des événements selon les droits

### Routes Protégées
```yaml
# config/packages/security.yaml
- { path: ^/event/\d+/presence, roles: ROLE_PARTICIPANT }
```

## 📱 Responsive Design

### Menu Contextuel
- Positionnement adaptatif selon la position du clic
- Fermeture automatique au clic extérieur
- Animations fluides sur tous les appareils

### Boutons d'Action
- Grille responsive pour les boutons
- Espacement adaptatif selon la taille d'écran
- Icônes et texte optimisés pour mobile

## 🎯 Avantages

1. **Accès Rapide** : Plus besoin de naviguer via la page de l'événement
2. **UX Améliorée** : Menu contextuel intuitif dans le calendrier
3. **Flexibilité** : Choix entre voir l'événement ou marquer la présence
4. **Cohérence** : Même fonctionnalité disponible partout
5. **Performance** : Redirection directe sans page intermédiaire

## 🔄 Maintenance

### Ajout de Nouvelles Options
Pour ajouter d'autres actions dans le menu contextuel :

1. Modifier la fonction `showEventContextMenu()`
2. Ajouter de nouveaux éléments au menu
3. Définir les styles CSS correspondants

### Personnalisation des Styles
Les couleurs et animations peuvent être facilement modifiées dans les fichiers CSS des templates.

## 📝 Notes de Développement

- **Compatibilité** : Fonctionne avec tous les navigateurs modernes
- **Accessibilité** : Support des lecteurs d'écran et navigation clavier
- **Performance** : Pas d'impact sur le chargement du calendrier
- **Évolutivité** : Architecture modulaire pour ajouts futurs

---

**Développé pour EventHub** - Système de gestion d'événements
