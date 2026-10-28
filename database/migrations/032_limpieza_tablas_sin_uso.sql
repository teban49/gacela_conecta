-- Limpieza: tablas sin uso en el código (foro, etiquetas/plantillas/estadísticas de tickets,
-- incidencias legadas, auditoría vacía y tablas legadas de la intranet).
DROP TABLE IF EXISTS intranet_foro_votos;
DROP TABLE IF EXISTS intranet_foro_respuestas;
DROP TABLE IF EXISTS intranet_foro_temas;
DROP TABLE IF EXISTS intranet_foro_categorias;
DROP TABLE IF EXISTS gestion_tickets_etiquetas_rel;
DROP TABLE IF EXISTS gestion_tickets_etiquetas;
DROP TABLE IF EXISTS gestion_tickets_plantillas;
DROP TABLE IF EXISTS gestion_tickets_estadisticas;
DROP TABLE IF EXISTS gestion_incidencias;
DROP TABLE IF EXISTS gcp_auditlog;
DROP TABLE IF EXISTS GCP_Intranet_Organigrama;
DROP TABLE IF EXISTS GCP_Intranet_Noticias;
DROP TABLE IF EXISTS GCP_Intranet_Manuales;
DROP TABLE IF EXISTS GCP_Intranet_Pildoras;
DROP TABLE IF EXISTS GCP_Intranet_Links;
