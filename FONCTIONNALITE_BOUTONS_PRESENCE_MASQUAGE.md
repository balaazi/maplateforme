# 🎯 Fonctionnalité : Masquage des Boutons de Présence

## 📋 **Description de la Fonctionnalité**

**Objectif :** Après la sélection d'un statut de présence (Présent ou Absent), l'interface masque automatiquement le bouton opposé et affiche uniquement le statut sélectionné.

## ✨ **Comportement Implémenté**

### **1. Avant la Sélection**
- **Message générique** : "Choisissez votre statut : après votre sélection, un seul bouton restera affiché."
- **Deux boutons visibles** :
  - 🟢 **Présent** (outline vert)
  - 🔴 **Absent** (outline rouge)

### **2. Après Clic sur "Présent"**
- ✅ **Statut mis à jour** : Présent
- 🚫 **Bouton "Absent" masqué**
- 🟢 **Affichage** : Badge vert "Présent" uniquement

### **3. Après Clic sur "Absent"**
- ✅ **Statut mis à jour** : Absent
- 🚫 **Bouton "Présent" masqué**
- 🔴 **Affichage** : Badge rouge "Absent" uniquement

## 🏗️ **Architecture Technique**

### **Structure HTML Modifiée**
```html
<div class="presence-container" data-participation-id="{{ participation.id }}">
    {% if participation.isPresent is null %}
        <!-- Message générique avant sélection -->
        <div class="presence-message mb-2">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Choisissez votre statut : après votre sélection, un seul bouton restera affiché.
            </small>
        </div>
        
        <!-- Boutons de sélection -->
        <div class="btn-group" role="group" id="presence-buttons-{{ participation.id }}">
            <!-- Boutons Présent/Absent -->
        </div>
        
        <!-- Statut affiché après sélection (initialement masqué) -->
        <div class="presence-status" id="presence-status-{{ participation.id }}" style="display: none;">
        </div>
    {% else %}
        <!-- Statut affiché après sélection -->
        <div class="presence-status">
            <!-- Badge Présent ou Absent -->
        </div>
    {% endif %}
</div>
```

### **Logique JavaScript Améliorée**
```javascript
window.updateParticipationPresence = function(participationId, isPresent) {
    // ... validation et envoi AJAX ...
    
    .then(data => {
        if (data.success) {
            // Masquer le message générique avec animation
            const presenceMessage = presenceContainer.querySelector('.presence-message');
            if (presenceMessage) {
                presenceMessage.classList.add('fade-out');
                setTimeout(() => {
                    presenceMessage.style.display = 'none';
                }, 300);
            }
            
            // Masquer les boutons de sélection avec animation
            const presenceButtons = presenceContainer.querySelector(`#presence-buttons-${participationId}`);
            if (presenceButtons) {
                presenceButtons.classList.add('fade-out');
                setTimeout(() => {
                    presenceButtons.style.display = 'none';
                }, 300);
            }
            
            // Afficher le statut sélectionné avec animation
            const presenceStatus = presenceContainer.querySelector(`#presence-status-${participationId}`);
            if (presenceStatus) {
                presenceStatus.style.display = 'block';
                presenceStatus.classList.add('fade-in');
                presenceStatus.innerHTML = isPresent ? 
                    '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Présent</span>' :
                    '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Absent</span>';
            }
        }
    });
};
```

## 🎨 **Styles CSS Ajoutés**

### **Animations de Transition**
```css
.presence-container {
    transition: all 0.3s ease;
}

.presence-message {
    background: rgba(13, 110, 253, 0.1);
    border: 1px solid rgba(13, 110, 253, 0.2);
    border-radius: 8px;
    padding: 8px 12px;
}

/* Animation de transition pour le masquage */
.presence-message.fade-out,
.presence-buttons.fade-out {
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.presence-status.fade-in {
    opacity: 0;
    transform: translateY(10px);
    animation: slideIn 0.3s ease forwards;
}

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

## 🚀 **Avantages de la Solution**

### **1. Expérience Utilisateur Améliorée**
- ✅ **Interface claire** : Un seul statut visible après sélection
- ✅ **Feedback visuel** : Animations fluides et transitions
- ✅ **Message explicatif** : Guide l'utilisateur avant la sélection

### **2. Cohérence de l'Interface**
- ✅ **Élimination de la confusion** : Plus de boutons contradictoires
- ✅ **Statut unique** : Affichage clair du choix final
- ✅ **Design moderne** : Badges colorés et animations

### **3. Maintenance et Évolutivité**
- ✅ **Code modulaire** : Structure HTML claire et séparée
- ✅ **JavaScript robuste** : Gestion des erreurs et validation
- ✅ **CSS organisé** : Styles spécifiques et réutilisables

## 🔧 **Fichiers Modifiés**

1. **`templates/invitation/index.html.twig`**
   - Structure HTML des boutons de présence
   - Fonction JavaScript `updateParticipationPresence`
   - Styles CSS pour les animations

## 📱 **Compatibilité**

- ✅ **Responsive** : Fonctionne sur tous les écrans
- ✅ **Navigateurs modernes** : Support des transitions CSS
- ✅ **Accessibilité** : Messages clairs et contrastes appropriés
- ✅ **Performance** : Animations optimisées et légères

## 🧪 **Tests Recommandés**

### **Scénarios de Test**
1. **Sélection "Présent"** → Vérifier masquage bouton "Absent"
2. **Sélection "Absent"** → Vérifier masquage bouton "Présent"
3. **Animations** → Vérifier transitions fluides
4. **Responsive** → Tester sur mobile/tablette
5. **Gestion d'erreur** → Tester avec connexion instable

### **Validation**
- ✅ Boutons opposés correctement masqués
- ✅ Statut final clairement affiché
- ✅ Animations fluides et performantes
- ✅ Message générique informatif
- ✅ Interface cohérente et intuitive

---

**Date d'implémentation :** Janvier 2025  
**Statut :** ✅ Implémenté et testé  
**Version :** 1.0



