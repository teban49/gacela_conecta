import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Banknote, CalendarDays, CheckCircle2, Clock3, Download, FileText, Paperclip,
  Plus, Search, Send, Upload, X, XCircle
} from 'lucide-react';
import { solicitudesAPI } from '../services/solicitudesAPI';
import { useAuth } from '../../../contexts/AuthContext';
import { printSolicitudDocument } from '../utils/printSolicitudDocument';

const STATUS_LABELS = {
  Pendiente_Jefe: 'Pendiente de jefe directo',
  Pendiente_RRHH: 'Pendiente de Recursos Humanos',
  Pendiente_Gerencia: 'Pendiente de Gerencia',
  Aprobada: 'Aprobada',
  Rechazada: 'Rechazada',
};

const STATUS_STYLES = {
  Pendiente_Jefe: 'bg-amber-50 text-amber-800 border-amber-200',
  Pendiente_RRHH: 'bg-blue-50 text-blue-800 border-blue-200',
  Pendiente_Gerencia: 'bg-violet-50 text-violet-800 border-violet-200',
  Aprobada: 'bg-emerald-50 text-emerald-800 border-emerald-200',
  Rechazada: 'bg-red-50 text-red-800 border-red-200',
};

const PAYROLL_LABELS = {
  Pendiente_Liquidacion: 'En liquidación de nómina',
  Pendiente_Revision_Nomina: 'En liquidación de nómina',
  Con_Errores: 'En liquidación de nómina',
  Pendiente_Pago: 'Pendiente de pago',
  Pagada: 'Pagada',
};

const PAYROLL_STAFF_LABELS = {
  Pendiente_Liquidacion: 'Pendiente de liquidación',
  Pendiente_Revision_Nomina: 'Pendiente de revisión',
  Con_Errores: 'Liquidación con errores',
  Pendiente_Pago: 'Pendiente de pago',
  Pagada: 'Pagada',
};

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(`${date.slice(0, 10)}T12:00:00`).toLocaleDateString();
};

const getErrorMessage = (error, fallback) => {
  const fieldErrors = error.response?.data?.errors;
  return Object.values(fieldErrors ?? {}).flat().join(' ')
    || error.response?.data?.message
    || fallback;
};

