-- Elimina tablas de respaldo que ningún código utiliza.
-- directorio_gacela_conecta ahora es una vista sobre gcp_usuarios (migración 039).
DROP TABLE IF EXISTS directorio_gacela_conecta_bak;
DROP TABLE IF EXISTS directorio_gacela_conecta_legacy;
DROP TABLE IF EXISTS entregas_dotacion_bak;
