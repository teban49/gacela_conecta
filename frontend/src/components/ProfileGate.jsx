import { useState } from 'react';
import { Loader2, UserCheck } from 'lucide-react';
import { authAPI } from '../services/api';
import { useAuth } from '../contexts/AuthContext';

const fields = [
  ['nombres', 'Nombres', 'text', true],
  ['apellidos', 'Apellidos', 'text', true],
  ['cedula', 'Cédula de ciudadanía', 'text', true],
  ['telefono', 'Teléfono', 'text', true],
  ['fechaNacimiento', 'Fecha de nacimiento', 'date', true],
  ['direccion', 'Dirección (opcional)', 'text', false],
];

/** Pide los datos faltantes del usuario o, si está todo completo, le pide confirmarlos en su primer ingreso. */
const ProfileGate = () => {
  const { user, updateUser } = useAuth();
  const [form, setForm] = useState(() => Object.fromEntries(fields.map(([key]) => [key, user?.[key] ?? ''])));
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  if (!user || user.perfilConfirmado) return null;

  const missing = user.perfilFaltantes ?? [];
  const isComplete = missing.length === 0;

  const submit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const response = await authAPI.updateProfile(form);
      if (response.data?.data) updateUser(response.data.data);
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'No fue posible guardar tus datos.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4">
      <form onSubmit={submit} role="dialog" aria-modal="true" aria-labelledby="profile-gate-title" className="my-auto w-full max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-xl">
        <div className="flex items-start gap-3">
          <div className="rounded-xl bg-orange-100 p-3 text-orange-700"><UserCheck size={22} /></div>
          <div>
            <h2 id="profile-gate-title" className="text-xl font-bold text-slate-900">
              {isComplete ? 'Confirma tus datos' : 'Completa tus datos'}
            </h2>
            <p className="mt-1 text-sm text-slate-500">
              {isComplete
                ? 'Revisa que tu información registrada sea correcta. Puedes corregirla antes de confirmar.'
                : 'Faltan algunos datos de tu registro. Se usarán automáticamente en tus solicitudes y documentos.'}
            </p>
          </div>
        </div>

        {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

        <div className="grid gap-4 sm:grid-cols-2">
          {fields.map(([key, label, type, required]) => (
            <label key={key} className={`text-sm font-semibold text-slate-700 ${key === 'direccion' ? 'sm:col-span-2' : ''}`}>
              {label}{required ? ' *' : ''}
              <input
                type={type}
                required={required}
                max={type === 'date' ? new Date().toISOString().slice(0, 10) : undefined}
                value={form[key] ?? ''}
                onChange={(event) => setForm({ ...form, [key]: event.target.value })}
                className={`mt-2 w-full rounded-xl border px-4 py-3 font-normal ${missing.includes(key) ? 'border-orange-400 bg-orange-50' : 'border-slate-200'}`}
              />
            </label>
          ))}
        </div>

        <div className="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
          {user.email ? <>Correo corporativo: <strong>{user.email}</strong>. </> : user.usuario ? <>Usuario de acceso: <strong>{user.usuario}</strong>. </> : null}
          Estos datos no se pueden cambiar desde aquí; si hay un error, avisa al administrador.
        </div>

        <div className="flex justify-end">
          <button type="submit" disabled={saving} className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 font-semibold text-white hover:bg-orange-700 disabled:opacity-50">
            {saving && <Loader2 size={16} className="animate-spin" />}
            {isComplete ? 'Confirmar mis datos' : 'Guardar y continuar'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ProfileGate;
