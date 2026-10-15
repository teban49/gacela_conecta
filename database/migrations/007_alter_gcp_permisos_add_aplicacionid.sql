-- Migration: Alter GCP_Permisos add AplicacionId
-- Description: Agregar campo AplicacionId a la tabla GCP_Permisos
-- Version: 1.0
-- Date: 2026-08-05

ALTER TABLE GCP_Permisos 
ADD COLUMN AplicacionId INT NOT NULL DEFAULT 1 AFTER Id,
ADD INDEX idx_aplicacion (AplicacionId),
ADD CONSTRAINT fk_permisos_aplicacion FOREIGN KEY (AplicacionId) REFERENCES GCP_Aplicaciones(Id) ON DELETE CASCADE;
