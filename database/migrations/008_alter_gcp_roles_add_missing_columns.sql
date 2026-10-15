-- Migration: Alter GCP_Roles add missing columns
-- Description: Agregar columnas faltantes a la tabla GCP_Roles
-- Version: 1.0
-- Date: 2026-08-05

ALTER TABLE gcp_roles 
ADD COLUMN Descripcion TEXT AFTER Codigo,
ADD COLUMN NivelAcceso INT DEFAULT 1 AFTER Descripcion;
