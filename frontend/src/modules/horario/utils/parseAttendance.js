import { unzipSync, strFromU8 } from 'fflate';

const decodeXml = (text) => text
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'")
  .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)))
  .replace(/&amp;/g, '&');

const textOf = (xml) => {
  let out = '';
  for (const match of xml.matchAll(/<t[^>]*>([\s\S]*?)<\/t>/g)) out += match[1];
  return decodeXml(out);
};

const pad = (value) => String(value).padStart(2, '0');
const formatUtc = (date) => `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())} ${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())}`;

// Excel guarda las fechas como número de días desde 1899-12-30
const serialToDateTime = (serial) => formatUtc(new Date(Math.round((serial - 25569) * 86400) * 1000));

const parseTime = (raw) => {
  const value = String(raw ?? '').trim();
  if (value === '') return null;
  if (/^\d+(\.\d+)?$/.test(value)) return serialToDateTime(Number(value));
  const iso = value.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{1,2}):(\d{2})(?::(\d{2}))?/);
  if (iso) return `${iso[1]}-${iso[2]}-${iso[3]} ${pad(iso[4])}:${iso[5]}:${iso[6] ?? '00'}`;
  const latam = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})[ T](\d{1,2}):(\d{2})(?::(\d{2}))?/);
  if (latam) return `${latam[3]}-${pad(latam[2])}-${pad(latam[1])} ${pad(latam[4])}:${latam[5]}:${latam[6] ?? '00'}`;
  return null;
};

const columnOf = (ref) => ref.replace(/\d+/g, '');

/**
 * Lee un .xlsx de asistencia (columnas Person ID, Name y Time) y devuelve las marcaciones.
 * Las filas con formato pero sin datos se ignoran.
 */
export const parseAttendanceWorkbook = async (file) => {
  const buffer = new Uint8Array(await file.arrayBuffer());
  const files = unzipSync(buffer, {
    filter: (entry) => /^xl\/(sharedStrings\.xml|worksheets\/sheet\d+\.xml)$/.test(entry.name),
  });

  const sheetName = Object.keys(files).filter((name) => name.startsWith('xl/worksheets/')).sort()[0];
  if (!sheetName) throw new Error('El archivo no contiene hojas de cálculo.');

  const shared = [];
  if (files['xl/sharedStrings.xml']) {
    for (const match of strFromU8(files['xl/sharedStrings.xml']).matchAll(/<si>([\s\S]*?)<\/si>/g)) shared.push(textOf(match[1]));
  }

  const sheet = strFromU8(files[sheetName]);
  const rows = [];
  for (const rowMatch of sheet.matchAll(/<row\b[^>]*?(?:\/>|>([\s\S]*?)<\/row>)/g)) {
    if (!rowMatch[1]) continue;
    const cells = {};
    for (const cell of rowMatch[1].matchAll(/<c r="([A-Z]+\d+)"([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g)) {
      const [, ref, attrs, inner = ''] = cell;
      const type = attrs.match(/\bt="(\w+)"/)?.[1];
      let value;
      if (type === 's') value = shared[Number(inner.match(/<v>([\s\S]*?)<\/v>/)?.[1])];
      else if (type === 'inlineStr') value = textOf(inner);
      else value = decodeXml(inner.match(/<v>([\s\S]*?)<\/v>/)?.[1] ?? '');
      if (value !== undefined && value !== '') cells[columnOf(ref)] = value;
    }
    if (Object.keys(cells).length) rows.push(cells);
  }

  if (!rows.length) throw new Error('El archivo está vacío.');

  const header = rows[0];
  const findColumn = (label, fallback) => Object.keys(header).find((col) => String(header[col]).trim().toLowerCase() === label) ?? fallback;
  const idCol = findColumn('person id', 'A');
  const nameCol = findColumn('name', 'B');
  const timeCol = findColumn('time', 'D');
  if (!/person id|name|time/i.test(Object.values(header).join(' '))) {
    throw new Error('No se encontraron las columnas Person ID, Name y Time en la primera fila.');
  }

  const registros = [];
  let ignoradas = 0;
  for (const row of rows.slice(1)) {
    const fecha_hora = parseTime(row[timeCol]);
    const person_id = String(row[idCol] ?? '').replace(/\D/g, '');
    const nombre = String(row[nameCol] ?? '').trim();
    if (!fecha_hora || !person_id || !nombre) {
      ignoradas += 1;
      continue;
    }
    registros.push({ person_id, nombre, fecha_hora });
  }

  return { registros, ignoradas };
};
