<?php
// Normaliza entregas_dotacion (histÃ³rico) en cabecera + items. Respaldo en entregas_dotacion_bak.
$cfg = require __DIR__ . '/../config/database.php';
$db = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['host'] ?? '127.0.0.1', $cfg['database'] ?? $cfg['dbname'] ?? 'gacela_conecta_beta'),
    $cfg['username'] ?? $cfg['user'] ?? 'root',
    $cfg['password'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$has = fn($t) => (bool) $db->query("SHOW TABLES LIKE '$t'")->fetchColumn();
if ($has('entregas_dotacion_bak')) {
    exit("Ya existe entregas_dotacion_bak; migraciÃ³n ya aplicada.\n");
}

$empresas = [];
foreach ($db->query('SELECT Id, Nombre FROM gcp_empresas') as $e) {
    $empresas[] = [(int) $e['Id'], strtolower($e['Nombre'])];
}
$empresaId = function (string $name) use ($empresas) {
    $n = strtolower($name);
    $map = ['rapido' => 'rapido', 'paz de rio' => 'paz de rio', 'flota' => 'flota'];
    foreach ($map as $needle => $_) {
        if (strpos($n, $needle) !== false) {
            foreach ($empresas as [$id, $nom]) {
                if (strpos($nom, $needle) !== false) return $id;
            }
        }
    }
    return null;
};

$db->exec('RENAME TABLE entregas_dotacion TO entregas_dotacion_bak');
$db->exec("CREATE TABLE entregas_dotacion (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  empresa_id INT NOT NULL,
  cedula VARCHAR(30) NOT NULL,
  fecha_entrega DATE NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_entregas_cedula (cedula, fecha_entrega),
  KEY idx_entregas_empresa (empresa_id),
  CONSTRAINT fk_entregas_empresa FOREIGN KEY (empresa_id) REFERENCES gcp_empresas (Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$db->exec("CREATE TABLE entregas_dotacion_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entrega_id BIGINT UNSIGNED NOT NULL,
  cantidad SMALLINT UNSIGNED NOT NULL,
  referencia VARCHAR(150) NOT NULL,
  talla VARCHAR(20) NULL,
  PRIMARY KEY (id),
  KEY idx_entrega_items (entrega_id),
  CONSTRAINT fk_entrega_items FOREIGN KEY (entrega_id) REFERENCES entregas_dotacion (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$h = $db->prepare('INSERT INTO entregas_dotacion (id, empresa_id, cedula, fecha_entrega, creado_en) VALUES (?,?,?,?,?)');
$i = $db->prepare('INSERT INTO entregas_dotacion_items (entrega_id, cantidad, referencia, talla) VALUES (?,?,?,?)');

$db->beginTransaction();
$rows = 0; $items = 0;
foreach ($db->query('SELECT * FROM entregas_dotacion_bak ORDER BY id') as $r) {
    $eid = $empresaId($r['empresa']);
    if ($eid === null) throw new RuntimeException('Empresa no reconocida: ' . $r['empresa']);
    $cedula = preg_replace('/\D+/', '', $r['cedula']);
    $h->execute([$r['id'], $eid, $cedula, $r['fecha_entrega'], $r['creado_en']]);
    $rows++;
    $list = json_decode((string) $r['detalle_dotacion'], true);
    foreach (is_array($list) ? $list : [] as $it) {
        $ref = trim((string) ($it['prenda'] ?? ''));
        $cant = (int) ($it['cantidad'] ?? 0);
        if ($ref === '' || $cant < 1) continue;
        $talla = trim((string) ($it['talla'] ?? ''));
        $i->execute([$r['id'], $cant, $ref, $talla === '' ? null : $talla]);
        $items++;
    }
}
$db->commit();
echo "Entregas: $rows, items: $items\n";
