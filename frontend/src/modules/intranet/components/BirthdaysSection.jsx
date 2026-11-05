import React, { useEffect, useMemo, useState } from 'react';
import { Cake, PartyPopper } from 'lucide-react';
import { intranetAPI } from '../services/intranetAPI';

const MONTH_NAMES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DAY_MS = 86400000;

const nextOccurrence = (mes, dia, today) => {
  for (let year = today.getFullYear(); year <= today.getFullYear() + 1; year += 1) {
    // El 29 de febrero se celebra el 28 en años no bisiestos
    const leap = new Date(year, 1, 29).getMonth() === 1;
    const date = new Date(year, mes - 1, mes === 2 && dia === 29 && !leap ? 28 : dia);
    if (date >= today) return date;
  }
  return null;
};

const daysLabel = (days) => {
  if (days === 0) return 'Hoy';
  if (days === 1) return 'Mañana';
  return `En ${days} días`;
};

const BirthdaysSection = () => {
  const [people, setPeople] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [month, setMonth] = useState('');

  useEffect(() => {
    intranetAPI.getCumpleanos()
      .then((response) => setPeople(response.data?.data ?? []))
      .catch((requestError) => setError(requestError.response?.data?.message || 'No fue posible cargar los cumpleaños.'))
      .finally(() => setLoading(false));
  }, []);

  const upcoming = useMemo(() => {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    return people
      .map((person) => {
        const date = nextOccurrence(person.mes, person.dia, today);
        return { ...person, date, days: Math.round((date - today) / DAY_MS) };
      })
      .sort((a, b) => a.days - b.days || a.nombre.localeCompare(b.nombre));
  }, [people]);

  const todays = upcoming.filter((person) => person.days === 0);
  const next = upcoming.find((person) => person.days > 0);
  const nextGroup = next ? upcoming.filter((person) => person.days === next.days) : [];
  const list = upcoming.filter((person) => !month || person.mes === Number(month));

  if (loading) return <div className="flex justify-center py-12"><div className="h-8 w-8 animate-spin rounded-full border-b-2 border-orange-600" /></div>;
  if (error) return <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>;
  if (!people.length) {
    return <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Aún no hay fechas de nacimiento registradas. Un administrador puede agregarlas desde el Directorio.</p>;
  }

  return (
    <section className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2">
        <div className="rounded-3xl bg-gradient-to-br from-orange-500 to-pink-500 p-6 text-white shadow-sm">
          <div className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide"><PartyPopper size={18} aria-hidden="true" /> Cumpleaños de hoy</div>
          {todays.length ? (
            <ul className="mt-3 space-y-1 text-lg font-bold">
              {todays.map((person) => <li key={person.id}>🎉 {person.nombre}</li>)}
            </ul>
          ) : <p className="mt-3 text-lg font-semibold opacity-90">Hoy no hay cumpleaños.</p>}
        </div>
        <div className="rounded-3xl border border-orange-200 bg-orange-50 p-6 shadow-sm">
          <div className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-orange-700"><Cake size={18} aria-hidden="true" /> Próximo cumpleaños</div>
          {next ? (
            <>
              <p className="mt-3 text-2xl font-bold text-slate-900">{daysLabel(next.days)} · {next.dia} de {MONTH_NAMES[next.mes - 1]}</p>
              <ul className="mt-2 space-y-0.5 text-slate-700">
                {nextGroup.map((person) => <li key={person.id} className="font-medium">{person.nombre} <span className="text-sm text-slate-500">· {person.area || person.empresa}</span></li>)}
              </ul>
            </>
          ) : <p className="mt-3 text-slate-600">No hay más cumpleaños próximos.</p>}
        </div>
      </div>

      <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h2 className="text-lg font-bold text-slate-900">Próximos cumpleaños</h2>
          <select value={month} onChange={(event) => setMonth(event.target.value)} aria-label="Filtrar por mes" className="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">Todos los meses</option>
            {MONTH_NAMES.map((name, index) => <option key={name} value={index + 1}>{name[0].toUpperCase() + name.slice(1)}</option>)}
          </select>
        </div>
        <ul className="divide-y divide-slate-100">
          {list.map((person) => (
            <li key={person.id} className="flex items-center gap-4 py-3">
              <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-orange-100 text-orange-700">
                <span className="text-base font-bold leading-none">{person.dia}</span>
                <span className="text-[10px] font-semibold uppercase">{MONTH_NAMES[person.mes - 1].slice(0, 3)}</span>
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate font-semibold text-slate-800">{person.nombre}</p>
                <p className="truncate text-xs text-slate-500">{[person.area, person.empresa].filter(Boolean).join(' · ')}</p>
              </div>
              <span className={`rounded-full px-3 py-1 text-xs font-semibold ${person.days === 0 ? 'bg-pink-100 text-pink-700' : person.days <= 7 ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600'}`}>
                {daysLabel(person.days)}
              </span>
            </li>
          ))}
          {list.length === 0 && <li className="py-6 text-center text-sm text-slate-500">No hay cumpleaños en este mes.</li>}
        </ul>
      </div>
    </section>
  );
};

export default BirthdaysSection;
