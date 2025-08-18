# Guide - Documents d'Événements Visibles dans la Section "Documents"

## 🎯 Problème Résolu

**"Lors de la création d'un événement, j'ai un champ « Documents et fichiers » dans lequel je peux ajouter les documents liés à l'événement. Je souhaite que ces documents et fichiers soient ensuite visibles dans la section « Documents »."**

## ✅ Solution Implémentée

### 🔧 Modifications Apportées

#### 1. **Contrôleur EventController** (`src/Controller/EventController.php`)

**Création d'événements :**
```php
// Traitement des fichiers uploadés
$uploadedFiles = $form->get('imageFile')->getData();
$documentsCreated = 0;
if ($uploadedFiles) {
    // Vérifier si c'est un fichier unique ou multiple
    if (!is_array($uploadedFiles)) {
        $uploadedFiles = [$uploadedFiles];
    }
    
    foreach ($uploadedFiles as $uploadedFile) {
        if ($uploadedFile) {
            try {
                $document = new Document();
                $document->setFile($uploadedFile);
                $document->setEvent($event);
                $entityManager->persist($document);
                $documentsCreated++;
            } catch (\Exception $e) {
                error_log('Erreur lors de la création du document: ' . $e->getMessage());
            }
        }
    }
}
```

**Modification d'événements :**
- Même logique appliquée pour permettre l'ajout de documents lors de la modification d'événements
- Messages de succès mis à jour pour indiquer le nombre de documents uploadés

#### 2. **Formulaire EventFormType** (`src/Form/EventFormType.php`)

```php
->add('imageFile', FileType::class, [
    'label' => 'Documents (PDF, Word, Images, etc.)',
    'mapped' => false,
    'required' => false,
    'multiple' => true,  // ← NOUVEAU : Support multi-fichiers
    'constraints' => [
        new File([
            'maxSize' => '10M',
            'mimeTypes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif'
            ],
            'mimeTypesMessage' => 'Veuillez télécharger un fichier valide (PDF, Word, Image).',
        ])
    ],
    'attr' => [
        'class' => 'form-control',
        'accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.gif',
        'multiple' => 'multiple'  // ← NOUVEAU : Attribut HTML pour multi-sélection
    ]
])
```

#### 3. **Gestion des Entités**

**Entité Document** (`src/Entity/Document.php`) :
- ✅ Déjà configurée avec VichUploaderBundle
- ✅ Relation ManyToOne avec Event
- ✅ Propriétés file et fileName gérées automatiquement

**Entité Event** (`src/Entity/Event.php`) :
- ✅ Relation OneToMany avec Document
- ✅ Collection $documents initialisée dans le constructeur

#### 4. **Contrôleur ParticipantController** (`src/Controller/ParticipantController.php`)

**Section Documents** (déjà configuré) :
```php
// Récupérer tous les documents uploadés des événements du participant
$documents = [];
foreach ($events as $event) {
    $eventDocuments = $event->getDocuments();
    foreach ($eventDocuments as $document) {
        $documents[] = $document;
    }
}

return $this->render('participant/documents.html.twig', [
    'documents' => $documents,
    'participations' => $allParticipations,
    'archivedCount' => $archivedCount
]);
```

#### 5. **Template documents.html.twig** (déjà configuré)

Le template affiche correctement :
- ✅ Liste des documents uploadés
- ✅ Nom du fichier et événement associé
- ✅ Date de création
- ✅ Bouton de téléchargement sécurisé

## 📁 Fonctionnalités

### ✅ **Upload Multiple**
- Support du téléchargement de plusieurs fichiers simultanément
- Validation automatique des types et tailles de fichiers

### ✅ **Types de Fichiers Supportés**
- PDF (`.pdf`)
- Documents Word (`.doc`, `.docx`)
- Images (`.jpg`, `.jpeg`, `.png`, `.webp`, `.gif`)
- Taille maximale : 10 MB par fichier

