import React, { useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { CalendarX, Trophy, Users } from 'lucide-react';
import { COLORS, Card, Empty, Kpi, Table } from '../components/HorarioParts';
import { WEEKDAYS, formatDate, monthKey, monthLabel } from '../utils/horarioStats';

const AbsencesTab = ({ absences, areas, areaLimit = null }) => {
  const [area, setArea] = useState('');
  const activeArea = areaLimit ?? area;
  const items = useMemo(() => absences.filter((item) => !activeArea || item.area === activeArea), [absences, activeArea]);

  const months = useMemo(() => [...new Set(items.map((item) => monthKey(item.fecha)))].sort(), [items]);

  const people = useMemo(() => {
    const map = new Map();
    items.forEach((item) => {
      if (!map.has(item.pid)) map.set(item.pid, { pid: item.pid, nombre: item.nombre, area: item.area, total: 0, meses: {} });
      const person = map.get(item.pid);
      const key = monthKey(item.fecha);
      person.total += 1;
      person.meses[key] = (person.meses[key] || 0) + 1;
    });
    return [...map.values()].sort((a, b) => b.total - a.total);
  }, [items]);

  const monthly = useMemo(() => months.map((key) => ({ mes: monthLabel(key), ausencias: items.filter((item) => monthKey(item.fecha) === key).length })), [items, months]);

  const weekdays = useMemo(() => WEEKDAYS.slice(0, 5).map((dia, index) => ({
    dia,
    ausencias: items.filter((item) => new Date(`${item.fecha}T00:00:00`).getDay() === (index + 1)).length,
  })), [items]);

  const top = people[0];

  return (
    <div className="space-y-6">
      <Card>
        <label className="text-sm font-medium text-slate-600">Área
          <select className="mt-1 w-full max-w-sm rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm sm:block" value={activeArea} disabled={Boolean(areaLimit)} onChange={(e) => setArea(e.target.value)}>
            {!areaLimit && <option value="">Todas las áreas</option>}
            {areas.map((item) => <option key={item} value={item}>{item}</option>)}
          </select>
        </label>
      </Card>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Kpi label="Ausencias totales" value={items.length} hint="Días hábiles sin marcación" tone="red" icon={CalendarX} />
        <Kpi label="Personas con ausencias" value={people.length} tone="amber" icon={Users} />
        <Kpi label="Promedio por persona" value={people.length ? (items.length / people.length).toFixed(1) : '0'} tone="slate" />
        <Kpi label="Más ausencias" value={top ? top.total : 0} hint={top?.nombre ?? 'Sin ausencias'} tone="blue" icon={Trophy} />
      </div>

      {items.length === 0 ? <Empty>No hay ausencias registradas en el periodo.</Empty> : (
        <>
          <div className="grid gap-6 lg:grid-cols-2">
            <Card title="Ausencias por mes" subtitle="Total de días de ausencia en cada mes del periodo.">
              <div className="h-72">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={monthly}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="mes" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="ausencias" name="Ausencias" fill={COLORS.contra} radius={[6, 6, 0, 0]} animationDuration={1000} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </Card>
            <Card title="Ausencias por día de la semana" subtitle="Útil para detectar patrones, como ausencias en lunes o viernes.">
              <div className="h-72">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={weekdays}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="dia" />
                    <YAxis allowDecimals={false} />
                    <Tooltip />
                    <Bar dataKey="ausencias" name="Ausencias" fill={COLORS.warn} radius={[6, 6, 0, 0]} animationDuration={1000} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </Card>
          </div>

          <Card title="Ausencias por persona" subtitle="Ordenadas de mayor a menor.">
            <div className="h-80">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={people.slice(0, 15)} layout="vertical" margin={{ left: 100 }}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis type="number" allowDecimals={false} />
                  <YAxis type="category" dataKey="nombre" width={180} tick={{ fontSize: 11 }} />
                  <Tooltip />
                  <Legend />
                  <Bar dataKey="total" name="Ausencias" fill={COLORS.contra} radius={[0, 6, 6, 0]} animationDuration={1000} />
                </BarChart>
              </ResponsiveContainer>
            </div>
            <div className="mt-4">
              <Table
                fileName="ausencias_por_persona"
                rows={people.map((person) => ({ ...person, key: person.pid }))}
                columns={[
                  { key: 'nombre', label: 'Persona' },
                  { key: 'area', label: 'Área' },
                  ...months.map((key) => ({ key, label: monthLabel(key), right: true, render: (row) => row.meses[key] || 0 })),
                  { key: 'total', label: 'Total', right: true, render: (row) => <span className="font-semibold text-red-700">{row.total}</span> },
                ]}
              />
            </div>
          </Card>

          <Card title="Detalle de ausencias" subtitle="Fechas exactas en las que no hubo marcación.">
            <Table
              fileName="detalle_ausencias"
              rows={[...items].sort((a, b) => b.fecha.localeCompare(a.fecha)).map((item) => ({ ...item, key: `${item.pid}-${item.fecha}` }))}
              columns={[
                { key: 'fecha', label: 'Fecha', render: (row) => formatDate(row.fecha) },
                { key: 'dia', label: 'Día', render: (row) => WEEKDAYS[(new Date(`${row.fecha}T00:00:00`).getDay() + 6) % 7] },
                { key: 'nombre', label: 'Persona' },
                { key: 'area', label: 'Área' },
              ]}
            />
          </Card>
        </>
      )}
      <p className="text-xs text-slate-500">Se considera ausencia un día hábil (sin sábados, domingos ni festivos) sin ninguna marcación, desde la primera vez que la persona aparece en el control de asistencia.</p>
    </div>
  );
};

export default AbsencesTab;
