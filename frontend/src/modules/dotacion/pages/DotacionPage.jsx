import { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  CheckCircle2, Clock3, FileText, Plus, Send, Shirt, X, XCircle,
} from 'lucide-react';
import { dotacionAPI } from '../services/dotacionAPI';
import DotacionDeliveries from '../components/DotacionDeliveries';
import { printDotacionAuthorization, printDotacionReceipt } from '../utils/printDotacionDocument';

const STATE_LABELS = {
  Pendiente_Jefe: 'Pendiente de Jefe Gestión Humana',
  Pendiente_Coordinador: 'Pendiente de Coordinador de Dotación',
  Aprobada: 'Aceptada',
  Rechazada: 'Denegada',
  Recibida: 'Recibida',
};

const STATE_STYLES = {
  Pendiente_Jefe: 'border-amber-200 bg-amber-50 text-amber-800',
  Pendiente_Coordinador: 'border-blue-200 bg-blue-50 text-blue-800',
  Aprobada: 'border-emerald-200 bg-emerald-50 text-emerald-800',
  Rechazada: 'border-red-200 bg-red-50 text-red-800',
  Recibida: 'border-slate-200 bg-slate-100 text-slate-700',
};

const getErrorMessage = (error, fallback) => Object.values(error.response?.data?.errors ?? {}).flat().join(' ')
  || error.response?.data?.message
  || fallback;

const formatDate = (value) => {
  if (!value) return '—';
  return new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('es-CO');
};

const DotacionPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [pageError, setPageError] = useState('');
  const [formError, setFormError] = useState('');
  const [detailError, setDetailError] = useState('');
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [comment, setComment] = useState('');
  const [companyId, setCompanyId] = useState('');
  const [beneficiaryId, setBeneficiaryId] = useState('');
  const [cedulaPlace, setCedulaPlace] = useState('');
  const [view, setView] = useState('solicitudes');
  const [articles, setArticles] = useState([{ Cantidad: '1', Referencia: '', Talla: '' }]);

  const loadData = useCallback(async () => {
    setLoading(true);
    setPageError('');
    try {
      const response = await dotacionAPI.getDashboard();
      const result = response.data?.data ?? {};
      setDashboard(result);
      setCompanyId((current) => current || String(result.Opciones?.Empresas?.[0]?.Id ?? ''));
    } catch (error) {
      setPageError(error.response?.data?.message || 'No fue posible cargar Dotación.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const openDetails = useCallback(async (id) => {
    setDetailError('');
    setComment('');
    try {
      const response = await dotacionAPI.getRequest(id);
      setSelectedRequest(response.data?.data ?? null);
    } catch (error) {
      setPageError(error.response?.data?.message || 'No fue posible abrir la solicitud.');
    }
  }, []);

  useEffect(() => {
    const requestId = searchParams.get('id');
    if (!requestId) return;
    openDetails(requestId);
    setSearchParams({}, { replace: true });
  }, [openDetails, searchParams, setSearchParams]);

  const requester = dashboard?.Opciones ?? {};
  const myRequests = dashboard?.MisSolicitudes ?? [];
  const pendingRequests = dashboard?.Pendientes ?? [];
  const beneficiaries = dashboard?.Beneficiarios ?? [];
  const beneficiary = beneficiaries.find((item) => String(item.Id) === String(beneficiaryId));
  const companyOptions = useMemo(() => {
    const all = requester.Empresas ?? [];
    if (!beneficiary) return all;
    const ids = String(beneficiary.EmpresasIds ?? '').split(',');
    return all.filter((company) => ids.includes(String(company.Id)));
  }, [requester.Empresas, beneficiary]);
  const canCreate = companyOptions.length > 0 || (beneficiary && (requester.Empresas ?? []).length > 0);

  const changeBeneficiary = (value) => {
    setBeneficiaryId(value);
    const target = beneficiaries.find((item) => String(item.Id) === String(value));
    const all = requester.Empresas ?? [];
    const ids = target ? String(target.EmpresasIds ?? '').split(',') : null;
    const options = ids ? all.filter((company) => ids.includes(String(company.Id))) : all;
    setCompanyId(String(options[0]?.Id ?? ''));
  };

  const changeArticle = (index, key, value) => {
    setArticles((current) => current.map((item, itemIndex) => (
      itemIndex === index ? { ...item, [key]: value } : item
    )));
  };

  const handleCreate = async (event) => {
    event.preventDefault();
    setSaving(true);
    setFormError('');
    try {
      await dotacionAPI.createRequest({
        EmpresaId: companyId,
        ...(beneficiaryId ? { BeneficiarioId: Number(beneficiaryId) } : {}),
        CedulaExpedidaEn: cedulaPlace.trim(),
        Articulos: articles.map((item) => ({
          Cantidad: Number(item.Cantidad),
          Referencia: item.Referencia.trim(),
          Talla: item.Talla.trim(),
        })),
      });
      setCedulaPlace('');
      setArticles([{ Cantidad: '1', Referencia: '', Talla: '' }]);
      await loadData();
    } catch (error) {
      setFormError(getErrorMessage(error, 'No se pudo enviar la solicitud.'));
    } finally {
      setSaving(false);
    }
  };

  const handleDecision = async (decision) => {
    if (!selectedRequest) return;
    setSaving(true);
    setDetailError('');
    try {
      await dotacionAPI.decideRequest(selectedRequest.Id, decision, comment.trim());
      await loadData();
      await openDetails(selectedRequest.Id);
    } catch (error) {
      setDetailError(getErrorMessage(error, 'No se pudo registrar la decisión.'));
    } finally {
      setSaving(false);
    }
  };

  const handleReceived = async () => {
    if (!selectedRequest) return;
    setSaving(true);
    setDetailError('');
    try {
      await dotacionAPI.markReceived(selectedRequest.Id);
      await loadData();
      await openDetails(selectedRequest.Id);
    } catch (error) {
      setDetailError(getErrorMessage(error, 'No se pudo confirmar la recepción.'));
    } finally {
      setSaving(false);
    }
  };

  const renderCard = (request, pending = false) => (
    <button
      key={request.Id}
      type="button"
      onClick={() => openDetails(request.Id)}
      className="w-full rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-orange-300 hover:shadow-md"
    >
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div className="text-xs font-semibold uppercase text-slate-500">{request.EmpresaNombre}</div>
          <h3 className="mt-1 text-lg font-bold text-slate-900">
            {pending ? `${request.SolicitanteNombres} ${request.SolicitanteApellidos}` : 'Solicitud de dotación'}
          </h3>
          <div className="mt-2 text-sm text-slate-600">
            {pending && `${request.SolicitanteCargo ?? ''} · `}
            {request.CantidadArticulos != null ? `${request.CantidadArticulos} artículos · ` : ''}
            Creada {formatDate(request.FechaCreacion)}
          </div>
        </div>
        <span className={`inline-flex shrink-0 items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-semibold ${STATE_STYLES[request.Estado] ?? ''}`}>
          <Clock3 size={14} /> {STATE_LABELS[request.Estado] ?? request.Estado}
        </span>
      </div>
    </button>
  );

  const pendingForCurrentUser = selectedRequest
    && pendingRequests.some((request) => Number(request.Id) === Number(selectedRequest.Id));

  if (loading && !dashboard) {
    return <div className="p-8 text-center text-slate-500">Cargando Dotación...</div>;
  }

  return (
    <div className="mx-auto max-w-7xl space-y-7 p-6 md:p-8">
      <header>
        <h1 className="text-3xl font-bold text-slate-900">Dotación</h1>
        <p className="mt-1 text-slate-600">Solicita y consulta la aprobación y entrega de dotación.</p>
      </header>

      {pageError && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{pageError}</p>}

      {dashboard?.PuedeVerEntregas && (
        <div className="flex gap-2 border-b border-slate-200">
          {[['solicitudes', 'Solicitudes'], ['entregas', 'Dotaciones entregadas']].map(([key, label]) => (
            <button key={key} type="button" onClick={() => setView(key)} className={`-mb-px border-b-2 px-4 py-2.5 text-sm font-semibold ${view === key ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800'}`}>{label}</button>
          ))}
        </div>
      )}

      {view === 'entregas' && dashboard?.PuedeVerEntregas ? <DotacionDeliveries /> : (
      <>      <form onSubmit={handleCreate} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
          <h2 className="text-xl font-bold text-slate-900">Nueva solicitud</h2>
          <p className="mt-1 text-sm text-slate-600">Flota Magdalena requiere aprobación de Jefe Gestión Humana y Coordinador de Dotación. Rápido Duitama y Expreso Paz de Río pasan directamente al coordinador.</p>
        </div>
        {!canCreate && <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Tu usuario no tiene una empresa asignada. Contacta al administrador.</p>}
        {formError && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{formError}</p>}

        <div className="grid gap-4 sm:grid-cols-2">
          {beneficiaries.length > 0 && (
            <label className="text-sm font-semibold text-slate-700 sm:col-span-2">Solicitar para
              <select value={beneficiaryId} onChange={(event) => changeBeneficiary(event.target.value)} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                <option value="">Para mí</option>
                {beneficiaries.map((item) => <option key={item.Id} value={item.Id}>{item.Nombres} {item.Apellidos}{item.Cedula ? ` - ${item.Cedula}` : ''}</option>)}
              </select>
            </label>
          )}
          <label className="text-sm font-semibold text-slate-700">Solicitante
            <input readOnly value={beneficiary ? `${beneficiary.Nombres} ${beneficiary.Apellidos}` : `${requester.Nombres ?? ''} ${requester.Apellidos ?? ''}`.trim()} className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal" />
          </label>
          <label className="text-sm font-semibold text-slate-700">Cédula
            <input readOnly value={beneficiary ? (beneficiary.Cedula ?? '') : (requester.Cedula ?? '')} className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal" />
          </label>
          <label className="text-sm font-semibold text-slate-700">Cargo
            <input readOnly value={beneficiary ? 'Taquillero/a' : (requester.Cargo ?? '')} className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal" />
          </label>
          <label className="text-sm font-semibold text-slate-700">Empresa <span className="text-red-600">*</span>
            <select required value={companyId} onChange={(event) => setCompanyId(event.target.value)} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
              {companyOptions.map((company) => <option key={company.Id} value={company.Id}>{company.Nombre}</option>)}
            </select>
          </label>
          <label className="text-sm font-semibold text-slate-700 sm:col-span-2">Lugar de expedición de la cédula <span className="text-red-600">*</span>
            <input required maxLength={120} value={cedulaPlace} onChange={(event) => setCedulaPlace(event.target.value)} placeholder="Ciudad o municipio" className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
          </label>
        </div>

        <div>
          <div className="mb-3 flex items-center justify-between">
            <h3 className="font-semibold text-slate-800">Artículos solicitados</h3>
            <button type="button" disabled={articles.length >= 20} onClick={() => setArticles((current) => [...current, { Cantidad: '1', Referencia: '', Talla: '' }])} className="inline-flex items-center gap-1 rounded-lg bg-orange-50 px-3 py-2 text-sm font-semibold text-orange-700 disabled:opacity-50">
              <Plus size={15} /> Agregar artículo
            </button>
          </div>
          <div className="space-y-3">
            {articles.map((item, index) => (
              <div key={index} className="grid gap-3 sm:grid-cols-[120px_1fr_140px_auto]">
                <label className="text-xs font-semibold text-slate-500">Cantidad
                  <input required type="number" min="1" max="100" step="1" value={item.Cantidad} onChange={(event) => changeArticle(index, 'Cantidad', event.target.value)} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" />
                </label>
                <label className="text-xs font-semibold text-slate-500">Referencia
                  <input required maxLength={150} value={item.Referencia} onChange={(event) => changeArticle(index, 'Referencia', event.target.value)} placeholder="Ej. pantalones, chaleco" className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" />
                </label>
                <label className="text-xs font-semibold text-slate-500">Talla
                  <input required maxLength={20} value={item.Talla} onChange={(event) => changeArticle(index, 'Talla', event.target.value)} placeholder="Ej. M, 32" className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" />
                </label>
                {articles.length > 1 && <button type="button" onClick={() => setArticles((current) => current.filter((_, itemIndex) => itemIndex !== index))} className="self-end rounded-lg p-2 text-slate-500 hover:bg-red-50 hover:text-red-600" aria-label="Quitar artículo"><X size={18} /></button>}
              </div>
            ))}
          </div>
        </div>

        <div className="flex justify-end">
          <button type="submit" disabled={saving || !canCreate} className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700 disabled:opacity-50">
            <Send size={16} /> {saving ? 'Enviando...' : 'Enviar solicitud'}
          </button>
        </div>
      </form>

      {pendingRequests.length > 0 && (
        <section className="space-y-3">
          <h2 className="text-xl font-bold text-slate-900">Pendientes de mi aprobación</h2>
          {pendingRequests.map((request) => renderCard(request, true))}
        </section>
      )}

      <section className="space-y-3">
        <h2 className="text-xl font-bold text-slate-900">Mis solicitudes</h2>
        {myRequests.length ? myRequests.map((request) => renderCard(request)) : (
          <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-12 text-center text-slate-500">Aún no has realizado solicitudes de dotación.</div>
        )}
      </section>
      </>
      )}

      {selectedRequest && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
          <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div className="mb-5 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
              <div>
                <h2 className="text-xl font-bold text-slate-900">Solicitud de dotación #{selectedRequest.Id}</h2>
                <p className="mt-1 text-sm text-slate-500">{selectedRequest.EmpresaNombre}</p>
              </div>
              <button type="button" onClick={() => setSelectedRequest(null)} className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200" aria-label="Cerrar"><X size={18} /></button>
            </div>
            <div className="space-y-5">
              {detailError && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{detailError}</p>}
              <div className="flex flex-wrap items-center gap-3">
                <span className={`rounded-full border px-3 py-1.5 text-xs font-semibold ${STATE_STYLES[selectedRequest.Estado] ?? ''}`}>{STATE_LABELS[selectedRequest.Estado] ?? selectedRequest.Estado}</span>
                <span className="text-sm text-slate-500">Creada {formatDate(selectedRequest.FechaCreacion)}</span>
              </div>
              <div className="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <div><strong>Solicitante:</strong> {selectedRequest.SolicitanteNombres} {selectedRequest.SolicitanteApellidos}</div>
                <div><strong>Cédula:</strong> {selectedRequest.SolicitanteCedula || '—'}</div>
                <div><strong>Cargo:</strong> {selectedRequest.SolicitanteCargo}</div>
                <div><strong>Expedida en:</strong> {selectedRequest.CedulaExpedidaEn}</div>
              </div>
              <div>
                <h3 className="mb-2 font-semibold text-slate-800">Artículos</h3>
                <div className="divide-y rounded-xl border border-slate-200">
                  {(selectedRequest.Articulos ?? []).map((item) => <div key={item.Id} className="flex items-center gap-3 p-3 text-sm"><Shirt size={16} className="text-slate-400" /><span className="font-semibold">{item.Cantidad} ×</span><span>{item.Referencia}</span>{item.Talla && <span className="text-slate-500">· Talla {item.Talla}</span>}</div>)}
                </div>
              </div>
              {(selectedRequest.Estado === 'Aprobada' || selectedRequest.Estado === 'Recibida') && (
                <div className="flex flex-wrap gap-3">
                  {selectedRequest.EsSolicitante && selectedRequest.Estado === 'Aprobada' && (
                    <>
                      <button type="button" onClick={() => !printDotacionAuthorization(selectedRequest) && setDetailError('El navegador bloqueó la ventana de impresión.')} className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"><FileText size={16} /> Generar autorización</button>
                      <button type="button" disabled={saving} onClick={handleReceived} className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"><CheckCircle2 size={16} /> Recibido</button>
                    </>
                  )}
                  {selectedRequest.EsCoordinador && selectedRequest.Estado === 'Recibida' && (
                    <button type="button" onClick={() => !printDotacionReceipt(selectedRequest) && setDetailError('El navegador bloqueó la ventana de impresión.')} className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"><FileText size={16} /> Ver formato de entrega</button>
                  )}
                </div>
              )}
              {!!selectedRequest.Historial?.length && (
                <div>
                  <h3 className="mb-2 font-semibold text-slate-800">Historial</h3>
                  <ol className="space-y-3 border-l-2 border-slate-200 pl-4">
                    {selectedRequest.Historial.map((item) => <li key={item.Id} className="relative"><span className="absolute -left-[22px] top-1 h-2.5 w-2.5 rounded-full bg-orange-500" /><div className="text-sm font-semibold">{item.Accion} · {item.Nombres} {item.Apellidos}</div><div className="text-xs text-slate-500">Etapa {item.Nivel} · {new Date(item.Fecha).toLocaleString('es-CO')}</div>{item.Comentario && <p className="mt-1 text-sm text-slate-600">{item.Comentario}</p>}</li>)}
                  </ol>
                </div>
              )}
              {pendingForCurrentUser && (
                <div className="border-t border-slate-100 pt-4">
                  <label className="block text-sm font-semibold text-slate-700">Comentario <span className="font-normal text-slate-500">(obligatorio para denegar)</span>
                    <textarea rows={3} value={comment} onChange={(event) => setComment(event.target.value)} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" placeholder="Observación para el solicitante" />
                  </label>
                  <div className="mt-4 flex flex-wrap justify-end gap-3">
                    <button type="button" disabled={saving || !comment.trim()} onClick={() => handleDecision('Rechazada')} className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"><XCircle size={16} /> Denegar</button>
                    <button type="button" disabled={saving} onClick={() => handleDecision('Aprobada')} className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"><CheckCircle2 size={16} /> Aceptar</button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DotacionPage;
