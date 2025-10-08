-- Migration des disponibilités déjà approuvées
INSERT INTO disponibilites_approuvees (
    disponibilite_id,
    user_id,
    date_dispo,
    periode,
    matiere_id,
    level_id,
    date_approbation,
    approbateur_id,
    statut,
    date_creation
)
SELECT 
    d.id as disponibilite_id,
    d.user_id,
    d.date_dispo,
    d.periode,
    ts.matiere_id,
    m.level_id,
    d.date_creation as date_approbation,
    (SELECT id FROM users WHERE role = 'admin' LIMIT 1) as approbateur_id,
    'confirmee' as statut,
    d.date_creation
FROM disponibilites d
JOIN teacher_subjects ts ON d.user_id = ts.user_id
JOIN matieres m ON ts.matiere_id = m.id
WHERE d.statut = 'approuve'
AND NOT EXISTS (
    SELECT 1 
    FROM disponibilites_approuvees da 
    WHERE da.disponibilite_id = d.id
);

-- Mise à jour des dates de modification
UPDATE disponibilites_approuvees 
SET date_modification = date_creation 
WHERE date_modification = date_creation; 