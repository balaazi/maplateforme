# 🗑️ Guide : Suppression des Documents de Test

## 🎯 **Objectif**

Supprimer les documents de test suivants qui apparaissent dans la section "Documents Uploadés" :
- **Test_Document** (PDF)
- **Test_Image** (Image)
- **Test_Document** (Word)

## ✅ **Fonctionnalité Implémentée**

### **1. Bouton de Suppression Ajouté**
- ✅ **Bouton rouge "Supprimer"** à côté de chaque bouton "Télécharger"
- ✅ **Icône corbeille** (`fas fa-trash-alt`) pour une identification claire
- ✅ **Styles CSS** avec gradient rouge et effets de survol

### **2. Sécurité et Confirmation**
- ✅ **Confirmation obligatoire** avant suppression
- ✅ **Message d'avertissement** sur l'irréversibilité de l'action
- ✅ **Vérification des autorisations** côté serveur
- ✅ **Protection CSRF** pour éviter les attaques

## 🚀 **Comment Procéder**

### **Étape 1 : Accéder à la Section Documents**
1. **Connectez-vous** à votre compte EventHub
2. **Allez dans** "Mon Compte" → "Documents"
3. **Localisez** la section "Documents Uploadés"

### **Étape 2 : Identifier les Documents de Test**
Vous verrez 3 cartes de documents :
- 🟢 **Test_Document** (PDF) - Icône "P"
- 🟢 **Test_Document** (Word) - Icône "W"  
- 🟢 **Test_Image** (Image) - Icône générique

### **Étape 3 : Supprimer Chaque Document**
Pour chaque document de test :

1. **Cliquez sur le bouton rouge "Supprimer"** 🗑️
2. **Confirmez la suppression** dans la boîte de dialogue :
   ```
   Êtes-vous sûr de vouloir supprimer le document "Test_Document" ?
   
   Cette action est irréversible.
   ```
3. **Cliquez sur "OK"** pour confirmer

### **Étape 4 : Vérification**
- ✅ **Document supprimé** de la liste
- ✅ **Compteur mis à jour** dans le header
- ✅ **Message de succès** affiché
- ✅ **Redirection** vers la page des événements

## 🔒 **Système de Sécurité**

### **1. Autorisations Requises**
```php
// Seuls ces utilisateurs peuvent supprimer des documents :
- L'organisateur de l'événement associé
- L'administrateur de la plateforme
```

### **2. Vérifications Automatiques**
- ✅ **Existence du document** en base de données
- ✅ **Droits de l'utilisateur** sur l'événement
- ✅ **Token CSRF** pour la sécurité
- ✅ **Suppression du fichier physique** et de l'entrée en base

### **3. Gestion des Erreurs**
```php
if (!$document) {
    throw $this->createNotFoundException('Document non trouvé');
}

if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
    throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer ce document');
}
```

## 🎨 **Interface Utilisateur**

### **1. Bouton de Suppression**
```html
<button type="button" 
        class="btn-delete-document" 
        data-document-id="{{ document.id }}"
        data-document-name="{{ document.fileName|split('.')|first|title }}"
        onclick="confirmDeleteDocument({{ document.id }}, '{{ document.fileName|split('.')|first|title }}')">
    <i class="fas fa-trash-alt"></i>
    Supprimer
</button>
```

### **2. Styles CSS**
```css
.btn-delete-document {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-delete-document:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
    background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
}
```

### **3. Positionnement**
- **À droite** du bouton "Télécharger"
- **Marge gauche** de 0.5rem pour l'espacement
- **Alignement vertical** avec les autres boutons

## 🔧 **Fonctionnalités JavaScript**

### **1. Confirmation de Suppression**
```javascript
function confirmDeleteDocument(documentId, documentName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le document "${documentName}" ?\n\nCette action est irréversible.`)) {
        deleteDocument(documentId);
    }
}
```

### **2. Suppression du Document**
```javascript
function deleteDocument(documentId) {
    // Créer un formulaire temporaire
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/document/delete/${documentId}`;
    
    // Ajouter le token CSRF
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = 'delete-token';
    form.appendChild(csrfToken);
    
    // Soumettre le formulaire
    document.body.appendChild(form);
    form.submit();
}
```

## 📁 **Architecture Technique**

### **1. Route de Suppression**
```php
#[Route('/delete/{id}', name: 'document_delete', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function delete(int $id, Request $request, EntityManagerInterface $em): Response
{
    // Vérification des droits et suppression
}
```

### **2. Processus de Suppression**
1. **Vérification** de l'existence du document
2. **Validation** des autorisations de l'utilisateur
3. **Suppression** du fichier physique du serveur
4. **Suppression** de l'entrée en base de données
5. **Redirection** avec message de succès

### **3. Gestion des Fichiers**
```php
// Supprimer le fichier physique
$filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();
if (file_exists($filePath)) {
    unlink($filePath);
}

// Supprimer l'entrée en base
$em->remove($document);
$em->flush();
```

## 🧪 **Tests et Validation**

### **1. Scénarios de Test**
- ✅ **Suppression d'un document** → Vérifier disparition de la liste
- ✅ **Confirmation annulée** → Vérifier que le document reste
- ✅ **Autorisations insuffisantes** → Vérifier message d'erreur
- ✅ **Document inexistant** → Vérifier gestion d'erreur

### **2. Validation des Fonctionnalités**
- ✅ **Bouton visible** pour chaque document
- ✅ **Confirmation** avant suppression
- ✅ **Suppression effective** du document
- ✅ **Mise à jour** de l'interface
- ✅ **Messages** de succès/erreur

## 📈 **Résultat Attendu**

### **Après Suppression des Documents de Test**
- 🗑️ **Test_Document** (PDF) : Supprimé
- 🗑️ **Test_Image** (Image) : Supprimé  
- 🗑️ **Test_Document** (Word) : Supprimé
- ✅ **Section "Documents Uploadés"** : Vide ou avec d'autres documents
- ✅ **Compteur dans le header** : Mis à jour (0 si aucun autre document)

### **Interface Finale**
- **Section "Documents Uploadés"** : Masquée si aucun document
- **Message d'état vide** : "Aucun document disponible"
- **Compteur** : Affichant le nombre réel de documents

## 🔧 **Dépannage**

### **1. Bouton de Suppression Non Visible**
- ✅ **Vérifier** que vous êtes l'organisateur de l'événement
- ✅ **Vérifier** que vous avez le rôle administrateur
- ✅ **Actualiser** la page pour recharger les styles

### **2. Erreur lors de la Suppression**
- ✅ **Vérifier** la connexion internet
- ✅ **Vérifier** que le document existe toujours
- ✅ **Contacter** l'administrateur si problème persiste

### **3. Document Reste Visible**
- ✅ **Actualiser** la page après suppression
- ✅ **Vérifier** les messages d'erreur
- ✅ **Vérifier** les logs du serveur

---

## 📚 **Ressources Complémentaires**

- **Contrôleur Document** : `src/Controller/DocumentController.php`
- **Template Documents** : `templates/participant/documents.html.twig`
- **Route de Suppression** : `/document/delete/{id}`
- **Autorisations** : Vérification des droits utilisateur

---

**🎯 Objectif :** Supprimer les documents de test  
**🔧 Solution :** Bouton de suppression avec confirmation  
**📅 Date d'implémentation :** Janvier 2025  
**🚀 Statut :** ✅ Implémenté et testé  
**📱 Version :** 1.0
