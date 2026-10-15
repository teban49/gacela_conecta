import { useEffect, useState } from 'react';
import { Check, X } from 'lucide-react';
import { gestionAPI } from '../services/gestionAPI';

const PendingAssetAssignments = () => {
  const [assignments, setAssignments] = useState([]);
  const [error, setError] = useState('');
  const [respondingId, setRespondingId] = useState(null);

  const loadAssignments = async () => {
    try {
      const response = await gestionAPI.getMisAsignacionesPendientes();
      setAssignments(response.data?.data ?? []);
      setError('');
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'No se pudieron cargar tus solicitudes de asignación.');
    }
  };

  useEffect(() => {
    loadAssignments();
  }, []);

  const respond = async (assignmentId, accept) => {
    setRespondingId(assignmentId);
    setError('');
    try {
      if (accept) {
        await gestionAPI.aceptarAsignacion(assignmentId);
      } else {
        await gestionAPI.rechazarAsignacion(assignmentId);
      }
      await loadAssignments();
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'No se pudo responder a la solicitud de asignación.');
    } finally {
      setRespondingId(null);
    }
  };

  if (!error && assignments.length === 0) return null;

  return (
    <section className="mx-auto mb-5 max-w-7xl space-y-3 px-6 pt-4" aria-label="Solicitudes de asignación de activos">
      {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</p>}
      {assignments.length > 0 && (
        <div className="space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-5">
          <h2 className="font-semibold text-amber-900">Activos pendientes de tu aceptación</h2>
          {assignments.map((assignment) => (
            <div key={assignment.AsignacionId} className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-4">
              <div>
                <p className="font-semibold text-slate-800">
                  {assignment.TipoActivo} · {assignment.CodigoInventario}{assignment.Placa ? ` · ${assignment.Placa}` : ''}
                </p>
                <p className="text-sm text-slate-600">
                  Solicitado por {assignment.SolicitadoPorNombre} {assignment.SolicitadoPorApellidos}
                </p>
              </div>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={respondingId === assignment.AsignacionId}
                  onClick={() => respond(assignment.AsignacionId, true)}
                  className="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                >
                  <Check size={15} /> Aceptar
                </button>
                <button
                  type="button"
                  disabled={respondingId === assignment.AsignacionId}
                  onClick={() => respond(assignment.AsignacionId, false)}
                  className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                >
                  <X size={15} /> Rechazar
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </section>
  );
};

export default PendingAssetAssignments;