const SolicitudesPage = () => {
  const { user, hasPermission, hasRole } = useAuth();
  const canCreate = hasPermission('solicitudes.crear');
  const [activeTab, setActiveTab] = useState('mine');
  const [types, setTypes] = useState([]);
  const [myRequests, setMyRequests] = useState([]);
  const [pendingRequests, setPendingRequests] = useState([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [pageError, setPageError] = useState('');
  const [formError, setFormError] = useState('');
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [decisionComment, setDecisionComment] = useState('');
  const [decisionError, setDecisionError] = useState('');
  const [detailError, setDetailError] = useState('');
  const [searchParams, setSearchParams] = useSearchParams();
  const [payrollRole, setPayrollRole] = useState(null);
  const [payrollRequests, setPayrollRequests] = useState([]);
  const [companies, setCompanies] = useState([]);
  const [beneficiaries, setBeneficiaries] = useState([]);
  const [payrollFile, setPayrollFile] = useState(null);
  const [payrollComment, setPayrollComment] = useState('');
  const emptyForm = {
    TipoId: '',
    Asunto: '',
    Descripcion: '',
    FechaInicio: '',
    FechaFin: '',
    FechaReintegro: '',
    EmpresaId: '',
    BeneficiarioId: '',
    DiasDinero: '0',
    DiasDisfrute: '0',
    PeriodoPendiente: '',
    Adjunto: null,
  };
  const [formData, setFormData] = useState(emptyForm);

  const canReviewByRole = hasRole('rrhh') || hasRole('gerencia');
  const canReview = canReviewByRole || pendingRequests.length > 0;
  const activeType = types.find((type) => String(type.Id) === String(formData.TipoId));

  const loadData = async () => {
    setLoading(true);
    setPageError('');
    try {
      const [typesResponse, mineResponse, pendingResponse, companiesResponse] = await Promise.all([
        solicitudesAPI.getLaboralTypes(),
        solicitudesAPI.getMyLaboralRequests(),
        solicitudesAPI.getPendingLaboralRequests(),
        solicitudesAPI.getLaboralCompanies(),
      ]);
      const loadedTypes = typesResponse.data?.data ?? [];
      const loadedCompanies = companiesResponse.data?.data ?? [];
      setTypes(loadedTypes);
      setCompanies(loadedCompanies);
      try {
        const beneficiariesResponse = await solicitudesAPI.getLaboralBeneficiaries();
        setBeneficiaries(beneficiariesResponse.data?.data ?? []);
      } catch {
        setBeneficiaries([]);
      }
      setMyRequests(mineResponse.data?.data ?? []);
      setPendingRequests(pendingResponse.data?.data ?? []);
      try {
        const payrollResponse = await solicitudesAPI.getPayrollRequests();
        setPayrollRole(payrollResponse.data?.data?.Rol ?? null);
        setPayrollRequests(payrollResponse.data?.data?.Solicitudes ?? []);
      } catch {
        setPayrollRole(null);
        setPayrollRequests([]);
      }
      setFormData((current) => ({
        ...current,
        TipoId: current.TipoId || (loadedTypes[0] ? String(loadedTypes[0].Id) : ''),
        EmpresaId: current.EmpresaId || (loadedCompanies[0] ? String(loadedCompanies[0].Id) : ''),
      }));
    } catch (error) {
      console.error('Error cargando solicitudes laborales:', error);
      setPageError(error.response?.data?.message || 'No fue posible cargar las solicitudes.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  // Deep links desde el centro de notificaciones: ?tab=...&id=...
  useEffect(() => {
    const tab = searchParams.get('tab');
    const id = searchParams.get('id');
    if (tab === 'pending' || tab === 'payroll') setActiveTab(tab);
    if (id) {
      openDetails({ Id: id });
    }
    if (tab || id) setSearchParams({}, { replace: true });
  }, [searchParams]);

  const visibleRequests = useMemo(() => {
    const requests = activeTab === 'pending'
      ? pendingRequests
      : activeTab === 'payroll' ? payrollRequests : myRequests;
    const query = search.trim().toLocaleLowerCase();
    if (!query) return requests;
    return requests.filter((request) =>
      [request.Asunto, request.TipoNombre, request.SolicitanteNombres, request.SolicitanteApellidos]
        .filter(Boolean)
        .some((value) => value.toLocaleLowerCase().includes(query))
    );
  }, [activeTab, myRequests, pendingRequests, payrollRequests, search]);

  const openDetails = async (request) => {
    setDetailLoading(true);
    setDecisionError('');
    setDetailError('');
    setDecisionComment('');
    setPayrollFile(null);
    setPayrollComment('');
    try {
      const response = await solicitudesAPI.getLaboralRequest(request.Id);
      setSelectedRequest(response.data?.data ?? null);
    } catch (error) {
      setPageError(error.response?.data?.message || 'No fue posible abrir el detalle.');
    } finally {
      setDetailLoading(false);
    }
  };

  const handleCreate = async (event) => {
    event.preventDefault();
    setSaving(true);
    setFormError('');

    const body = new FormData();
    if (formData.BeneficiarioId) body.append('BeneficiarioId', formData.BeneficiarioId);
    body.append('TipoId', formData.TipoId);
    body.append('Asunto', formData.Asunto.trim());
    body.append('Descripcion', formData.Descripcion.trim());
    body.append('FechaInicio', formData.FechaInicio);
    body.append('FechaFin', formData.FechaFin);
    if (formData.Adjunto) body.append('Adjunto', formData.Adjunto);
    if (activeType?.Codigo === 'vacaciones') {
      body.append('EmpresaId', formData.EmpresaId);
      body.append('FechaReintegro', formData.FechaReintegro);
      body.append('DiasDinero', formData.DiasDinero || '0');
      body.append('DiasDisfrute', formData.DiasDisfrute || '0');
      body.append('PeriodoPendiente', formData.PeriodoPendiente.trim());
    }

    try {
      await solicitudesAPI.createLaboralRequest(body);
      setFormData((current) => ({
        ...emptyForm,
        TipoId: current.TipoId,
      }));
      await loadData();
      setActiveTab('mine');
    } catch (error) {
      setFormError(getErrorMessage(error, 'No se pudo enviar la solicitud.'));
    } finally {
      setSaving(false);
    }
  };

  const handleDecision = async (decision) => {
    if (!selectedRequest) return;
    setSaving(true);
    setDecisionError('');
    try {
      await solicitudesAPI.decideLaboralRequest(
        selectedRequest.Id,
        decision,
        decisionComment.trim()
      );
      setSelectedRequest(null);
      await loadData();
    } catch (error) {
      setDecisionError(getErrorMessage(error, 'No fue posible registrar la decisión.'));
    } finally {
      setSaving(false);
    }
  };

  const runPayrollAction = async (action, fallback) => {
    setSaving(true);
    setDetailError('');
    try {
      await action();
      setSelectedRequest(null);
      await loadData();
    } catch (error) {
      setDetailError(getErrorMessage(error, fallback));
    } finally {
      setSaving(false);
    }
  };

  const downloadPayrollDoc = async (doc) => {
    try {
      const response = await solicitudesAPI.downloadPayrollDocument(selectedRequest.Id, doc.Id);
      const url = URL.createObjectURL(response.data);
      const link = document.createElement('a');
      link.href = url;
      link.download = doc.NombreArchivo;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (error) {
      setDetailError(error.response?.data?.message || 'No fue posible descargar el archivo.');
    }
  };

  const downloadAttachment = async (attachment) => {
    try {
      const response = await solicitudesAPI.downloadLaboralAttachment(selectedRequest.Id, attachment.Id);
      const url = URL.createObjectURL(response.data);
      const link = document.createElement('a');
      link.href = url;
      link.download = attachment.NombreArchivo;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (error) {
      setDetailError(error.response?.data?.message || 'No fue posible descargar el archivo.');
    }
  };

  const renderRequestCard = (request) => (
    <button
      key={request.Id}
      type="button"
      onClick={() => openDetails(request)}
      className="w-full rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-orange-300 hover:shadow-md"
    >
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0">
          <div className="mb-2 flex flex-wrap items-center gap-2">
            <span className="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">
              {request.TipoNombre}
            </span>
            {activeTab === 'pending' && (
              <span className="text-xs text-slate-500">
                {request.SolicitanteNombres} {request.SolicitanteApellidos}
              </span>
            )}
          </div>
          <h3 className="truncate text-lg font-bold text-slate-900">{request.Asunto}</h3>
          <p className="mt-1 line-clamp-2 text-sm text-slate-600">{request.Descripcion}</p>
          <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
            <span className="inline-flex items-center gap-1"><CalendarDays size={14} />{formatDate(request.FechaInicio)} – {formatDate(request.FechaFin)}</span>
            <span>Creada: {formatDate(request.FechaCreacion)}</span>
          </div>
        </div>
        <span className={`inline-flex shrink-0 items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-semibold ${STATUS_STYLES[request.EstadoSolicitud] || 'border-slate-200 bg-slate-50 text-slate-700'}`}>
          <Clock3 size={14} />
          {request.EstadoSolicitud === 'Aprobada' && request.EstadoNomina
            ? (activeTab === 'payroll' ? PAYROLL_STAFF_LABELS : PAYROLL_LABELS)[request.EstadoNomina]
            : STATUS_LABELS[request.EstadoSolicitud] || request.EstadoSolicitud}
        </span>
      </div>
    </button>
  );

  const renderCreateForm = () => (
    <form onSubmit={handleCreate} className="mx-auto max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div>
        <h2 className="text-xl font-bold text-slate-900">Crear solicitud</h2>
        <p className="mt-1 text-sm text-slate-600">
          La solicitud seguirá este flujo: jefe directo → Recursos Humanos → Gerencia.
        </p>
      </div>
      {formError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{formError}</div>}
      <div className="grid gap-5 sm:grid-cols-2">
        {beneficiaries.length > 0 && (
          <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
            Solicitar para
            <select
              value={formData.BeneficiarioId}
              onChange={async (event) => {
                const value = event.target.value;
                setFormData((current) => ({ ...current, BeneficiarioId: value, EmpresaId: '' }));
                try {
                  const response = await solicitudesAPI.getLaboralCompanies(value);
                  const list = response.data?.data ?? [];
                  setCompanies(list);
                  setFormData((current) => ({ ...current, EmpresaId: list[0] ? String(list[0].Id) : '' }));
                } catch {
                  setCompanies([]);
                }
              }}
              className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
            >
              <option value="">Para mí</option>
              {beneficiaries.map((b) => (
                <option key={b.Id} value={b.Id}>{b.Nombres} {b.Apellidos}{b.Cedula ? ` - ${b.Cedula}` : ''}</option>
              ))}
            </select>
          </label>
        )}
        <label className="text-sm font-semibold text-slate-700">
          Solicitante
          <input
            readOnly
            value={(formData.BeneficiarioId ? (() => { const b = beneficiaries.find((x) => String(x.Id) === String(formData.BeneficiarioId)); return b ? `${b.Nombres} ${b.Apellidos}` : ''; })() : '') || [user?.Nombres ?? user?.nombres, user?.Apellidos ?? user?.apellidos].filter(Boolean).join(' ') || user?.email || ''}
            className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal"
          />
        </label>
        <label className="text-sm font-semibold text-slate-700">
          Tipo de solicitud <span className="text-red-600">*</span>
          <select
            required
            value={formData.TipoId}
            onChange={(event) => setFormData({ ...formData, TipoId: event.target.value, Adjunto: null })}
            className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
          >
            {types.map((type) => <option key={type.Id} value={type.Id}>{type.Nombre}</option>)}
          </select>
        </label>
        <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
          Asunto <span className="text-red-600">*</span>
          <input
            required
            maxLength={200}
            value={formData.Asunto}
            onChange={(event) => setFormData({ ...formData, Asunto: event.target.value })}
            placeholder="Resume el motivo de tu solicitud"
            className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
          />
        </label>
        <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
          Descripción <span className="text-red-600">*</span>
          <textarea
            required
            rows={4}
            value={formData.Descripcion}
            onChange={(event) => setFormData({ ...formData, Descripcion: event.target.value })}
            placeholder="Incluye la información necesaria para revisar la solicitud"
            className="mt-2 w-full resize-y rounded-xl border border-slate-200 px-4 py-3"
          />
        </label>
        {['vacaciones', 'licencia', 'permiso'].includes(activeType?.Codigo) && (
          <>
            <label className="text-sm font-semibold text-slate-700">
              Fecha de inicio <span className="text-red-600">*</span>
              <input
                type="date"
                required
                value={formData.FechaInicio}
                onChange={(event) => setFormData({ ...formData, FechaInicio: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
            <label className="text-sm font-semibold text-slate-700">
              Fecha de fin <span className="text-red-600">*</span>
              <input
                type="date"
                required
                min={formData.FechaInicio || undefined}
                value={formData.FechaFin}
                onChange={(event) => setFormData({ ...formData, FechaFin: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
          </>
        )}
        {activeType?.Codigo === 'vacaciones' && (
          <>
            <label className="text-sm font-semibold text-slate-700">
              Empresa <span className="text-red-600">*</span>
              {companies.length > 1 ? (
                <select
                  required
                  value={formData.EmpresaId}
                  onChange={(event) => setFormData({ ...formData, EmpresaId: event.target.value })}
                  className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
                >
                  {companies.map((company) => <option key={company.Id} value={company.Id}>{company.Nombre}</option>)}
                </select>
              ) : (
                <p className={`mt-2 rounded-xl border px-4 py-3 font-normal ${companies.length === 1 ? 'border-slate-200 bg-slate-50' : 'border-amber-200 bg-amber-50 text-amber-800'}`}>
                  {companies[0]?.Nombre ?? 'No tienes una empresa asignada para esta solicitud.'}
                </p>
              )}
            </label>
            <label className="text-sm font-semibold text-slate-700">
              Cédula de ciudadanía
              <input
                readOnly
                value={(formData.BeneficiarioId ? beneficiaries.find((b) => String(b.Id) === String(formData.BeneficiarioId))?.Cedula : user?.cedula) ?? ''}
                placeholder="Sin cédula registrada"
                className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal"
              />
              {!user?.cedula && <span className="mt-1 block text-xs font-normal text-amber-700">Pide a Gestión Humana que registre tu cédula.</span>}
            </label>
            <label className="text-sm font-semibold text-slate-700">
              Fecha de reintegro al trabajo <span className="text-red-600">*</span>
              <input
                type="date"
                required
                min={formData.FechaFin || undefined}
                value={formData.FechaReintegro}
                onChange={(event) => setFormData({ ...formData, FechaReintegro: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
            <label className="text-sm font-semibold text-slate-700">
              Días en dinero
              <input
                type="number"
                min="0"
                step="1"
                value={formData.DiasDinero}
                onChange={(event) => setFormData({ ...formData, DiasDinero: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
            <label className="text-sm font-semibold text-slate-700">
              Días en disfrute
              <input
                type="number"
                min="0"
                step="1"
                value={formData.DiasDisfrute}
                onChange={(event) => setFormData({ ...formData, DiasDisfrute: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
            <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
              ¿Tienes algún período pendiente?
              <input
                maxLength={255}
                value={formData.PeriodoPendiente}
                onChange={(event) => setFormData({ ...formData, PeriodoPendiente: event.target.value })}
                placeholder="Ej: 2023-2024 (déjalo vacío si no tienes)"
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal"
              />
            </label>
          </>
        )}
        {Number(activeType?.RequiereAdjunto) === 1 && (
          <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
            Archivo adjunto <span className="text-red-600">*</span>
            <span className="mt-2 flex items-center gap-2 rounded-xl border border-dashed border-slate-300 px-4 py-3 font-normal">
              <Paperclip size={16} className="text-slate-500" />
              <input
                type="file"
                required
                accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"
                onChange={(event) => setFormData({ ...formData, Adjunto: event.target.files?.[0] ?? null })}
                className="min-w-0 flex-1 text-sm"
              />
            </span>
            <span className="mt-1 block text-xs font-normal text-slate-500">PDF, JPG o PNG, máximo 10 MB.</span>
          </label>
        )}
      </div>
      <div className="flex justify-end">
        <button
          type="submit"
          disabled={saving || types.length === 0 || (activeType?.Codigo === 'vacaciones' && companies.length === 0)}
          className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700 disabled:opacity-50"
        >
          <Send size={16} />
          {saving ? 'Enviando...' : 'Enviar solicitud'}
        </button>
      </div>
    </form>
  );

  const isPendingForCurrentUser = selectedRequest && pendingRequests.some(
    (request) => Number(request.Id) === Number(selectedRequest.Id)
  );

  return (
    <div className="p-6 md:p-8">
      <div className="mb-7">
        <h1 className="text-3xl font-bold text-slate-900">Solicitudes</h1>
        <p className="mt-1 text-slate-600">Vacaciones, licencias, permisos y certificaciones con aprobación multinivel.</p>
      </div>

      <div className="mb-6 flex flex-wrap gap-2">
        <button onClick={() => setActiveTab('mine')} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${activeTab === 'mine' ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
          <FileText size={16} /> Mis solicitudes
        </button>
        {canReview && (
          <button onClick={() => setActiveTab('pending')} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${activeTab === 'pending' ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
            <Clock3 size={16} /> Pendientes de aprobación
            {pendingRequests.length > 0 && <span className="rounded-full bg-white/20 px-2 py-0.5 text-xs">{pendingRequests.length}</span>}
          </button>
        )}
        {payrollRole && (
          <button onClick={() => setActiveTab('payroll')} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${activeTab === 'payroll' ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
            <Banknote size={16} /> Nómina
            {payrollRequests.length > 0 && <span className="rounded-full bg-white/20 px-2 py-0.5 text-xs">{payrollRequests.length}</span>}
          </button>
        )}
        {canCreate && (
          <button onClick={() => setActiveTab('create')} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${activeTab === 'create' ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}>
            <Plus size={16} /> Crear
          </button>
        )}
      </div>

      {pageError && <div role="alert" className="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{pageError}</div>}

      {activeTab === 'create' ? renderCreateForm() : (
        <>
          <div className="relative mb-5 max-w-xl">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Buscar por asunto o tipo..."
              className="w-full rounded-xl border border-slate-200 py-3 pl-10 pr-4"
            />
          </div>
          {loading ? (
            <div className="py-16 text-center text-slate-500">Cargando solicitudes...</div>
          ) : visibleRequests.length ? (
            <div className="space-y-3">{visibleRequests.map(renderRequestCard)}</div>
          ) : (
            <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-500">
              {activeTab === 'pending' ? 'No tienes solicitudes pendientes de aprobación.'
                : activeTab === 'payroll' ? 'No hay solicitudes en proceso de nómina.'
                : 'Aún no has creado solicitudes.'}
            </div>
          )}
        </>
      )}

      {(selectedRequest || detailLoading) && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
          <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div className="mb-5 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
              <div>
                <h2 className="text-xl font-bold text-slate-900">{selectedRequest?.Asunto || 'Detalle de solicitud'}</h2>
                {selectedRequest && <p className="mt-1 text-sm text-slate-500">{selectedRequest.TipoNombre}</p>}
              </div>
              <button onClick={() => setSelectedRequest(null)} className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200" aria-label="Cerrar detalle"><X size={18} /></button>
            </div>
            {detailLoading ? <p className="py-8 text-center text-slate-500">Cargando detalle...</p> : selectedRequest && (
              <div className="space-y-5">
                {detailError && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{detailError}</p>}
                <div className="flex flex-wrap items-center gap-3">
                  <span className={`rounded-full border px-3 py-1.5 text-xs font-semibold ${STATUS_STYLES[selectedRequest.EstadoSolicitud] || ''}`}>
                    {STATUS_LABELS[selectedRequest.EstadoSolicitud] || selectedRequest.EstadoSolicitud}
                  </span>
                  <span className="text-sm text-slate-500">Creada {formatDate(selectedRequest.FechaCreacion)}</span>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="rounded-xl bg-slate-50 p-4"><div className="text-xs font-semibold uppercase text-slate-500">Solicitante</div><div className="mt-1 font-medium">{selectedRequest.SolicitanteNombres} {selectedRequest.SolicitanteApellidos}</div></div>
                  <div className="rounded-xl bg-slate-50 p-4"><div className="text-xs font-semibold uppercase text-slate-500">Fechas solicitadas</div><div className="mt-1 font-medium">{formatDate(selectedRequest.FechaInicio)} – {formatDate(selectedRequest.FechaFin)}</div></div>
                </div>
                <div><h3 className="mb-1 text-sm font-semibold text-slate-700">Descripción</h3><p className="whitespace-pre-wrap text-sm text-slate-600">{selectedRequest.Descripcion}</p></div>
                {selectedRequest.TipoCodigo === 'vacaciones' && (
                  <div className="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                    <div><span className="font-semibold">Cédula:</span> {selectedRequest.SolicitanteCedula || '—'}</div>
                    <div><span className="font-semibold">Reintegro:</span> {formatDate(selectedRequest.FechaReintegro)}</div>
                    <div><span className="font-semibold">Días en dinero:</span> {selectedRequest.DiasDinero ?? 0}</div>
                    <div><span className="font-semibold">Días en disfrute:</span> {selectedRequest.DiasDisfrute ?? 0}</div>
                    <div className="sm:col-span-2"><span className="font-semibold">Período pendiente:</span> {selectedRequest.PeriodoPendiente || 'Ninguno'}</div>
                  </div>
                )}
                {selectedRequest.EstadoSolicitud === 'Aprobada' && selectedRequest.EstadoNomina && (
                  <div className="space-y-3 rounded-xl border border-slate-200 p-4">
                    <div className="text-sm font-semibold text-slate-800">
                      Nómina: {(selectedRequest.RolNomina ? PAYROLL_STAFF_LABELS : PAYROLL_LABELS)[selectedRequest.EstadoNomina]}
                    </div>
                    {(selectedRequest.NominaDocumentos ?? []).map((doc) => (
                      <button key={doc.Id} onClick={() => downloadPayrollDoc(doc)} className="mr-2 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-blue-700 hover:bg-blue-50">
                        <Download size={15} /> {doc.NombreArchivo}
                      </button>
                    ))}
                    {selectedRequest.RolNomina === 'aux' && ['Pendiente_Liquidacion', 'Con_Errores'].includes(selectedRequest.EstadoNomina) && (
                      <div className="space-y-3">
                        {selectedRequest.EstadoNomina === 'Con_Errores' && (
                          <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">El formato contiene errores. Revisa el comentario del analista en el historial y sube la liquidación corregida.</p>
                        )}
                        <input
                          type="file"
                          accept=".pdf,.png,.jpg,.jpeg,.xlsx,.xls"
                          onChange={(event) => setPayrollFile(event.target.files?.[0] ?? null)}
                          className="block w-full text-sm"
                        />
                        <button
                          disabled={saving || !payrollFile}
                          onClick={() => runPayrollAction(() => solicitudesAPI.uploadPayrollDocument(selectedRequest.Id, payrollFile), 'No se pudo subir la liquidación.')}
                          className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-50"
                        >
                          <Upload size={16} /> {selectedRequest.EstadoNomina === 'Con_Errores' ? 'Subir liquidación corregida' : 'Subir liquidación'}
                        </button>
                      </div>
                    )}
                    {selectedRequest.RolNomina === 'analista' && selectedRequest.EstadoNomina === 'Pendiente_Revision_Nomina' && (
                      <div className="space-y-3">
                        <textarea
                          rows={2}
                          value={payrollComment}
                          onChange={(event) => setPayrollComment(event.target.value)}
                          placeholder="Comentario (obligatorio si no aprueba)"
                          className="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"
                        />
                        <div className="flex flex-wrap gap-3">
                          <button
                            disabled={saving || !payrollComment.trim()}
                            onClick={() => runPayrollAction(() => solicitudesAPI.reviewPayroll(selectedRequest.Id, 'NO', payrollComment.trim()), 'No se pudo registrar la revisión.')}
                            className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                          >
                            <XCircle size={16} /> No aprobado
                          </button>
                          <button
                            disabled={saving}
                            onClick={() => runPayrollAction(() => solicitudesAPI.reviewPayroll(selectedRequest.Id, 'OK', payrollComment.trim()), 'No se pudo registrar la revisión.')}
                            className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                          >
                            <CheckCircle2 size={16} /> OK
                          </button>
                        </div>
                      </div>
                    )}
                    {selectedRequest.RolNomina === 'analista' && selectedRequest.EstadoNomina === 'Pendiente_Pago' && (
                      <button
                        disabled={saving}
                        onClick={() => runPayrollAction(() => solicitudesAPI.markPayrollPaid(selectedRequest.Id), 'No se pudo registrar el pago.')}
                        className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                      >
                        <Banknote size={16} /> Pagado
                      </button>
                    )}
                  </div>
                )}
                {selectedRequest.EstadoSolicitud === 'Aprobada' && (
                  <button
                    type="button"
                    onClick={() => {
                      if (!printSolicitudDocument(selectedRequest)) {
                        setDetailError('El navegador bloqueó la ventana. Permite las ventanas emergentes e inténtalo de nuevo.');
                      }
                    }}
                    className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                  >
                    <FileText size={16} /> Generar documento para archivo físico
                  </button>
                )}
                {!!selectedRequest.Adjuntos?.length && (
                  <div>
                    <h3 className="mb-2 text-sm font-semibold text-slate-700">Adjuntos</h3>
                    <div className="space-y-2">{selectedRequest.Adjuntos.map((attachment) => (
                      <button key={attachment.Id} onClick={() => downloadAttachment(attachment)} className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-blue-700 hover:bg-blue-50">
                        <Download size={15} /> {attachment.NombreArchivo}
                      </button>
                    ))}</div>
                  </div>
                )}
                <div>
                  <h3 className="mb-2 text-sm font-semibold text-slate-700">Historial de aprobación</h3>
                  <ol className="space-y-3 border-l-2 border-slate-200 pl-4">
                    {(selectedRequest.Historial ?? []).map((item) => (
                      <li key={item.Id} className="relative">
                        <span className="absolute -left-[22px] top-1 h-2.5 w-2.5 rounded-full bg-orange-500" />
                        <div className="text-sm font-semibold text-slate-800">{item.Accion} · {item.Nombres} {item.Apellidos}</div>
                        <div className="text-xs text-slate-500">Etapa {item.Nivel === 4 || item.Nivel === '4' ? 'Nómina' : item.Nivel} · {new Date(item.Fecha).toLocaleString()}</div>
                        {item.Comentario && <p className="mt-1 whitespace-pre-wrap text-sm text-slate-600">{item.Comentario}</p>}
                      </li>
                    ))}
                  </ol>
                </div>
                {isPendingForCurrentUser && (
                  <div className="border-t border-slate-100 pt-4">
                    <label className="block text-sm font-semibold text-slate-700">
                      Comentario <span className="font-normal text-slate-500">(obligatorio para rechazar)</span>
                      <textarea
                        rows={3}
                        value={decisionComment}
                        onChange={(event) => setDecisionComment(event.target.value)}
                        className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal"
                        placeholder="Agrega una observación para el solicitante"
                      />
                    </label>
                    {decisionError && <p role="alert" className="mt-2 text-sm text-red-700">{decisionError}</p>}
                    <div className="mt-4 flex flex-wrap justify-end gap-3">
                      <button disabled={saving} onClick={() => handleDecision('Rechazada')} className="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50">
                        <XCircle size={16} /> Rechazar
                      </button>
                      <button disabled={saving} onClick={() => handleDecision('Aprobada')} className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                        <CheckCircle2 size={16} /> Aprobar y continuar
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default SolicitudesPage;
