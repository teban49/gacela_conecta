import { useCallback, useEffect, useState } from 'react';
import { Search, Shirt } from 'lucide-react';
import { dotacionAPI } from '../services/dotacionAPI';

const formatDate = (value) => (
  value ? new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('es-CO') : 'Sin fecha'
);

const DotacionDeliveries = () => {
  const [query, setQuery] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState({ Entregas: [], Total: 0, PorPagina: 50 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await dotacionAPI.getDeliveries({ q: search, page });
      setData(response.data?.data ?? { Entregas: [], Total: 0, PorPagina: 50 });
    } catch (err) {
      setError(err.response?.data?.message || 'No fue posible cargar las dotaciones entregadas.');
    } finally {
      setLoading(false);
    }
  }, [search, page]);

  useEffect(() => {
    load();
  }, [load]);

  const totalPages = Math.max(1, Math.ceil(data.Total / (data.PorPagina || 50)));

  return (
    <section className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-xl font-bold text-slate-900">Dotaciones entregadas</h2>
          <p className="text-sm text-slate-600">Entregas de las empresas vinculadas a tu usuario, de la más reciente a la más antigua ({data.Total} registros).</p>
        </div>
        <form
          onSubmit={(event) => { event.preventDefault(); setPage(1); setSearch(query.trim()); }}
          className="flex gap-2"
        >
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Buscar por cédula o nombre" className="w-64 rounded-xl border border-slate-200 px-3 py-2.5 text-sm" />
          <button type="submit" className="inline-flex items-center gap-1 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white"><Search size={16} /> Buscar</button>
        </form>
      </div>

      {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</p>}

      {loading ? <div className="py-10 text-center text-slate-500">Cargando...</div> : data.Entregas.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-12 text-center text-slate-500">No hay dotaciones entregadas para mostrar.</div>
      ) : (
        <div className="space-y-3">
          {data.Entregas.map((entrega) => (
            <div key={entrega.Id} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <div className="font-bold text-slate-900">{entrega.Empleado || 'Empleado sin usuario registrado'}</div>
                  <div className="text-sm text-slate-500">C.C. {entrega.Cedula} · {entrega.Empresa}</div>
                </div>
                <div className="text-sm font-semibold text-slate-700">{formatDate(entrega.Fecha)}</div>
              </div>
              <ul className="mt-3 grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                {entrega.Articulos.map((item, index) => (
                  <li key={index} className="flex items-center gap-2"><Shirt size={14} className="text-slate-400" />{item.Cantidad} × {item.Referencia}{item.Talla ? ` (Talla ${item.Talla})` : ''}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      )}

      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-3">
          <button type="button" disabled={page <= 1} onClick={() => setPage((p) => p - 1)} className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm disabled:opacity-40">Anterior</button>
          <span className="text-sm text-slate-600">Página {page} de {totalPages}</span>
          <button type="button" disabled={page >= totalPages} onClick={() => setPage((p) => p + 1)} className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm disabled:opacity-40">Siguiente</button>
        </div>
      )}
    </section>
  );
};

export default DotacionDeliveries;
