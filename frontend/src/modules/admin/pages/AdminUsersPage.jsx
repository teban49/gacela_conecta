import React, { useEffect, useMemo, useState } from 'react';
import { Edit, Plus, Search, Trash2, UsersRound, X } from 'lucide-react';
import { useAuth } from '../../../contexts/AuthContext';
import { securityAPI } from '../../security/services/securityAPI';

const emptyForm = {
  Nombres: '', Apellidos: '', Cedula: '', EmailCorporativo: '', Telefono: '',
  FechaNacimiento: '', Extension: '', Direccion: '', Area: '', Password: '',
  EmpresaIds: [], AgenciaId: '', CargoId: '', CargoJefeDirectoId: '', RolId: '', Estado: 1,
};

const inputClass = 'w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none';

const Field = ({ label, children }) => (
  <label className="block text-sm font-medium text-slate-700">
    <span className="mb-1 block">{label}</span>
    {children}
  </label>
);

export const AdminUsersPage = () => {
  const { hasRole } = useAuth();
  const isAdmin = hasRole('admin');
  const [users, setUsers] = useState([]);
  const [catalogs, setCatalogs] = useState({ empresas: [], agencias: [], cargos: [], roles: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [search, setSearch] = useState('');
  const [companyFilter, setCompanyFilter] = useState('');
  const [form, setForm] = useState(null);
  const [editingId, setEditingId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [visible, setVisible] = useState(100);

  const load = async () => {
    setLoading(true);
    try {
      const [usersRes, catalogsRes] = await Promise.all([securityAPI.getUsers(), securityAPI.getUserCatalogs()]);
      setUsers(usersRes.data?.data ?? []);
      setCatalogs(catalogsRes.data?.data ?? { empresas: [], agencias: [], cargos: [], roles: [] });
    } catch (e) {
      setError(e.response?.data?.message || 'No fue posible cargar los usuarios.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { if (isAdmin) load(); }, [isAdmin]);

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return users.filter((u) => {
      if (companyFilter && !String(u.EmpresasIds ?? '').split(',').includes(companyFilter)) return false;
      if (!term) return true;
      return `${u.Nombres} ${u.Apellidos} ${u.Cedula ?? ''} ${u.EmailCorporativo ?? ''} ${u.Usuario ?? ''}`.toLowerCase().includes(term);
    });
  }, [users, search, companyFilter]);

  const lookup = (list, id) => list.find((item) => String(item.Id) === String(id))?.Nombre ?? '—';

  const openCreate = () => {
    setEditingId(null);
    setForm({ ...emptyForm, RolId: String(catalogs.roles.find((r) => r.Codigo === 'usuario')?.Id ?? '') });
  };

  const openEdit = (u) => {
    setEditingId(u.Id);
    setForm({
      Nombres: u.Nombres ?? '', Apellidos: u.Apellidos ?? '', Cedula: u.Cedula ?? '',
      EmailCorporativo: u.EmailCorporativo ?? '', Telefono: u.Telefono ?? '',
      FechaNacimiento: u.FechaNacimiento ? String(u.FechaNacimiento).slice(0, 10) : '',
      Extension: u.Extension ?? '', Direccion: u.Direccion ?? '', Area: u.Area ?? '', Password: '',
      EmpresaIds: String(u.EmpresasIds ?? '').split(',').filter(Boolean),
      AgenciaId: String(u.AgenciaId ?? ''), CargoId: String(u.CargoId ?? ''),
      CargoJefeDirectoId: u.CargoJefeDirectoId ? String(u.CargoJefeDirectoId) : '',
      RolId: String(u.RolId ?? ''), Estado: Number(u.Estado) ? 1 : 0,
    });
  };

  const set = (key, value) => setForm((prev) => ({ ...prev, [key]: value }));
  const toggleCompany = (id) => set('EmpresaIds', form.EmpresaIds.includes(id) ? form.EmpresaIds.filter((c) => c !== id) : [...form.EmpresaIds, id]);

  const save = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    setNotice('');
    const payload = {
      ...form,
      EmpresaIds: form.EmpresaIds.map(Number),
      AgenciaId: Number(form.AgenciaId),
      CargoId: Number(form.CargoId),
      CargoJefeDirectoId: form.CargoJefeDirectoId ? Number(form.CargoJefeDirectoId) : null,
    };
    try {
      let userId = editingId;
      if (editingId) {
        await securityAPI.updateUser(editingId, payload);
      } else {
        const res = await securityAPI.createUser(payload);
        userId = res.data?.data?.Id;
      }
      if (userId && form.RolId) await securityAPI.setUserRole(userId, Number(form.RolId));
      setNotice(editingId ? 'Usuario actualizado.' : 'Usuario creado.');
      setForm(null);
      await load();
    } catch (e) {
      const errors = e.response?.data?.errors;
      setError(errors ? Object.values(errors).flat().join(' ') : (e.response?.data?.message || 'No fue posible guardar el usuario.'));
    } finally {
      setSaving(false);
    }
  };

  const remove = async (u) => {
    if (!window.confirm(`¿Eliminar a ${u.Nombres} ${u.Apellidos}? Esta acción no se puede deshacer.`)) return;
    setError('');
    setNotice('');
    try {
      await securityAPI.deleteUser(u.Id);
      setNotice('Usuario eliminado.');
      await load();
    } catch (e) {
      setError(e.response?.data?.message || 'No fue posible eliminar el usuario.');
    }
  };

  if (!isAdmin) {
    return (
      <main className="mx-auto mt-12 max-w-2xl rounded-2xl border border-red-200 bg-white p-8 text-center shadow-sm">
        <h2 className="mb-2 text-2xl font-bold text-slate-800">Acceso denegado</h2>
        <p className="text-gray-600">Este módulo está disponible solo para administradores del sistema.</p>
      </main>
    );
  }

  return (
    <main className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <header className="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div className="flex items-center gap-3">
          <div className="rounded-2xl bg-rose-100 p-3 text-rose-700"><UsersRound size={24} /></div>
          <div>
            <h1 className="text-2xl font-bold text-slate-900">Administración de usuarios</h1>
            <p className="text-sm text-slate-500">{users.length} usuarios registrados.</p>
          </div>
        </div>
        <button type="button" onClick={openCreate} className="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
          <Plus size={16} /> Nuevo usuario
        </button>
      </header>

      {error && <div className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</div>}
      {notice && <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{notice}</div>}

      <section className="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="mb-4 flex flex-wrap gap-3">
          <div className="relative min-w-64 flex-1">
            <Search size={16} className="absolute left-3 top-2.5 text-slate-400" />
            <input className={`${inputClass} pl-9`} placeholder="Buscar por nombre, cédula o correo" value={search} onChange={(e) => setSearch(e.target.value)} />
          </div>
          <select className={`${inputClass} max-w-xs`} value={companyFilter} onChange={(e) => setCompanyFilter(e.target.value)}>
            <option value="">Todas las empresas</option>
            {catalogs.empresas.map((c) => <option key={c.Id} value={String(c.Id)}>{c.Nombre}</option>)}
          </select>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-200 text-xs uppercase text-slate-500">
              <tr>
                <th className="p-2">Nombre</th><th className="p-2">Cédula</th><th className="p-2">Correo</th>
                <th className="p-2">Cargo</th><th className="p-2">Agencia</th><th className="p-2">Rol</th>
                <th className="p-2">Estado</th><th className="p-2 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              {loading && <tr><td colSpan={8} className="p-6 text-center text-slate-500">Cargando...</td></tr>}
              {!loading && filtered.length === 0 && <tr><td colSpan={8} className="p-6 text-center text-slate-500">Sin resultados.</td></tr>}
              {filtered.slice(0, visible).map((u) => (
                <tr key={u.Id} className="border-b border-slate-100 hover:bg-slate-50">
                  <td className="p-2 font-medium text-slate-800">{u.Nombres} {u.Apellidos}</td>
                  <td className="p-2">{u.Cedula || '—'}</td>
                  <td className="p-2">{u.EmailCorporativo || (u.Usuario ? `Usuario: ${u.Usuario}` : '—')}</td>
                  <td className="p-2">{lookup(catalogs.cargos, u.CargoId)}</td>
                  <td className="p-2">{lookup(catalogs.agencias, u.AgenciaId)}</td>
                  <td className="p-2">{u.RolNombre}</td>
                  <td className="p-2">
                    <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${Number(u.Estado) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'}`}>
                      {Number(u.Estado) ? 'Activo' : 'Inactivo'}
                    </span>
                  </td>
                  <td className="p-2">
                    <div className="flex justify-end gap-1">
                      <button type="button" title="Editar" onClick={() => openEdit(u)} className="rounded-lg p-2 text-sky-700 hover:bg-sky-50"><Edit size={16} /></button>
                      <button type="button" title="Eliminar" onClick={() => remove(u)} className="rounded-lg p-2 text-red-600 hover:bg-red-50"><Trash2 size={16} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {filtered.length > visible && (
          <div className="mt-4 text-center">
            <button type="button" onClick={() => setVisible((v) => v + 100)} className="rounded-xl border border-slate-300 px-4 py-2 text-sm">
              Mostrar más ({filtered.length - visible} restantes)
            </button>
          </div>
        )}
      </section>

      {form && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
          <form onSubmit={save} className="max-h-[92vh] w-full max-w-3xl space-y-4 overflow-y-auto rounded-3xl bg-white p-6 shadow-xl">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-bold text-slate-900">{editingId ? 'Editar usuario' : 'Nuevo usuario'}</h2>
              <button type="button" onClick={() => setForm(null)} className="rounded-lg p-1 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>
            <div className="grid gap-3 md:grid-cols-2">
              <Field label="Nombres"><input required className={inputClass} value={form.Nombres} onChange={(e) => set('Nombres', e.target.value)} /></Field>
              <Field label="Apellidos"><input required className={inputClass} value={form.Apellidos} onChange={(e) => set('Apellidos', e.target.value)} /></Field>
              <Field label="Cédula de ciudadanía"><input className={inputClass} value={form.Cedula} onChange={(e) => set('Cedula', e.target.value)} /></Field>
              <Field label="Correo corporativo (opcional para conductores y taquilleros)"><input type="email" className={inputClass} value={form.EmailCorporativo} onChange={(e) => set('EmailCorporativo', e.target.value)} /></Field>
              <Field label="Teléfono"><input className={inputClass} value={form.Telefono} onChange={(e) => set('Telefono', e.target.value)} /></Field>
              <Field label="Fecha de nacimiento"><input type="date" className={inputClass} value={form.FechaNacimiento} onChange={(e) => set('FechaNacimiento', e.target.value)} /></Field>
              <Field label="Extensión"><input className={inputClass} value={form.Extension} onChange={(e) => set('Extension', e.target.value)} /></Field>
              <Field label="Dirección"><input className={inputClass} value={form.Direccion} onChange={(e) => set('Direccion', e.target.value)} /></Field>
              <Field label="Área"><input className={inputClass} value={form.Area} onChange={(e) => set('Area', e.target.value)} /></Field>
              <Field label={editingId ? 'Nueva contraseña (opcional)' : 'Contraseña (opcional)'}><input type="password" minLength={8} autoComplete="new-password" className={inputClass} value={form.Password} onChange={(e) => set('Password', e.target.value)} /></Field>
              <Field label="Agencia">
                <select required className={inputClass} value={form.AgenciaId} onChange={(e) => set('AgenciaId', e.target.value)}>
                  <option value="">Selecciona...</option>
                  {catalogs.agencias.map((a) => <option key={a.Id} value={String(a.Id)}>{a.Nombre}</option>)}
                </select>
              </Field>
              <Field label="Cargo">
                <select required className={inputClass} value={form.CargoId} onChange={(e) => set('CargoId', e.target.value)}>
                  <option value="">Selecciona...</option>
                  {catalogs.cargos.map((c) => <option key={c.Id} value={String(c.Id)}>{c.Nombre}</option>)}
                </select>
              </Field>
              <Field label="Cargo del jefe directo">
                <select className={inputClass} value={form.CargoJefeDirectoId} onChange={(e) => set('CargoJefeDirectoId', e.target.value)}>
                  <option value="">Sin asignar</option>
                  {catalogs.cargos.map((c) => <option key={c.Id} value={String(c.Id)}>{c.Nombre}</option>)}
                </select>
              </Field>
              <Field label="Rol">
                <select required className={inputClass} value={form.RolId} onChange={(e) => set('RolId', e.target.value)}>
                  <option value="">Selecciona...</option>
                  {catalogs.roles.map((r) => <option key={r.Id} value={String(r.Id)}>{r.Nombre}</option>)}
                </select>
              </Field>
              <Field label="Estado">
                <select className={inputClass} value={form.Estado} onChange={(e) => set('Estado', Number(e.target.value))}>
                  <option value={1}>Activo</option>
                  <option value={0}>Inactivo</option>
                </select>
              </Field>
            </div>
            <fieldset>
              <legend className="mb-1 text-sm font-medium text-slate-700">Empresas</legend>
              <div className="flex flex-wrap gap-3">
                {catalogs.empresas.map((c) => (
                  <label key={c.Id} className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.EmpresaIds.includes(String(c.Id))} onChange={() => toggleCompany(String(c.Id))} />
                    {c.Nombre}
                  </label>
                ))}
              </div>
            </fieldset>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setForm(null)} className="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
              <button type="submit" disabled={saving} className="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-50">
                {saving ? 'Guardando...' : 'Guardar'}
              </button>
            </div>
          </form>
        </div>
      )}
    </main>
  );
};

export default AdminUsersPage;
