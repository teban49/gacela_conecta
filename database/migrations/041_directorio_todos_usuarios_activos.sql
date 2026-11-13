-- El directorio muestra a todas las personas activas, tengan correo o no.
CREATE OR REPLACE VIEW directorio_gacela_conecta AS
SELECT u.Id AS id,
       u.Nombres AS first_name,
       u.Apellidos AS last_name,
       u.Direccion AS address,
       u.Telefono AS phone,
       u.EmailCorporativo AS email,
       u.Extension AS extension,
       COALESCE(NULLIF(u.Area, ''), a.Nombre) AS area_agencia,
       c.Nombre AS cargo,
       (SELECT GROUP_CONCAT(e.Nombre ORDER BY e.Id SEPARATOR ', ')
          FROM gcp_usuariosempresas ue
          INNER JOIN gcp_empresas e ON e.Id = ue.EmpresaId
         WHERE ue.UsuarioId = u.Id) AS empresa,
       u.FechaNacimiento AS fecha_nacimiento
FROM gcp_usuarios u
INNER JOIN gcp_agencias a ON a.Id = u.AgenciaId
INNER JOIN gcp_cargos c ON c.Id = u.CargoId
WHERE u.Estado = 1;
