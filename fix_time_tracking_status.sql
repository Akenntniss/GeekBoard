-- Script de correction des entrées time_tracking existantes
-- À exécuter sur le serveur pour corriger les données historiques

-- 1. Marquer comme 'completed' les entrées avec clock_out mais sans status correct
UPDATE time_tracking 
SET status = 'completed' 
WHERE clock_out IS NOT NULL 
  AND (status IS NULL OR status = '' OR status NOT IN ('active', 'break', 'completed'));

-- 2. Marquer comme 'active' les entrées sans clock_out (en cours)
UPDATE time_tracking 
SET status = 'active' 
WHERE clock_out IS NULL 
  AND (status IS NULL OR status = '' OR status NOT IN ('active', 'break'));

-- 3. Vérification - Afficher le nombre d'entrées par status
SELECT status, COUNT(*) as count FROM time_tracking GROUP BY status;

-- 4. Vérification - Afficher les demandes en attente
SELECT COUNT(*) as pending_approvals FROM time_tracking 
WHERE admin_approved = 0 AND status = 'completed';
