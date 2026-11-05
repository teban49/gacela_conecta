import React, { useEffect, useState } from 'react';
import { Clock, Upload } from 'lucide-react';
import { useAuth } from '../../../contexts/AuthContext';
import { canViewHorario } from '../../../utils/roleAccess';
import { horarioAPI } from '../services/horarioAPI';
import ImportAttendanceModal from '../components/ImportAttendanceModal';
import GeneralTab from './GeneralTab';
import PersonTab from './PersonTab';
import AbsencesTab from './AbsencesTab';

const tabs = [
  { id: 'general', label: 'Informe general' },
  { id: 'personas', label: 'Por persona / área' },
  { id: 'ausencias', label: 'Ausencias' },
];

const shiftMonths = (date, months) => {
  const d = new Date(`${date}T00:00:00`);
  d.setMonth(d.getMonth() + months, 1);
  return d.toISOString().slice(0, 10);
};

export const HorarioPage = () => {
  const { user, hasRole } = useAuth();
  const isAdmin = hasRole('admin');
  const [showImport, setShowImport] = useState(false);
  const allowed = canViewHorario(user);
  const [tab, setTab] = useState('general');
  const [report, setReport] = useState(null);
  const [range, setRange] = useState({ desde: '', hasta: '' });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = async (desde, hasta) => {
    setLoading(true);
    setError('');
    try {
      const response = await horarioAPI.getReport(desde, hasta);
      const data = response.data?.data;
      setReport(data);
      setRange({ desde: data.desde ?? '', hasta: data.hasta ?? '' });
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'No fue posible cargar el control de horario.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (allowed) load();
  }, [allowed]);

  if (!allowed) {
    return (
      <main className="mx-auto mt-12 max-w-2xl rounded-2xl border border-red-200 bg-white p-8 text-center shadow-sm">
        <h2 className="mb-2 text-2xl font-bold text-slate-800">Acceso denegado</h2>
        <p className="text-gray-600">Este módulo está disponible para administración, gerencia, directivos y jefes.</p>
      </main>
    );
  }

  const presets = report?.maxDate ? [
    { label: 'Último mes con datos', desde: `${report.maxDate.slice(0, 7)}-01`, hasta: report.maxDate },
    { label: 'Últimos 3 meses', desde: shiftMonths(report.maxDate, -2), hasta: report.maxDate },
    { label: 'Todo el historial', desde: report.minDate, hasta: report.maxDate },
  ] : [];

  return (
    <main className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <header className="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div className="flex items-center gap-3">
          <div className="rounded-2xl bg-sky-100 p-3 text-sky-700"><Clock size={24} /></div>
          <div>
            <h1 className="text-2xl font-bold text-slate-900">Control de horario</h1>
            <p className="text-sm text-slate-500">Entradas, salidas y ausencias del personal.</p>
          </div>
        </div>
        <form
          className="flex flex-wrap items-end gap-2"
          onSubmit={(event) => { event.preventDefault(); load(range.desde, range.hasta); }}
        >
          <label className="text-xs font-semibold text-slate-500">Desde
            <input type="date" required value={range.desde} min={report?.minDate} max={report?.maxDate} onChange={(e) => setRange({ ...range, desde: e.target.value })} className="mt-1 block rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </label>
          <label className="text-xs font-semibold text-slate-500">Hasta
            <input type="date" required value={range.hasta} min={report?.minDate} max={report?.maxDate} onChange={(e) => setRange({ ...range, hasta: e.target.value })} className="mt-1 block rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </label>
          <button type="submit" className="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Aplicar</button>
          {isAdmin && (
            <button type="button" onClick={() => setShowImport(true)} className="inline-flex items-center gap-2 rounded-xl border border-sky-600 px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50">
              <Upload size={16} /> Cargar asistencia
            </button>
          )}
        </form>
      </header>

      {presets.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {presets.map((preset) => (
            <button key={preset.label} type="button" onClick={() => load(preset.desde, preset.hasta)} className="rounded-full bg-white px-4 py-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:bg-sky-50">
              {preset.label}
            </button>
          ))}
        </div>
      )}

      <nav className="flex flex-wrap gap-2" aria-label="Secciones de horario">
        {tabs.map((item) => (
          <button
            key={item.id}
            type="button"
            onClick={() => setTab(item.id)}
            className={`rounded-full px-5 py-2.5 text-sm font-bold transition ${tab === item.id ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-sky-50'}`}
          >
            {item.label}
          </button>
        ))}
      </nav>

      {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}
      {loading ? (
        <div className="flex justify-center py-16"><div className="h-10 w-10 animate-spin rounded-full border-b-2 border-sky-600" /></div>
      ) : report && (
        <>
          {tab === 'general' && <GeneralTab rows={report.dias} />}
          {tab === 'personas' && <PersonTab rows={report.dias} areas={report.areas} />}
          {tab === 'ausencias' && <AbsencesTab absences={report.ausencias} areas={report.areas} />}
        </>
      )}
      {showImport && <ImportAttendanceModal onClose={() => setShowImport(false)} onImported={() => load()} />}
    </main>
  );
};

export default HorarioPage;
