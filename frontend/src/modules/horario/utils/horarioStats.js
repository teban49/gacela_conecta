export const WEEKDAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
export const MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

export const sum = (rows, key) => rows.reduce((total, row) => total + (row[key] || 0), 0);

export const formatMinutes = (value) => {
  const minutes = Math.round(value || 0);
  if (minutes < 60) return `${minutes} min`;
  return `${Math.floor(minutes / 60)} h ${minutes % 60} min`;
};

export const monthKey = (date) => date.slice(0, 7);

export const monthLabel = (key) => {
  const [year, month] = key.split('-');
  return `${MONTHS[Number(month) - 1]} ${year}`;
};

export const totalsOf = (rows) => {
  const valid = rows.filter((row) => row.tipo !== 'inconsistente');
  const tarde = sum(valid, 'tarde');
  const temprano = sum(valid, 'temprano');
  const salidaTemprano = sum(valid, 'salidaTemprano');
  const salidaTarde = sum(valid, 'salidaTarde');
  const festivo = sum(valid.filter((row) => row.tipo === 'festivo'), 'favor');
  const favor = temprano + salidaTarde + festivo;
  const contra = tarde + salidaTemprano;
  return { tarde, temprano, salidaTemprano, salidaTarde, festivo, favor, contra, balance: favor - contra, dias: valid.length };
};

// Minutos de llegada tarde y días con retraso por día de la semana (solo días laborales)
export const byWeekday = (rows) => WEEKDAYS.slice(0, 5).map((name, index) => {
  const dayRows = rows.filter((row) => row.dow === index + 1 && row.tipo === 'normal');
  const late = dayRows.filter((row) => row.tarde > 0);
  return {
    dia: name,
    minutosTarde: sum(dayRows, 'tarde'),
    promedioTarde: dayRows.length ? Math.round(sum(dayRows, 'tarde') / dayRows.length) : 0,
    llegadasTarde: late.length,
  };
});

export const byPerson = (rows) => {
  const map = new Map();
  rows.forEach((row) => {
    if (!map.has(row.pid)) map.set(row.pid, { pid: row.pid, nombre: row.nombre, area: row.area, rows: [] });
    map.get(row.pid).rows.push(row);
  });
  return [...map.values()]
    .map((person) => ({ ...person, ...totalsOf(person.rows) }))
    .sort((a, b) => a.nombre.localeCompare(b.nombre));
};

export const byArea = (rows) => {
  const map = new Map();
  rows.forEach((row) => {
    if (!map.has(row.area)) map.set(row.area, []);
    map.get(row.area).push(row);
  });
  return [...map.entries()]
    .map(([area, areaRows]) => ({ area, personas: new Set(areaRows.map((row) => row.pid)).size, ...totalsOf(areaRows) }))
    .sort((a, b) => b.contra - a.contra);
};

export const byDate = (rows) => {
  const map = new Map();
  rows.filter((row) => row.tipo !== 'inconsistente').forEach((row) => {
    const current = map.get(row.fecha) || { fecha: row.fecha, favor: 0, contra: 0 };
    current.favor += row.favor;
    current.contra += row.contra;
    map.set(row.fecha, current);
  });
  return [...map.values()].sort((a, b) => a.fecha.localeCompare(b.fecha));
};

export const formatDate = (value) => {
  const [year, month, day] = value.split('-');
  return `${day}/${month}/${year}`;
};
