-- 🔧 SCRIPT DE RÉSOLUTION DES CONTRAINTES DE CLÉS ÉTRANGÈRES
-- =============================================================

-- 1️⃣ VÉRIFIER LES CONTRAINTES EXISTANTES
-- ----------------------------------------
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = 'my_database'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- 2️⃣ VÉRIFIER LES NOTIFICATIONS D'UN UTILISATEUR SPÉCIFIQUE
-- -----------------------------------------------------------
-- Remplacez USER_ID par l'ID de l'utilisateur à supprimer
-- SELECT COUNT(*) as total_notifications FROM notification WHERE user_id = USER_ID;

-- 3️⃣ SUPPRIMER LES NOTIFICATIONS D'UN UTILISATEUR
-- ------------------------------------------------
-- Remplacez USER_ID par l'ID de l'utilisateur à supprimer
-- DELETE FROM notification WHERE user_id = USER_ID;

-- 4️⃣ SUPPRIMER L'UTILISATEUR (après avoir supprimé les notifications)
-- --------------------------------------------------------------------
-- Remplacez USER_ID par l'ID de l'utilisateur à supprimer
-- DELETE FROM users WHERE id = USER_ID;

-- 5️⃣ VÉRIFIER LES AUTRES TABLES AVEC DES CONTRAINTES
-- ---------------------------------------------------
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = 'my_database'
AND REFERENCED_TABLE_NAME = 'users'
ORDER BY TABLE_NAME;

-- 6️⃣ OPTION : DÉSACTIVER TEMPORAIREMENT LES VÉRIFICATIONS DE CLÉS ÉTRANGÈRES
-- ---------------------------------------------------------------------------
-- ATTENTION : Utilisez cette option avec précaution !
-- SET FOREIGN_KEY_CHECKS = 0;

-- 7️⃣ SUPPRIMER L'UTILISATEUR ET TOUTES SES DONNÉES ASSOCIÉES
-- -------------------------------------------------------------
-- Remplacez USER_ID par l'ID de l'utilisateur à supprimer
-- DELETE FROM users WHERE id = USER_ID;

-- 8️⃣ RÉACTIVER LES VÉRIFICATIONS DE CLÉS ÉTRANGÈRES
-- --------------------------------------------------
-- SET FOREIGN_KEY_CHECKS = 1;
