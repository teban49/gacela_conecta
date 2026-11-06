import React, { useEffect, useMemo, useState } from 'react';
import { ArrowLeft, Search, Users } from 'lucide-react';
import { Link } from 'react-router-dom';
import { intranetAPI } from '../services/intranetAPI';

const columns = [
  ['first_name', 'Nombres'],
  ['last_name', 'Apellidos'],
  ['address', 'Dirección'],
  ['phone', 'Teléfono'],
  ['email', 'Correo'],
  ['extension', 'Extensión'],
  ['area_agencia', 'Área / Agencia'],
  ['cargo', 'Cargo'],
  ['empresa', 'Empresa'],
];

const DirectoryPage = ({ embedded = false }) => {
  const [entries, setEntries] = useState([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const loadDirectory = async () => {
    setLoading(true);
    try {
      const response = await intranetAPI.getDirectorio();
      setEntries(response.data?.data ?? []);
      setError('');
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'No fue posible cargar el directorio.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDirectory();
  }, []);

  const filteredEntries = useMemo(() => {
    const normalizedSearch = search.trim().toLocaleLowerCase();
    if (!normalizedSearch) return entries;

    return entries.filter((entry) =>
      columns.some(([key]) => String(entry[key] ?? '').toLocaleLowerCase().includes(normalizedSearch))
    );
  }, [entries, search]);

  return (
    <main className={`w-full max-w-none space-y-6 ${embedded ? '' : 'p-6'}`}>
      <header className="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
          {!embedded && (
            <Link to="/intranet" className="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-orange-700 hover:text-orange-800">
              <ArrowLeft size={16} /> Volver a Intranet
            </Link>
          )}
          <div className="flex items-center gap-3">
            <div className="rounded-2xl bg-orange-100 p-3 text-orange-700"><Users size={24} /></div>
            <div>
              <h1 className="text-2xl font-bold text-slate-900">Directorio</h1>
              <p className="text-sm text-slate-500">Todos los contactos y campos registrados en el directorio.</p>
            </div>
          </div>
        </div>
        <div className="flex w-full flex-col gap-3 sm:max-w-md sm:flex-row sm:items-center">
        <div className="relative w-full">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Buscar en todos los campos..."
            aria-label="Buscar en el directorio"
            className="w-full rounded-xl border border-slate-200 py-3 pl-10 pr-4 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
          />
        </div>
        </div>
      </header>

      <section className="w-full rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h2 className="font-semibold text-slate-800">Contactos</h2>
          <span className="text-sm text-slate-500">
            {loading ? 'Cargando...' : `${filteredEntries.length} de ${entries.length} registros`}
          </span>
        </div>
        {error ? (
          <div role="alert" className="p-8 text-center text-sm font-medium text-red-700">{error}</div>
        ) : loading ? (
          <div className="p-8 text-center text-sm text-slate-500">Cargando directorio...</div>
        ) : filteredEntries.length === 0 ? (
          <div className="p-8 text-center text-sm text-slate-500">
            {entries.length ? 'No hay resultados para la búsqueda.' : 'No hay contactos registrados.'}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full table-fixed divide-y divide-slate-200 text-left text-sm">
              <colgroup>
                <col style={{ width: '9%' }} />
                <col style={{ width: '9%' }} />
                <col style={{ width: '12%' }} />
                <col style={{ width: '8%' }} />
                <col style={{ width: '15%' }} />
                <col style={{ width: '7%' }} />
                <col style={{ width: '21%' }} />
                <col style={{ width: '9%' }} />
                <col style={{ width: '10%' }} />
              </colgroup>
              <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                  {columns.map(([key, label]) => <th key={key} scope="col" className="break-words px-2 py-3 font-semibold">{label}</th>)}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredEntries.map((entry) => (
                  <tr key={entry.id} className="hover:bg-orange-50/40">
                    {columns.map(([key]) => (
                      <td key={key} className={`break-words px-2 py-3 text-slate-700 ${key === 'email' ? '[overflow-wrap:anywhere]' : ''}`}>
                        {key === 'email' && entry[key] ? (
                          <a href={`mailto:${entry[key]}`} className="text-blue-700 hover:underline">{entry[key]}</a>
                        ) : (
                          entry[key] ?? '—'
                        )}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </main>
  );
};

export default DirectoryPage;