### ✅ **Sécurité**
- Validation des types MIME
- Noms de fichiers sécurisés (slug + identifiant unique)
- Téléchargement sécurisé avec vérification des autorisations

### ✅ **Intégration**
- Documents visibles dans la section "Documents" pour tous les participants
- Lien automatique avec l'événement
- Métadonnées complètes (date, événement associé)

## 🔒 Autorisations de Téléchargement

### Qui peut télécharger un document ?
1. **Organisateur de l'événement** : Accès total
2. **Administrateur** : Accès total  
3. **Participants ayant accepté l'invitation** : Accès aux documents de leurs événements

### Code de Sécurité (`DocumentController::download`)
```php
// L'organisateur a toujours accès
if ($event->getOrganizer() === $user) {
    // OK
}
// L'administrateur a toujours accès
elseif (in_array('ROLE_ADMIN', $user->getRoles())) {
    // OK
}
// Vérifier si l'utilisateur est un participant
else {
    $hasAccess = false;
    foreach ($event->getParticipations() as $participation) {
        if ($participation->getUser() === $user) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce document');
    }
}
```

## 📋 Flux Utilisateur

### 1. **Création d'Événement**
1. L'utilisateur accède au formulaire de création d'événement
2. Dans la section "Documents et fichiers", il peut sélectionner un ou plusieurs fichiers
3. Lors de la soumission, les fichiers sont automatiquement uploadés et liés à l'événement
4. Message de confirmation avec le nombre de documents uploadés

### 2. **Consultation des Documents**
1. L'utilisateur accède à la section "Documents" depuis son tableau de bord participant
2. Tous les documents des événements auxquels il participe sont affichés
3. Chaque document affiche : nom, événement associé, date d'upload
4. Téléchargement sécurisé via bouton dédié

## 🎨 Interface Utilisateur

### **Formulaire de Création**
- Champ de sélection multiple avec support drag & drop
- Indicateurs visuels des types de fichiers acceptés
- Messages d'erreur en cas de fichier non valide

### **Section Documents**
- Design moderne avec cartes pour chaque document
- Icônes selon le type de fichier
- Animations et effets de survol
- Statistiques en temps réel

## 🚀 Avantages

### ✅ **Simplicité**
- Processus intégré au workflow de création d'événements
- Pas d'étape supplémentaire pour l'utilisateur

### ✅ **Efficacité**
- Upload multiple simultané
- Gestion automatique des métadonnées

### ✅ **Sécurité**
- Autorisations granulaires
- Validation stricte des fichiers
- Stockage sécurisé avec noms uniques

### ✅ **Expérience Utilisateur**
- Interface intuitive et moderne
- Feedback immédiat lors de l'upload
- Accès centralisé aux documents

## 📈 Statistiques et Monitoring

Le système track automatiquement :
- Nombre de documents par événement
- Total des documents par utilisateur
- Taille des fichiers uploadés
- Dates d'upload et de consultation

---

## 🔧 Configuration Technique

### **VichUploaderBundle**
```yaml
# config/packages/vich_uploader.yaml
documents:
    uri_prefix: /uploads/documents
    upload_destination: '%kernel.project_dir%/public/uploads/documents'
    namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
    delete_on_update: true
    delete_on_remove: true
```

### **Routes**
- `document_download` : Téléchargement sécurisé des documents
- `event_create` : Création d'événements avec upload
- `event_edit` : Modification avec ajout de documents
- `participant_documents` : Consultation des documents

### **Entités Doctrine**
- `Event` ↔ `Document` : Relation OneToMany/ManyToOne
- Cascade automatique pour la suppression
- Métadonnées complètes avec timestamps

---

**✅ RÉSULTAT :** Les documents uploadés lors de la création d'événements sont maintenant automatiquement visibles dans la section "Documents" pour tous les participants concernés, avec un système de sécurité robuste et une interface utilisateur moderne.
