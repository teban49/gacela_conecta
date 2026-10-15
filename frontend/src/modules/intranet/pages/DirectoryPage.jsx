import React, { useEffect, useMemo, useState } from 'react';
import { ArrowLeft, Edit, Search, Users, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import { intranetAPI } from '../services/intranetAPI';
import { useAuth } from '../../../contexts/AuthContext';

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
  const [editingEntry, setEditingEntry] = useState(null);
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState('');
  const [saveMessage, setSaveMessage] = useState('');
  const { hasPermission } = useAuth();
  const canEditDirectory = hasPermission('intranet.administrar_directorio');

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

  const saveEntry = async (event) => {
    event.preventDefault();
    if (!editingEntry) return;
    setSaving(true);
    setSaveError('');
    setSaveMessage('');
    try {
      await intranetAPI.updateDirectorio(editingEntry.id, Object.fromEntries(
        columns.map(([key]) => [key, editingEntry[key] ?? ''])
      ));
      setEditingEntry(null);
      setSaveMessage('Contacto actualizado correctamente.');
      await loadDirectory();
    } catch (requestError) {
      setSaveError(requestError.response?.data?.message || 'No fue posible guardar los cambios.');
    } finally {
      setSaving(false);
    }
  };

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
        <div className="relative w-full sm:max-w-sm">
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
      </header>

      {saveMessage && <div role="status" className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{saveMessage}</div>}
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
                <col style={{ width: canEditDirectory ? '10%' : '21%' }} />
                <col style={{ width: '9%' }} />
                <col style={{ width: '10%' }} />
                {canEditDirectory && <col style={{ width: '11%' }} />}
              </colgroup>
              <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                  {columns.map(([key, label]) => <th key={key} scope="col" className="break-words px-2 py-3 font-semibold">{label}</th>)}
                  {canEditDirectory && <th scope="col" className="px-4 py-3 font-semibold">Acciones</th>}
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
                    {canEditDirectory && (
                      <td className="px-2 py-3">
                        <button
                          type="button"
                          onClick={() => { setSaveError(''); setEditingEntry({ ...entry }); }}
                          aria-label={`Editar ${entry.first_name} ${entry.last_name}`}
                          className="inline-flex items-center gap-1 rounded-lg bg-orange-50 px-2 py-2 text-xs font-semibold text-orange-700 hover:bg-orange-100"
                        >
                          <Edit size={14} /> Editar
                        </button>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {editingEntry && (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
          <form onSubmit={saveEntry} role="dialog" aria-modal="true" aria-labelledby="edit-directory-title" className="my-auto w-full max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="edit-directory-title" className="text-xl font-bold text-slate-900">Editar contacto</h2>
                <p className="mt-1 text-sm text-slate-500">Actualiza la información del directorio.</p>
              </div>
              <button type="button" onClick={() => setEditingEntry(null)} aria-label="Cerrar" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>
            {saveError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{saveError}</div>}
            <div className="grid gap-4 sm:grid-cols-2">
              {columns.map(([key, label]) => (
                <label key={key} className="text-sm font-semibold text-slate-700">
                  {label}{(key === 'first_name' || key === 'last_name') ? ' *' : ''}
                  <input
                    required={key === 'first_name' || key === 'last_name'}
                    type={key === 'email' ? 'email' : 'text'}
                    maxLength={{ first_name: 100, last_name: 120, address: 255, phone: 30, email: 150, extension: 30, area_agencia: 120, cargo: 120, empresa: 120 }[key]}
                    value={editingEntry[key] ?? ''}
                    onChange={(event) => setEditingEntry({ ...editingEntry, [key]: event.target.value })}
                    className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal"
                  />
                </label>
              ))}
            </div>
            <div className="flex justify-end gap-3">
              <button type="button" disabled={saving} onClick={() => setEditingEntry(null)} className="rounded-xl border border-slate-200 px-4 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
              <button type="submit" disabled={saving} className="rounded-xl bg-orange-600 px-4 py-2.5 font-semibold text-white hover:bg-orange-700 disabled:opacity-50">{saving ? 'Guardando...' : 'Guardar cambios'}</button>
            </div>
          </form>
        </div>
      )}
    </main>
  );
};

export default DirectoryPage;
