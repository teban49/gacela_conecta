import React, { useRef, useState } from 'react';
import {
  Bar, BarChart, CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis,
} from 'recharts';
import { formatDate, formatMinutes } from '../utils/horarioStats';
import { Download } from 'lucide-react';
import { downloadTableAsXlsx } from '../utils/exportTable';

export const COLORS = { favor: '#16a34a', contra: '#dc2626', neutral: '#0ea5e9', warn: '#f59e0b' };
const ANIMATION_DURATION = 1000;

export const Card = ({ title, subtitle, headerAction, children, className = '' }) => (
  <section className={`rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ${className}`}>
    {title && (
      <header className="mb-4 flex items-start justify-between gap-3">
        <div>
          <h3 className="font-semibold text-slate-800">{title}</h3>
          {subtitle && <p className="text-xs text-slate-500">{subtitle}</p>}
        </div>
        {headerAction}
      </header>
    )}
    {children}
  </section>
);

export const Kpi = ({ label, value, hint, tone = 'slate', icon: Icon }) => {
  const tones = {
    slate: 'bg-slate-50 text-slate-700',
    green: 'bg-green-50 text-green-700',
    red: 'bg-red-50 text-red-700',
    blue: 'bg-sky-50 text-sky-700',
    amber: 'bg-amber-50 text-amber-700',
  };
  return (
    <div className={`rounded-2xl p-4 ${tones[tone]}`}>
      <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wide">
        <span>{label}</span>
        {Icon && <Icon size={16} aria-hidden="true" />}
      </div>
      <p className="mt-2 text-2xl font-bold">{value}</p>
      {hint && <p className="mt-1 text-xs opacity-80">{hint}</p>}
    </div>
  );
};

export const Empty = ({ children }) => (
  <p className="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">{children}</p>
);

export const WeekdayChart = ({ data }) => {
  const [metric, setMetric] = useState('late');
  const isLate = metric === 'late';
  const valueKey = isLate ? 'minutosTarde' : 'minutosSalidaTemprano';
  const metricLabel = isLate ? 'Llegadas tarde' : 'Salidas temprano';
  const worst = data.reduce((max, item) => (item[valueKey] > max[valueKey] ? item : max), data[0]);
  return (
    <Card
      title={`Día de la semana con más ${isLate ? 'llegadas tarde' : 'salidas temprano'}`}
      subtitle={worst?.[valueKey] > 0 ? `El día con más minutos de ${isLate ? 'retraso' : 'salida temprana'} es el ${worst.dia.toLowerCase()} (${formatMinutes(worst[valueKey])}).` : `No hay ${metricLabel.toLowerCase()} en el periodo.`}
      headerAction={(
        <button type="button" onClick={() => setMetric(isLate ? 'early' : 'late')} className="shrink-0 rounded-lg border border-sky-600 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-50">
          Ver {isLate ? 'salidas temprano' : 'llegadas tarde'}
        </button>
      )}
    >
      <div className="h-72">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={data}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="dia" />
            <YAxis allowDecimals={false} />
            <Tooltip formatter={(value) => [`${value} min`, metricLabel]} />
            <Legend />
            <Bar dataKey={valueKey} name={metricLabel} fill={isLate ? COLORS.contra : '#f97316'} radius={[6, 6, 0, 0]} animationDuration={ANIMATION_DURATION} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </Card>
  );
};

export const DailyTrendChart = ({ data }) => (
  <Card title="Minutos a favor y en contra por día" subtitle="Evolución diaria de todo el personal en el periodo.">
    <div className="h-72">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="fecha" tickFormatter={(value) => formatDate(value).slice(0, 5)} />
          <YAxis />
          <Tooltip labelFormatter={formatDate} formatter={(value) => `${value} min`} />
          <Legend />
          <Line type="monotone" dataKey="favor" name="A favor" stroke={COLORS.favor} strokeWidth={2} dot={false} animationDuration={ANIMATION_DURATION} />
          <Line type="monotone" dataKey="contra" name="En contra" stroke={COLORS.contra} strokeWidth={2} dot={false} animationDuration={ANIMATION_DURATION} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  </Card>
);

export const Table = ({ columns, rows, empty = 'Sin datos para mostrar.', fileName = 'tabla_horario' }) => {
  const tableRef = useRef(null);
  if (rows.length === 0) return <Empty>{empty}</Empty>;
  return (
    <div>
      <div className="mb-2 flex justify-end">
        <button type="button" onClick={() => downloadTableAsXlsx(tableRef.current, fileName)} className="inline-flex items-center gap-2 rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
          <Download size={14} aria-hidden="true" /> Exportar Excel
        </button>
      </div>
      <div className="max-h-96 overflow-auto rounded-xl border border-slate-100">
        <table ref={tableRef} className="w-full text-left text-sm">
          <thead className="sticky top-0 bg-slate-50 text-xs uppercase text-slate-500">
            <tr>{columns.map((column) => <th key={column.key} className={`px-3 py-2 ${column.right ? 'text-right' : ''}`}>{column.label}</th>)}</tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((row, index) => (
              <tr key={row.key ?? index} className="hover:bg-slate-50">
                {columns.map((column) => (
                  <td key={column.key} className={`px-3 py-2 ${column.right ? 'text-right tabular-nums' : ''}`}>
                    {column.render ? column.render(row) : row[column.key]}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export const Minutes = ({ value, tone }) => {
  const color = tone === 'favor' ? 'text-green-700' : tone === 'contra' ? 'text-red-700' : 'text-slate-700';
  return <span className={`font-medium ${value ? color : 'text-slate-400'}`}>{value || 0}</span>;
};

export const InconsistencyTable = ({ rows }) => {
  const items = rows.filter((row) => row.tipo === 'inconsistente');
  return (
    <Card
      title="Marcaciones incompletas"
      subtitle="Días en los que falta registrar la entrada o la salida. No suman minutos a favor ni en contra."
    >
      <Table
        fileName="marcaciones_incompletas"
        empty="No hay inconsistencias en el periodo."
        rows={items.map((row) => ({ ...row, key: `${row.pid}-${row.fecha}` }))}
        columns={[
          { key: 'nombre', label: 'Persona' },
          { key: 'area', label: 'Área' },
          { key: 'fecha', label: 'Fecha', render: (row) => formatDate(row.fecha) },
          { key: 'entrada', label: 'Entrada', render: (row) => row.entrada ?? '—' },
          { key: 'salida', label: 'Salida', render: (row) => row.salida ?? '—' },
          {
            key: 'inconsistencia',
            label: 'Falta',
            render: (row) => (
              <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                {row.inconsistencia === 'sin_entrada' ? 'Entrada' : 'Salida'}
              </span>
            ),
          },
        ]}
      />
    </Card>
  );
};
