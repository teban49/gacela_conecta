import React, { useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { COLORS, Card, Empty, InconsistencyTable, Kpi, Minutes, Table, WeekdayChart } from '../components/HorarioParts';
import { WEEKDAYS, byPerson, byWeekday, formatDate, formatMinutes, totalsOf } from '../utils/horarioStats';

const select = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-sky-400';

const compliance = (rows) => {
  const valid = rows.filter((row) => row.tipo !== 'inconsistente');
  if (!valid.length) return 0;
  return Math.round((valid.filter((row) => row.contra === 0).length / valid.length) * 100);
};

const TopList = ({ title, subtitle, items, valueKey, tone }) => (
  <Card title={title} subtitle={subtitle}>
    {items.length === 0 ? <Empty>Sin datos.</Empty> : (
      <ol className="space-y-3">
        {items.map((person, index) => {
          const max = items[0][valueKey] || 1;
          return (
            <li key={person.pid}>
              <div className="flex justify-between text-sm">
                <span className="font-medium text-slate-700">{index + 1}. {person.nombre}</span>
                <span className={tone === 'favor' ? 'font-semibold text-green-700' : 'font-semibold text-red-700'}>{formatMinutes(person[valueKey])}</span>
              </div>
              <div className="mt-1 h-2 rounded-full bg-slate-100">
                <div className="h-2 rounded-full" style={{ width: `${Math.max(4, (person[valueKey] / max) * 100)}%`, backgroundColor: tone === 'favor' ? COLORS.favor : COLORS.contra }} />
              </div>
            </li>
          );
        })}
      </ol>
    )}
  </Card>
);

const PersonTab = ({ rows, areas, areaLimit = null }) => {
  const [area, setArea] = useState('');
  const [pid, setPid] = useState('');
  const activeArea = areaLimit ?? area;

  const people = useMemo(() => byPerson(rows), [rows]);
  const peopleInArea = useMemo(() => people.filter((person) => !activeArea || person.area === activeArea), [people, activeArea]);
  const selected = peopleInArea.find((person) => person.pid === pid) ?? null;

  const scopedRows = useMemo(
    () => rows.filter((row) => (!activeArea || row.area === activeArea) && (!selected || row.pid === selected.pid)),
    [rows, activeArea, selected]
  );
  const totals = useMemo(() => totalsOf(scopedRows), [scopedRows]);
  const weekdays = useMemo(() => byWeekday(scopedRows), [scopedRows]);
  const ranking = useMemo(() => byPerson(scopedRows), [scopedRows]);
  const topDebt = useMemo(() => [...ranking].filter((p) => p.contra > 0).sort((a, b) => b.contra - a.contra).slice(0, 5), [ranking]);
  const topFavor = useMemo(() => [...ranking].filter((p) => p.favor > 0).sort((a, b) => b.favor - a.favor).slice(0, 5), [ranking]);

  return (
    <div className="space-y-6">
      <Card>
        <div className="grid gap-3 sm:grid-cols-2">
          <label className="text-sm font-medium text-slate-600">Área
            <select className={`${select} mt-1 w-full`} value={activeArea} disabled={Boolean(areaLimit)} onChange={(e) => { setArea(e.target.value); setPid(''); }}>
              {!areaLimit && <option value="">Todas las áreas</option>}
              {areas.map((item) => <option key={item} value={item}>{item}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium text-slate-600">Persona
            <select className={`${select} mt-1 w-full`} value={selected?.pid ?? ''} onChange={(e) => setPid(e.target.value)}>
              <option value="">Todas las personas</option>
              {peopleInArea.map((person) => <option key={person.pid} value={person.pid}>{person.nombre}</option>)}
            </select>
          </label>
        </div>
      </Card>

      {scopedRows.length === 0 ? <Empty>No hay marcaciones para el filtro seleccionado.</Empty> : (
        <>
          <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <Kpi label="Cumplimiento" value={`${compliance(scopedRows)}%`} hint="Días sin minutos en contra" tone="blue" />
            <Kpi label="Total a favor" value={formatMinutes(totals.favor)} tone="green" />
            <Kpi label="Total en contra" value={formatMinutes(totals.contra)} tone="red" />
            <Kpi label="Balance" value={`${totals.balance >= 0 ? '+' : '-'}${formatMinutes(Math.abs(totals.balance))}`} tone={totals.balance >= 0 ? 'green' : 'red'} />
            <Kpi label="Días registrados" value={totals.dias} tone="slate" />
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <WeekdayChart data={weekdays} />
            <Card title="Detalle de minutos" subtitle={selected ? selected.nombre : activeArea || 'Todo el personal'}>
              <div className="h-72">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={[
                    { concepto: 'Llegada tarde', minutos: totals.tarde, fill: '#dc2626' },
                    { concepto: 'Salida temprano', minutos: totals.salidaTemprano, fill: '#f97316' },
                    { concepto: 'Llegada temprano', minutos: totals.temprano, fill: '#16a34a' },
                    { concepto: 'Salida tarde', minutos: totals.salidaTarde + totals.festivo, fill: '#0ea5e9' },
                  ]}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="concepto" interval={0} tick={{ fontSize: 12 }} />
                    <YAxis />
                    <Tooltip formatter={(value) => `${value} min`} />
                    <Bar dataKey="minutos" name="Minutos" radius={[6, 6, 0, 0]} shape={(props) => <rect {...props} fill={props.payload.fill} rx={6} />} animationDuration={1000} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </Card>
          </div>

          {!selected && (
            <div className="grid gap-6 lg:grid-cols-2">
              <TopList title="Top: más minutos en contra" subtitle={`Quienes más deben${area ? ` en ${area}` : ''}`} items={topDebt} valueKey="contra" tone="contra" />
              <TopList title="Top: más minutos a favor" subtitle={`Quienes más aportan${area ? ` en ${area}` : ''}`} items={topFavor} valueKey="favor" tone="favor" />
            </div>
          )}

          {!selected ? (
            <Card title="Cumplimiento por persona" subtitle="Minutos acumulados en el periodo.">
              <div className="h-80">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={ranking}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="nombre" interval={0} angle={-35} textAnchor="end" height={110} tick={{ fontSize: 11 }} />
                    <YAxis />
                    <Tooltip formatter={(value) => `${value} min`} />
                    <Legend verticalAlign="top" />
                    <Bar dataKey="favor" name="A favor" fill={COLORS.favor} radius={[4, 4, 0, 0]} animationDuration={1000} />
                    <Bar dataKey="contra" name="En contra" fill={COLORS.contra} radius={[4, 4, 0, 0]} animationDuration={1000} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
              <div className="mt-4">
                <Table
                  fileName="cumplimiento_por_persona"
                  rows={ranking.map((person) => ({ ...person, key: person.pid }))}
                  columns={[
                    { key: 'nombre', label: 'Persona', render: (row) => <button type="button" className="text-left font-medium text-sky-700 hover:underline" onClick={() => { setArea(row.area === 'Sin área' ? '' : row.area); setPid(row.pid); }}>{row.nombre}</button> },
                    { key: 'area', label: 'Área' },
                    { key: 'tarde', label: 'Llegada tarde', right: true, render: (row) => <Minutes value={row.tarde} tone="contra" /> },
                    { key: 'salidaTemprano', label: 'Salida temprano', right: true, render: (row) => <Minutes value={row.salidaTemprano} tone="contra" /> },
                    { key: 'favor', label: 'A favor', right: true, render: (row) => <Minutes value={row.favor} tone="favor" /> },
                    { key: 'balance', label: 'Balance', right: true, render: (row) => <span className={`font-semibold ${row.balance >= 0 ? 'text-green-700' : 'text-red-700'}`}>{row.balance > 0 ? '+' : ''}{row.balance}</span> },
                    { key: 'cumpl', label: 'Cumplimiento', right: true, render: (row) => `${compliance(row.rows)}%` },
                  ]}
                />
              </div>
            </Card>
          ) : (
            <Card title={`Detalle diario de ${selected.nombre}`}>
              <Table
                fileName="detalle_diario_persona"
                rows={scopedRows.map((row) => ({ ...row, key: row.fecha }))}
                columns={[
                  { key: 'fecha', label: 'Fecha', render: (row) => `${formatDate(row.fecha)} · ${WEEKDAYS[row.dow - 1].slice(0, 3)}` },
                  { key: 'entrada', label: 'Entrada', render: (row) => row.entrada ?? '—' },
                  { key: 'salida', label: 'Salida', render: (row) => row.salida ?? '—' },
                  { key: 'tarde', label: 'Llegó tarde', right: true, render: (row) => <Minutes value={row.tarde} tone="contra" /> },
                  { key: 'salidaTemprano', label: 'Salió temprano', right: true, render: (row) => <Minutes value={row.salidaTemprano} tone="contra" /> },
                  { key: 'temprano', label: 'Llegó temprano', right: true, render: (row) => <Minutes value={row.temprano} tone="favor" /> },
                  { key: 'salidaTarde', label: 'Salió tarde', right: true, render: (row) => <Minutes value={row.tipo === 'festivo' ? row.favor : row.salidaTarde} tone="favor" /> },
                  { key: 'estado', label: 'Estado', render: (row) => (row.tipo === 'inconsistente' ? 'Marcación incompleta' : row.tipo === 'festivo' ? (row.festivo || 'Fin de semana') : 'Normal') },
                ]}
              />
            </Card>
          )}

          <InconsistencyTable rows={scopedRows} />
        </>
      )}
    </div>
  );
};

export default PersonTab;
