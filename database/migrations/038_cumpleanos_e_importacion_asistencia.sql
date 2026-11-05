-- Fecha de nacimiento en el directorio para calcular los cumpleaños.
ALTER TABLE directorio_gacela_conecta
    ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL AFTER empresa;

-- Evita registrar dos veces la misma marcación al importar archivos de asistencia.
ALTER TABLE control_asistencia
    ADD UNIQUE INDEX IF NOT EXISTS uq_control_asistencia_marcacion (person_id, fecha_hora);
