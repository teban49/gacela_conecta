ALTER TABLE gestion_tickets
    ADD COLUMN IF NOT EXISTS SatisfaccionTiempoSolucion ENUM('muy_satisfecho', 'satisfecho', 'insatisfecho', 'muy_insatisfecho') NULL AFTER Satisfaccion,
    ADD COLUMN IF NOT EXISTS SatisfaccionCalidadAtencion ENUM('muy_satisfecho', 'satisfecho', 'insatisfecho', 'muy_insatisfecho') NULL AFTER SatisfaccionTiempoSolucion,
    ADD COLUMN IF NOT EXISTS SatisfaccionEficaciaSolucion ENUM('muy_satisfecho', 'satisfecho', 'insatisfecho', 'muy_insatisfecho') NULL AFTER SatisfaccionCalidadAtencion;
