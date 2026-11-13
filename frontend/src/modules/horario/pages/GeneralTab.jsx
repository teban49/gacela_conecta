import React, { useMemo } from 'react';
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { ArrowDownCircle, ArrowUpCircle, Clock, LogIn, LogOut, Scale } from 'lucide-react';
import { COLORS, Card, DailyTrendChart, Kpi, Minutes, Table, WeekdayChart } from '../components/HorarioParts';
import { byArea, byDate, byWeekday, formatMinutes, totalsOf } from '../utils/horarioStats';

const GeneralTab = ({ rows }) => {
  const totals = useMemo(() => totalsOf(rows), [rows]);
  const areas = useMemo(() => byArea(rows), [rows]);
  const weekdays = useMemo(() => byWeekday(rows), [rows]);
  const daily = useMemo(() => byDate(rows), [rows]);

  const pie = [
    { name: 'Llegada tarde', value: totals.tarde, color: '#dc2626' },
    { name: 'Salida temprano', value: totals.salidaTemprano, color: '#f97316' },
    { name: 'Llegada temprano', value: totals.temprano, color: '#16a34a' },
    { name: 'Salida tarde', value: totals.salidaTarde + totals.festivo, color: '#0ea5e9' },
  ].filter((item) => item.value > 0);

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <Kpi label="Llegadas tarde" value={formatMinutes(totals.tarde)} hint="Minutos en contra" tone="red" icon={LogIn} />
        <Kpi label="Salidas temprano" value={formatMinutes(totals.salidaTemprano)} hint="Minutos en contra" tone="red" icon={LogOut} />
        <Kpi label="Llegadas temprano" value={formatMinutes(totals.temprano)} hint="Minutos a favor" tone="green" icon={LogIn} />
        <Kpi label="Salidas tarde" value={formatMinutes(totals.salidaTarde)} hint={totals.festivo ? `+ ${formatMinutes(totals.festivo)} en festivos` : 'Minutos a favor'} tone="green" icon={LogOut} />
        <Kpi label="Total a favor" value={formatMinutes(totals.favor)} tone="green" icon={ArrowUpCircle} />
        <Kpi label="Total en contra" value={formatMinutes(totals.contra)} tone="red" icon={ArrowDownCircle} />
      </div>
      <Kpi
        label="Balance de la empresa"
        value={`${totals.balance >= 0 ? '+' : '-'}${formatMinutes(Math.abs(totals.balance))}`}
        hint={totals.balance >= 0 ? 'El personal aporta más tiempo del que debe.' : 'El personal debe más tiempo del que aporta.'}
        tone={totals.balance >= 0 ? 'green' : 'red'}
        icon={Scale}
      />

      <div className="grid gap-6 lg:grid-cols-2">
        <WeekdayChart data={weekdays} />
        <Card title="Distribución de los minutos" subtitle="Peso de cada concepto sobre el total registrado.">
          <div className="h-72">
            {pie.length === 0 ? null : (
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie data={pie} dataKey="value" nameKey="name" innerRadius={55} outerRadius={95} paddingAngle={2} label={({ value }) => `${value}`} animationDuration={1000}>
                    {pie.map((item) => <Cell key={item.name} fill={item.color} />)}
                  </Pie>
                  <Tooltip formatter={(value) => `${value} min`} />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            )}
          </div>
        </Card>
      </div>

      <DailyTrendChart data={daily} />

      <Card title="Minutos por área" subtitle="Comparación de minutos a favor y en contra de cada área.">
        <div className="h-80">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={areas}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="area" interval={0} angle={-25} textAnchor="end" height={70} />
              <YAxis />
              <Tooltip formatter={(value) => `${value} min`} />
              <Legend verticalAlign="top" />
              <Bar dataKey="favor" name="A favor" fill={COLORS.favor} radius={[6, 6, 0, 0]} animationDuration={1000} />
              <Bar dataKey="contra" name="En contra" fill={COLORS.contra} radius={[6, 6, 0, 0]} animationDuration={1000} />
            </BarChart>
          </ResponsiveContainer>
        </div>
        <div className="mt-4">
          <Table
            fileName="minutos_por_area"
            rows={areas.map((area) => ({ ...area, key: area.area }))}
            columns={[
              { key: 'area', label: 'Área' },
              { key: 'personas', label: 'Personas', right: true },
              { key: 'tarde', label: 'Llegada tarde', right: true, render: (row) => <Minutes value={row.tarde} tone="contra" /> },
              { key: 'salidaTemprano', label: 'Salida temprano', right: true, render: (row) => <Minutes value={row.salidaTemprano} tone="contra" /> },
              { key: 'temprano', label: 'Llegada temprano', right: true, render: (row) => <Minutes value={row.temprano} tone="favor" /> },
              { key: 'salidaTarde', label: 'Salida tarde', right: true, render: (row) => <Minutes value={row.salidaTarde + row.festivo} tone="favor" /> },
              { key: 'balance', label: 'Balance', right: true, render: (row) => <span className={`font-semibold ${row.balance >= 0 ? 'text-green-700' : 'text-red-700'}`}>{row.balance > 0 ? '+' : ''}{row.balance}</span> },
            ]}
          />
        </div>
      </Card>
      <p className="flex items-center gap-2 text-xs text-slate-500">
        <Clock size={14} aria-hidden="true" />
        Horario: lunes 8:00–17:00 y martes a viernes 8:00–17:30. Solo se cuentan llegadas tarde después de las 8:15. El trabajo en festivos y fines de semana suma a favor.
      </p>
    </div>
  );
};

export default GeneralTab;
