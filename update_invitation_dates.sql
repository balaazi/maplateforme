-- Mettre à jour les dates de création des invitations en attente
UPDATE invitation 
SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY)
WHERE status = 'pending';
