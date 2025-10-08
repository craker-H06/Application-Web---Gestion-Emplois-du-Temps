-- Ajouter la colonne statut à la table disponibilites
ALTER TABLE disponibilites
ADD COLUMN statut ENUM('en_attente', 'approuve', 'rejete') NOT NULL DEFAULT 'en_attente';

-- Mettre à jour les disponibilités existantes avec le statut par défaut
UPDATE disponibilites SET statut = 'en_attente' WHERE statut IS NULL; 