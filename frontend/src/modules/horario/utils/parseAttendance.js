import { unzipSync, strFromU8 } from 'fflate';

const decodeXml = (text) => text
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'")
  .replace(/&#(x[0-9a-f]+|\d+);/gi, (_, code) => {
    const codePoint = code[0].toLowerCase() === 'x' ? Number.parseInt(code.slice(1), 16) : Number(code);
    return codePoint <= 0x10ffff && !(codePoint >= 0xd800 && codePoint <= 0xdfff)
      ? String.fromCodePoint(codePoint)
      : `&#${code};`;
  })
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

const normalizeHeader = (value) => String(value ?? '').replace(/^\uFEFF/, '').trim().toLowerCase();
const ATTENDANCE_CHECK_POINT = 'Asistencia_Puerta1_Lector de tarjetas de entrada1';

const parseDelimitedRows = (text, delimiter) => {
  const rows = [];
  let row = [];
  let value = '';
  let quoted = false;
  for (let i = 0; i < text.length; i += 1) {
    const character = text[i];
    if (character === '"') {
      if (quoted && text[i + 1] === '"') {
        value += '"';
        i += 1;
      } else {
        quoted = !quoted;
      }
    } else if (character === delimiter && !quoted) {
      row.push(value);
      value = '';
    } else if ((character === '\n' || character === '\r') && !quoted) {
      if (character === '\r' && text[i + 1] === '\n') i += 1;
      row.push(value);
      if (row.some((cell) => cell.trim() !== '')) rows.push(row);
      row = [];
      value = '';
    } else {
      value += character;
    }
  }
  if (value !== '' || row.length) {
    row.push(value);
    if (row.some((cell) => cell.trim() !== '')) rows.push(row);
  }
  return rows;
};

const parseAttendanceRows = (rows, getValue) => {
  if (!rows.length) throw new Error('El archivo está vacío.');
  const header = rows[0];
  const findColumn = (name) => header.findIndex((value) => normalizeHeader(value) === name);
  const idCol = findColumn('person id');
  const nameCol = findColumn('name');
  const timeCol = findColumn('time');
  const checkPointCol = findColumn('attendance check point');
  if ([idCol, nameCol, timeCol, checkPointCol].some((column) => column < 0)) {
    throw new Error('No se encontraron las columnas Person ID, Name, Time y Attendance Check Point en el encabezado.');
  }

  const registros = [];
  let ignoradas = 0;
  let filtradas = 0;
  for (const row of rows.slice(1)) {
    if (String(getValue(row, checkPointCol) ?? '').trim() !== ATTENDANCE_CHECK_POINT) {
      filtradas += 1;
      continue;
    }
    const fecha_hora = parseTime(getValue(row, timeCol));
    const person_id = String(getValue(row, idCol) ?? '').replace(/\D/g, '');
    const nombre = String(getValue(row, nameCol) ?? '').trim();
    if (!fecha_hora || !person_id || !nombre) {
      ignoradas += 1;
      continue;
    }
    registros.push({ person_id, nombre, fecha_hora });
  }
  return { registros, ignoradas, filtradas };
};

const columnOf = (ref) => ref.replace(/\d+/g, '');

const decodeTextFile = async (file) => {
  const buffer = await file.arrayBuffer();
  try {
    return new TextDecoder('utf-8', { fatal: true }).decode(buffer);
  } catch {
    return new TextDecoder('windows-1252').decode(buffer);
  }
};

/**
 * Lee un archivo de asistencia Excel o texto (CSV/TSV/TXT) y devuelve las marcaciones.
 */
export const parseAttendanceWorkbook = async (file) => {
  const extension = file.name?.split('.').pop()?.toLowerCase();
  if (['csv', 'tsv', 'txt'].includes(extension) || file.type?.startsWith('text/')) {
    const text = await decodeTextFile(file);
    const firstLine = text.split(/\r?\n/, 1)[0];
    const delimiter = ['\t', ',', ';'].reduce((best, candidate) => (
      parseDelimitedRows(firstLine, candidate)[0]?.length > parseDelimitedRows(firstLine, best)[0]?.length ? candidate : best
    ), ',');
    return parseAttendanceRows(parseDelimitedRows(text, delimiter), (row, column) => row[column]);
  }
  if (extension !== 'xlsx') {
    throw new Error('Formato no compatible. Selecciona un archivo .xlsx, .csv, .tsv o .txt.');
  }

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
  const columns = ['person id', 'name', 'time', 'attendance check point'].map((name) => (
    Object.keys(header).find((col) => normalizeHeader(header[col]) === name)
  ));
  if (columns.some((column) => column === undefined)) {
    throw new Error('No se encontraron las columnas Person ID, Name, Time y Attendance Check Point en la primera fila.');
  }
  const normalizedRows = [
    ['person id', 'name', 'time', 'attendance check point'],
    ...rows.slice(1).map((row) => columns.map((column) => row[column])),
  ];
  return parseAttendanceRows(normalizedRows, (row, columnIndex) => row[columnIndex]);
};
