import React, { useState, useEffect } from 'react';
import { Plus, Edit, Package, Search, User, Calendar, Building2, AlertCircle, CheckCircle, Wrench, UserPlus, UserMinus, Archive, X, Ticket, MessageSquare, Send, Download } from 'lucide-react';
import { gestionAPI } from '../services/gestionAPI';
import { useAuth } from '../../../contexts/AuthContext';

const satisfactionLabels = {
  muy_satisfecho: 'Muy satisfecho',
  satisfecho: 'Satisfecho',
  insatisfecho: 'Insatisfecho',
  muy_insatisfecho: 'Muy insatisfecho',
};

const GestionPage = () => {
  const { hasPermission, user } = useAuth();
  const canViewAllManagement = hasPermission('gestion.ver');
  const canViewIncidents = canViewAllManagement || hasPermission('gestion.incidencias.ver');
  const [activeTab, setActiveTab] = useState(() => (
    hasPermission('gestion.ver') ? 'activos' : 'incidencias'
  ));
  const [activos, setActivos] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [empresas, setEmpresas] = useState([]);
  const [proveedores, setProveedores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [assigningActivo, setAssigningActivo] = useState(null);
  const [users, setUsers] = useState([]);
  const [selectedUser, setSelectedUser] = useState('');
  const [userSearch, setUserSearch] = useState('');
  const [savingAssignment, setSavingAssignment] = useState(false);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [editingAsset, setEditingAsset] = useState(null);
  const [savingAsset, setSavingAsset] = useState(false);
  const [newSupplierName, setNewSupplierName] = useState('');
  const [addingSupplier, setAddingSupplier] = useState(false);
  const [assetForm, setAssetForm] = useState({
    CategoriaId: '',
    EmpresaId: '',
    TipoPropiedad: 'propio',
    ProveedorId: '',
    NumeroFacturaContrato: '',
    FechaInicio: '',
    FechaFin: '',
    Placa: '',
    Marca: '',
    Modelo: '',
    NumeroSerie: '',
    Observaciones: '',
  });
  const [actionError, setActionError] = useState('');
  const [actionMessage, setActionMessage] = useState('');
  const [incidencias, setIncidencias] = useState([]);
  const [categoriasIncidencias, setCategoriasIncidencias] = useState([]);
  const [showIncidentForm, setShowIncidentForm] = useState(false);
  const [selectedIncident, setSelectedIncident] = useState(null);
  const [closingIncident, setClosingIncident] = useState(false);
  const [incidentSaving, setIncidentSaving] = useState(false);
  const [incidentError, setIncidentError] = useState('');
  const [incidentContext, setIncidentContext] = useState({ Ubicacion: '', Empresas: [] });
  const [incidentStatusFilter, setIncidentStatusFilter] = useState('todas');
  const [incidentForm, setIncidentForm] = useState({
    CategoriaId: '',
    Prioridad: 'media',
    Titulo: '',
    Descripcion: '',
    EmpresaId: '',
    Adjunto: null,
  });
  const [mantenimientos, setMantenimientos] = useState([]);
  const [selectedMaintenance, setSelectedMaintenance] = useState(null);
  const [maintenanceForm, setMaintenanceForm] = useState({ Ejecutor: 'ti', ProveedorId: '' });
  const [maintenanceSaving, setMaintenanceSaving] = useState(false);
  const [maintenanceError, setMaintenanceError] = useState('');
  const [licencias, setLicencias] = useState([]);
  const [licenseOptions, setLicenseOptions] = useState({ Empresas: [], Areas: [], Activos: [] });
  const [showLicenseForm, setShowLicenseForm] = useState(false);
  const [editingLicenseId, setEditingLicenseId] = useState(null);
  const [licenseSaving, setLicenseSaving] = useState(false);
  const [licenseError, setLicenseError] = useState('');
  const [licenseAssetSearch, setLicenseAssetSearch] = useState('');
  const [licenseForm, setLicenseForm] = useState({
    Nombre: '',
    Tipo: 'comercial',
    EmpresaId: '',
    AreaId: '',
    Proveedor: '',
    NumeroLicencia: '',
    Cantidad: '1',
    FechaVencimiento: '',
    Notas: '',
    ActivosIds: [],
  });
  const canManageAssets = hasPermission('gestion.administrar_activos');
  const canManageIncidents = hasPermission('gestion.administrar_incidencias');
  const canManageMaintenance = hasPermission('gestion.administrar_mantenimiento');
  const canManageLicenses = hasPermission('gestion.administrar_licencias');

  useEffect(() => {
    loadData();
  }, [activeTab]);

  const loadData = async () => {
    setLoading(true);
    setLoadError('');
    try {
      const incidentRequests = canViewIncidents
        ? Promise.all([
            gestionAPI.getIncidencias(),
            gestionAPI.getCategoriasIncidencias(),
            gestionAPI.getContextoIncidencias(),
          ])
        : Promise.resolve(null);
      const managementRequests = canViewAllManagement
        ? Promise.all([
            gestionAPI.getActivos(),
            gestionAPI.getCategoriasActivos(),
            gestionAPI.getEmpresas(),
            gestionAPI.getProveedores(),
            gestionAPI.getMantenimientosPendientes(),
            gestionAPI.getLicencias(),
            gestionAPI.getOpcionesLicencias(),
          ])
        : Promise.resolve(null);
      const [incidentData, managementData] = await Promise.all([incidentRequests, managementRequests]);
      if (incidentData) {
        const [incidentsResponse, incidentCategoriesResponse, incidentContextResponse] = incidentData;
        setIncidencias(incidentsResponse.data?.data ?? []);
        setCategoriasIncidencias(incidentCategoriesResponse.data?.data ?? []);
        const context = incidentContextResponse.data?.data ?? { Ubicacion: '', Empresas: [] };
        setIncidentContext(context);
        setIncidentForm((current) => ({
          ...current,
          CategoriaId: current.CategoriaId || (incidentCategoriesResponse.data?.data?.[0]?.Id
            ? String(incidentCategoriesResponse.data.data[0].Id)
            : ''),
          EmpresaId: context.Empresas.length === 1 ? String(context.Empresas[0].Id) : current.EmpresaId,
        }));
      }
      if (!managementData) return;
      const [res, categoriesResponse, companiesResponse, providersResponse, maintenanceResponse, licensesResponse, licenseOptionsResponse] = managementData;
      setActivos(res.data?.data ?? []);
      const activeCategories = categoriesResponse.data?.data ?? [];
      const activeCompanies = companiesResponse.data?.data ?? [];
      const activeProviders = providersResponse.data?.data ?? [];
      setCategorias(activeCategories);
      setEmpresas(activeCompanies);
      setProveedores(activeProviders);
      setMantenimientos(maintenanceResponse.data?.data ?? []);
      setLicencias(licensesResponse.data?.data ?? []);
      setLicenseOptions(licenseOptionsResponse.data?.data ?? { Empresas: [], Areas: [], Activos: [] });
      setAssetForm((current) => ({
        ...current,
        CategoriaId: current.CategoriaId || (activeCategories[0]?.Id ? String(activeCategories[0].Id) : ''),
        EmpresaId: current.EmpresaId || (activeCompanies[0]?.Id ? String(activeCompanies[0].Id) : ''),
      }));
      setLicenseForm((current) => ({
        ...current,
        EmpresaId: current.EmpresaId || (licenseOptionsResponse.data?.data?.Empresas?.[0]?.Id ? String(licenseOptionsResponse.data.data.Empresas[0].Id) : ''),
        AreaId: current.AreaId || (licenseOptionsResponse.data?.data?.Areas?.[0]?.Id ? String(licenseOptionsResponse.data.data.Areas[0].Id) : ''),
      }));
    } catch (error) {
      console.error('Error loading data:', error);
      setLoadError(error.response?.data?.message || 'No se pudieron cargar los datos de Gestión Tecnológica. Actualiza la página e inténtalo de nuevo.');
    } finally {
      setLoading(false);
    }
  };

  const filteredActivos = activos.filter(a => {
    const matchesSearch = (a.CategoriaNombre ?? '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                         a.CodigoInventario?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         a.Placa?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         a.Marca?.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesSearch;
  });
  const filteredMantenimientos = mantenimientos.filter((maintenance) => (
    `${maintenance.TipoActivo ?? ''} ${maintenance.CodigoInventario ?? ''} ${maintenance.Placa ?? ''} ${maintenance.EmpresaNombre ?? ''} ${maintenance.Anio ?? ''} ${maintenance.Semestre ?? ''}`
      .toLocaleLowerCase()
      .includes(searchTerm.toLocaleLowerCase())
  ));
  const filteredLicencias = licencias.filter((license) => (
    [
      license.Nombre, license.Tipo, license.EmpresaNombre, license.AreaNombre,
      license.Proveedor, license.NumeroLicencia, license.FechaVencimiento,
      ...(license.Activos ?? []).flatMap((asset) => [asset.CodigoInventario, asset.TipoActivo, asset.Placa]),
    ].some((value) => String(value ?? '').toLocaleLowerCase().includes(searchTerm.toLocaleLowerCase()))
  ));

  const getAssetStatusColor = (estado) => {
    const colors = {
      nuevo: 'bg-blue-50 text-blue-700 border-blue-200',
      en_uso: 'bg-green-50 text-green-700 border-green-200',
      mantenimiento: 'bg-yellow-50 text-yellow-700 border-yellow-200',
      dañado: 'bg-red-50 text-red-700 border-red-200',
      obsoleto: 'bg-purple-50 text-purple-700 border-purple-200',
      retirado: 'bg-gray-50 text-gray-700 border-gray-200',
    };
    return colors[estado] || 'bg-gray-50 text-gray-700';
  };

  const openAssignment = async (activo) => {
    setActionError('');
    setActionMessage('');
    setAssigningActivo(activo);
    setSelectedUser('');
    setUserSearch('');
    try {
      const response = await gestionAPI.getAssignableUsers();
      setUsers(response.data?.data ?? []);
    } catch (error) {
      setActionError(error.response?.data?.message || 'No se pudieron cargar los usuarios activos.');
    }
  };

  const handleAssign = async (event) => {
    event.preventDefault();
    if (!assigningActivo || !selectedUser) return;
    setSavingAssignment(true);
    setActionError('');
    try {
      const response = await gestionAPI.assignActivo(assigningActivo.Id, Number(selectedUser));
      setActionMessage(response.data?.message || 'Activo asignado correctamente.');
      setAssigningActivo(null);
      await loadData();
    } catch (error) {
      setActionError(error.response?.data?.message || 'No se pudo asignar el activo.');
    } finally {
      setSavingAssignment(false);
    }
  };

  const handleUnassign = async (activo) => {
    if (!window.confirm(`¿Retirar ${activo.CodigoInventario} a su usuario actual? El activo quedará disponible.`)) return;
    setActionError('');
    setActionMessage('');
    try {
      const response = await gestionAPI.unassignActivo(activo.Id);
      setActionMessage(response.data?.message || 'Activo desasignado correctamente.');
      await loadData();
    } catch (error) {
      setActionError(error.response?.data?.message || 'No se pudo retirar la asignación del activo.');
    }
  };

  const handleRetire = async (activo) => {
    if (!window.confirm(`¿Dar de baja ${activo.CodigoInventario}? Se quitará su asignación y no podrá volver a asignarse.`)) return;
    setActionError('');
    setActionMessage('');
    try {
      const response = await gestionAPI.retireActivo(activo.Id);
      setActionMessage(response.data?.message || 'Activo dado de baja.');
      await loadData();
    } catch (error) {
      setActionError(error.response?.data?.message || 'No se pudo dar de baja el activo.');
    }
  };

  const handleCreateAsset = async (event) => {
    event.preventDefault();
    setActionError('');
    setActionMessage('');
    setSavingAsset(true);
    try {
      const payload = {
        ...assetForm,
        CategoriaId: Number(assetForm.CategoriaId),
        EmpresaId: Number(assetForm.EmpresaId),
        ProveedorId: assetForm.ProveedorId ? Number(assetForm.ProveedorId) : null,
        NumeroFacturaContrato: assetForm.NumeroFacturaContrato.trim() || null,
        FechaInicio: assetForm.FechaInicio || null,
        FechaFin: assetForm.FechaFin || null,
        Placa: assetForm.Placa.trim() || null,
        Marca: assetForm.Marca.trim() || null,
        Modelo: assetForm.Modelo.trim() || null,
        NumeroSerie: assetForm.NumeroSerie.trim() || null,
        Observaciones: assetForm.Observaciones.trim() || null,
      };
      const response = editingAsset
        ? await gestionAPI.updateActivo(editingAsset.Id, payload)
        : await gestionAPI.createActivo(payload);
      setShowCreateForm(false);
      setEditingAsset(null);
      setAssetForm((current) => ({ ...current, NumeroFacturaContrato: '', FechaInicio: '', FechaFin: '', Placa: '', Marca: '', Modelo: '', NumeroSerie: '', Observaciones: '', ProveedorId: '' }));
      setActionMessage(response.data?.message || (editingAsset ? 'Activo actualizado correctamente.' : 'Activo registrado correctamente.'));
      await loadData();
    } catch (error) {
      setActionError(error.response?.data?.message || 'No se pudo registrar el activo.');
    } finally {
      setSavingAsset(false);
    }
  };

  const openAssetEditor = (asset) => {
    setActionError('');
    setEditingAsset(asset);
    setAssetForm({
      CategoriaId: String(asset.CategoriaId),
      EmpresaId: String(asset.EmpresaId ?? ''),
      TipoPropiedad: asset.TipoPropiedad || 'propio',
      ProveedorId: String(asset.ProveedorId ?? ''),
      NumeroFacturaContrato: asset.NumeroFacturaContrato || '',
      FechaInicio: asset.FechaInicio?.slice(0, 10) || '',
      FechaFin: asset.FechaFin?.slice(0, 10) || '',
      Placa: asset.Placa || '',
      Marca: asset.Marca || '',
      Modelo: asset.Modelo || '',
      NumeroSerie: asset.NumeroSerie || '',
      Observaciones: asset.Observaciones || '',
    });
    setShowCreateForm(true);
  };

  const handleCreateSupplier = async () => {
    setActionError('');
    setAddingSupplier(true);
    try {
      const response = await gestionAPI.createProveedor({ Nombre: newSupplierName.trim() });
      const suppliersResponse = await gestionAPI.getProveedores();
      setProveedores(suppliersResponse.data?.data ?? []);
      setAssetForm((current) => ({ ...current, ProveedorId: String(response.data?.data?.Id ?? '') }));
      setNewSupplierName('');
    } catch (error) {
      setActionError(error.response?.data?.message || 'No se pudo agregar el proveedor.');
    } finally {
      setAddingSupplier(false);
    }
  };

  const handleCompleteMaintenance = async (event) => {
    event.preventDefault();
    if (!selectedMaintenance) return;
    setMaintenanceSaving(true);
    setMaintenanceError('');
    try {
      await gestionAPI.completarMantenimiento(selectedMaintenance.ActivoId, selectedMaintenance.Anio, selectedMaintenance.Semestre, {
        Ejecutor: maintenanceForm.Ejecutor,
        ProveedorId: maintenanceForm.Ejecutor === 'proveedor' ? Number(maintenanceForm.ProveedorId) : null,
      });
      setSelectedMaintenance(null);
      await loadData();
    } catch (error) {
      setMaintenanceError(error.response?.data?.message || 'No se pudo registrar el mantenimiento.');
    } finally {
      setMaintenanceSaving(false);
    }
  };

  const handleCreateLicense = async (event) => {
    event.preventDefault();
    setLicenseSaving(true);
    setLicenseError('');
    try {
      const payload = {
        ...licenseForm,
        EmpresaId: Number(licenseForm.EmpresaId),
        AreaId: Number(licenseForm.AreaId),
        Cantidad: Number(licenseForm.Cantidad),
        ActivosIds: licenseForm.ActivosIds.map(Number),
        FechaVencimiento: licenseForm.FechaVencimiento || null,
      };
      const response = editingLicenseId
        ? await gestionAPI.updateLicencia(editingLicenseId, payload)
        : await gestionAPI.createLicencia(payload);
      setShowLicenseForm(false);
      setEditingLicenseId(null);
      setLicenseForm((current) => ({ ...current, Nombre: '', Proveedor: '', NumeroLicencia: '', Cantidad: '1', FechaVencimiento: '', Notas: '', ActivosIds: [] }));
      setActionMessage(response.data?.message || (editingLicenseId ? 'Licencia actualizada correctamente.' : 'Licencia registrada correctamente.'));
      await loadData();
    } catch (error) {
      const fieldErrors = error.response?.data?.errors;
      setLicenseError(Object.values(fieldErrors ?? {}).flat().join(' ')
        || error.response?.data?.message
        || 'No se pudo registrar la licencia.');
    } finally {
      setLicenseSaving(false);
    }
  };

  const openLicenseEditor = (license) => {
    setLicenseError('');
    setLicenseAssetSearch('');
    setEditingLicenseId(license.Id);
    setLicenseForm({
      Nombre: license.Nombre ?? '',
      Tipo: license.Tipo ?? 'comercial',
      EmpresaId: String(license.EmpresaId ?? ''),
      AreaId: String(license.AreaId ?? ''),
      Proveedor: license.Proveedor ?? '',
      NumeroLicencia: license.NumeroLicencia ?? '',
      Cantidad: String(license.Cantidad ?? 1),
      FechaVencimiento: license.FechaVencimiento?.slice(0, 10) ?? '',
      Notas: license.Notas ?? '',
      ActivosIds: (license.Activos ?? []).map((asset) => String(asset.ActivoId)),
    });
    setShowLicenseForm(true);
  };

  const handleCreateIncident = async (event) => {
    event.preventDefault();
    setIncidentSaving(true);
    setIncidentError('');
    const body = new FormData();
    body.append('CategoriaId', String(Number(incidentForm.CategoriaId)));
    body.append('Prioridad', incidentForm.Prioridad);
    body.append('Titulo', incidentForm.Titulo.trim());
    body.append('Descripcion', incidentForm.Descripcion.trim());
    if (incidentContext.Empresas.length > 1) body.append('EmpresaId', incidentForm.EmpresaId);
    if (incidentForm.Adjunto) body.append('Adjunto', incidentForm.Adjunto);
    try {
      await gestionAPI.createIncidencia(body);
      setIncidentForm((current) => ({
        ...current,
        Titulo: '',
        Descripcion: '',
        EmpresaId: incidentContext.Empresas.length === 1 ? String(incidentContext.Empresas[0].Id) : '',
        Prioridad: 'media',
        Adjunto: null,
      }));
      setShowIncidentForm(false);
      await loadData();
    } catch (error) {
      const fieldErrors = error.response?.data?.errors;
      setIncidentError(Object.values(fieldErrors ?? {}).flat().join(' ')
        || error.response?.data?.message
        || 'No se pudo crear el ticket de incidencia.');
    } finally {
      setIncidentSaving(false);
    }
  };

  const downloadIncidentAttachment = async () => {
    if (!selectedIncident) return;
    try {
      const response = await gestionAPI.downloadIncidenciaAdjunto(selectedIncident.Id);
      const url = URL.createObjectURL(response.data);
      const link = document.createElement('a');
      link.href = url;
      link.download = selectedIncident.ArchivoAdjuntoNombre || 'evidencia';
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (error) {
      setIncidentError(error.response?.data?.message || 'No fue posible descargar la evidencia.');
    }
  };

  const openIncident = async (incident) => {
    setIncidentError('');
    setIncidentSaving(true);
    try {
      const response = await gestionAPI.getIncidencia(incident.Id);
      setSelectedIncident({
        ...response.data?.data,
        SatisfaccionTiempoSolucion: '',
        SatisfaccionCalidadAtencion: '',
        SatisfaccionEficaciaSolucion: '',
        ComentarioSatisfaccion: '',
      });
    } catch (error) {
      setIncidentError(error.response?.data?.message || 'No se pudo cargar el detalle del ticket.');
    } finally {
      setIncidentSaving(false);
    }
  };

  const handleUpdateIncident = async (event) => {
    event.preventDefault();
    if (!selectedIncident) return;
    setIncidentSaving(true);
    setIncidentError('');
    const payload = {
      Estado: selectedIncident.Estado,
      Prioridad: selectedIncident.Prioridad,
      Comentario: selectedIncident.NuevoComentario ?? '',
    };
    try {
      await gestionAPI.updateIncidencia(selectedIncident.Id, payload);
      const response = await gestionAPI.getIncidencia(selectedIncident.Id);
      setSelectedIncident(response.data?.data ?? null);
      await loadData();
    } catch (error) {
      const fieldErrors = error.response?.data?.errors;
      setIncidentError(Object.values(fieldErrors ?? {}).flat().join(' ')
        || error.response?.data?.message
        || 'No se pudo actualizar el ticket.');
    } finally {
      setIncidentSaving(false);
    }
  };

  const handleCloseIncident = async (event) => {
    event.preventDefault();
    if (!selectedIncident) return;
    setIncidentSaving(true);
    setIncidentError('');
    try {
      await gestionAPI.cerrarIncidencia(selectedIncident.Id, {
        SatisfaccionTiempoSolucion: selectedIncident.SatisfaccionTiempoSolucion,
        SatisfaccionCalidadAtencion: selectedIncident.SatisfaccionCalidadAtencion,
        SatisfaccionEficaciaSolucion: selectedIncident.SatisfaccionEficaciaSolucion,
        ComentarioSatisfaccion: selectedIncident.ComentarioSatisfaccion ?? '',
      });
      const response = await gestionAPI.getIncidencia(selectedIncident.Id);
      setSelectedIncident(response.data?.data ?? null);
      await loadData();
    } catch (error) {
      const fieldErrors = error.response?.data?.errors;
      setIncidentError(Object.values(fieldErrors ?? {}).flat().join(' ')
        || error.response?.data?.message
        || 'No se pudo cerrar la incidencia.');
    } finally {
      setIncidentSaving(false);
    }
  };

  const getIncidentStatusStyle = (status) => ({
    nueva: 'bg-blue-50 text-blue-700 border-blue-200',
    pendiente: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    cerrada: 'bg-slate-100 text-slate-700 border-slate-200',
  }[status] || 'bg-slate-50 text-slate-600 border-slate-200');

  const getIncidentPriorityStyle = (priority) => ({
    baja: 'bg-green-50 text-green-700',
    media: 'bg-yellow-50 text-yellow-700',
    alta: 'bg-orange-50 text-orange-700',
    critica: 'bg-red-50 text-red-700',
  }[priority] || 'bg-slate-50 text-slate-600');

  const filteredIncidencias = incidencias.filter((incident) => {
    const query = searchTerm.toLowerCase();
    const matchesSearch = [incident.Ticket, incident.Titulo, incident.SolicitanteNombre, incident.SolicitanteApellidos]
      .some((value) => (value ?? '').toLowerCase().includes(query));
    return matchesSearch && (incidentStatusFilter === 'todas' || incident.Estado === incidentStatusFilter);
  });
  const isIncidentRequester = selectedIncident
    && Number(selectedIncident.SolicitanteId) === Number(user?.id);

  const renderActivos = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-slate-800">Activos Tecnológicos</h2>
        {canManageAssets && <button onClick={() => { setActionError(''); setEditingAsset(null); setShowCreateForm(true); }} disabled={categorias.length === 0} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
          <Plus size={16} /> Nuevo Activo
        </button>}
      </div>
      {loadError && <div role="alert" className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{loadError}</div>}

      {actionError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{actionError}</div>}
      {actionMessage && <div role="status" className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{actionMessage}</div>}

      {filteredActivos.length === 0 ? (
        <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500">
          No hay activos tecnológicos que coincidan con la búsqueda.
        </div>
      ) : (
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {filteredActivos.map((activo) => (
          <div key={activo.Id} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition">
            <div className="flex items-start justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="rounded-xl p-3" style={{ backgroundColor: `${activo.CategoriaColor}20`, color: activo.CategoriaColor }}>
                  <Package size={24} />
                </div>
                <div>
                  <h3 className="text-lg font-bold text-slate-800">{activo.CategoriaNombre || 'Activo tecnológico'}</h3>
                  <p className="text-xs text-slate-500 font-mono">{activo.CodigoInventario}</p>
                </div>
              </div>
              <span className={`inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold border ${getAssetStatusColor(activo.EstadoActivo)}`}>
                {(activo.EstadoActivo ?? 'nuevo').replace('_', ' ')}
              </span>
            </div>

            <div className="space-y-2 text-sm text-slate-600 mb-4">
              <div className="flex items-center gap-2">
                <span className="font-semibold">Marca:</span>
                <span>{activo.Marca || '-'}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="font-semibold">Modelo:</span>
                <span>{activo.Modelo || '-'}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="font-semibold">Serie:</span>
                <span className="font-mono">{activo.NumeroSerie || '-'}</span>
              </div>
              <div className="flex items-center gap-2">
                <User size={14} />
                <span>{activo.AsignadoNombre ? `${activo.AsignadoNombre} ${activo.AsignadoApellidos}` : 'Sin asignar'}</span>
              </div>
              <div className="flex items-center gap-2">
                <Building2 size={14} />
                <span>{activo.EmpresaNombre || 'Empresa sin asignar'}</span>
              </div>
              <div><span className="font-semibold">Placa:</span> <span className="font-mono">{activo.Placa || '-'}</span></div>
              <div><span className="font-semibold">Propiedad:</span> {activo.TipoPropiedad === 'arriendo' ? 'En arriendo' : activo.TipoPropiedad === 'propio' ? 'Propio' : '-'}</div>
              <div><span className="font-semibold">Proveedor:</span> {activo.ProveedorNombre || '-'}</div>
              <div className="flex items-center gap-2">
                <Calendar size={14} />
                <span>Inicio: {activo.FechaInicio ? new Date(`${activo.FechaInicio}T00:00:00`).toLocaleDateString() : '-'}</span>
              </div>
              <div className="flex items-center gap-2">
                <Calendar size={14} />
                <span>Fin garantía/contrato: {activo.FechaFin ? new Date(`${activo.FechaFin}T00:00:00`).toLocaleDateString() : '-'}</span>
              </div>
              <div><span className="font-semibold">Factura / contrato:</span> {activo.NumeroFacturaContrato || '-'}</div>
              {activo.Observaciones && <div className="border-t border-slate-100 pt-2"><span className="font-semibold">Observaciones:</span> {activo.Observaciones}</div>}
            </div>

            {canManageAssets && activo.EstadoActivo !== 'retirado' && (
              <div className="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                  <button onClick={() => openAssetEditor(activo)} className="inline-flex items-center justify-center gap-1 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                    <Edit size={14} /> Editar
                  </button>
                  {activo.AsignadoA ? (
                  <button onClick={() => handleUnassign(activo)} className="inline-flex flex-1 items-center justify-center gap-1 rounded-lg bg-amber-50 px-2 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                    <UserMinus size={14} /> Retirar asignación
                  </button>
                ) : (
                  <button onClick={() => openAssignment(activo)} disabled={!activo.Estado || activo.UsuarioAsignacionPendienteId} className="inline-flex flex-1 items-center justify-center gap-1 rounded-lg bg-green-50 px-2 py-2 text-xs font-semibold text-green-700 hover:bg-green-100 disabled:opacity-50">
                    <UserPlus size={14} /> {activo.UsuarioAsignacionPendienteNombre ? 'Solicitud pendiente' : 'Solicitar asignación'}
                  </button>
                )}
                <button onClick={() => handleRetire(activo)} className="inline-flex items-center justify-center gap-1 rounded-lg bg-red-50 px-2 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                  <Archive size={14} /> Dar de baja
                </button>
              </div>
            )}
          </div>
        ))}
      </div>
      )}
    </div>
  );

  const renderMantenimiento = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">Mantenimientos pendientes</h2>
          <p className="mt-1 text-sm text-slate-600">Períodos semestrales pendientes desde la compra o inicio del contrato de cada activo.</p>
        </div>
      </div>
      {actionError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{actionError}</div>}
      {filteredMantenimientos.length === 0 ? (
        <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500">
          {mantenimientos.length ? 'No hay mantenimientos que coincidan con la búsqueda.' : 'No hay períodos semestrales pendientes para los activos activos.'}
        </div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
          <table className="w-full min-w-[700px]">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Activo</th>
                <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Empresa</th>
                <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Período</th>
                <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Fecha límite</th>
                {canManageMaintenance && <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Acción</th>}
              </tr>
            </thead>
            <tbody>
              {filteredMantenimientos.map((maintenance) => (
                <tr key={`${maintenance.ActivoId}-${maintenance.Anio}-${maintenance.Semestre}`} className="border-t border-slate-100">
                  <td className="px-5 py-4">
                    <div className="font-semibold text-slate-800">{maintenance.TipoActivo || 'Activo tecnológico'}</div>
                    <div className="text-xs font-mono text-slate-500">{maintenance.CodigoInventario}{maintenance.Placa ? ` · ${maintenance.Placa}` : ''}</div>
                  </td>
                  <td className="px-5 py-4 text-sm text-slate-600">{maintenance.EmpresaNombre || '-'}</td>
                  <td className="px-5 py-4 text-sm text-slate-600">Semestre {maintenance.Semestre} · {maintenance.Anio}</td>
                  <td className="px-5 py-4 text-sm text-slate-600">{new Date(`${maintenance.FechaLimite}T00:00:00`).toLocaleDateString()}</td>
                  {canManageMaintenance && (
                    <td className="px-5 py-4">
                      <button
                        type="button"
                        onClick={() => { setMaintenanceError(''); setMaintenanceForm({ Ejecutor: 'ti', ProveedorId: '' }); setSelectedMaintenance(maintenance); }}
                        className="rounded-lg bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 hover:bg-green-100"
                      >
                        Registrar realizado
                      </button>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );

  const renderIncidencias = () => (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">Tickets de incidencias</h2>
          <p className="mt-1 text-sm text-slate-600">Reporta un problema tecnológico y consulta su seguimiento.</p>
        </div>
        <button
          onClick={() => { setIncidentError(''); setShowIncidentForm((visible) => !visible); }}
          className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700"
        >
          {showIncidentForm ? <X size={16} /> : <Plus size={16} />}
          {showIncidentForm ? 'Cancelar' : 'Nueva Incidencia'}
        </button>
      </div>

      {incidentError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{incidentError}</div>}

      {showIncidentForm && (
        <form onSubmit={handleCreateIncident} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div>
            <h3 className="text-lg font-bold text-slate-800">Crear ticket</h3>
            <p className="mt-1 text-sm text-slate-600">El número de ticket y su estado inicial se asignarán automáticamente.</p>
          </div>
          {incidentContext.Empresas.length === 0 && <div role="alert" className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Tu usuario no tiene una empresa asignada; contacta a un administrador para poder crear tickets.</div>}
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-semibold text-slate-700">
              Categoría <span className="text-red-600">*</span>
              <select
                required
                value={incidentForm.CategoriaId}
                onChange={(event) => setIncidentForm({ ...incidentForm, CategoriaId: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
              >
                {categoriasIncidencias.map((category) => <option key={category.Id} value={category.Id}>{category.Nombre}</option>)}
              </select>
            </label>
            <label className="text-sm font-semibold text-slate-700">
              Prioridad
              <select
                value={incidentForm.Prioridad}
                onChange={(event) => setIncidentForm({ ...incidentForm, Prioridad: event.target.value })}
                className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
              >
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
              </select>
            </label>
            <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
              Título <span className="text-red-600">*</span>
              <input
                required
                maxLength={255}
                value={incidentForm.Titulo}
                onChange={(event) => setIncidentForm({ ...incidentForm, Titulo: event.target.value })}
                placeholder="Resume brevemente el problema"
                className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
            <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
              Descripción <span className="text-red-600">*</span>
              <textarea
                required
                rows={4}
                value={incidentForm.Descripcion}
                onChange={(event) => setIncidentForm({ ...incidentForm, Descripcion: event.target.value })}
                placeholder="Describe el error, el equipo afectado y los pasos para reproducirlo"
                className="mt-2 w-full resize-y rounded-xl border border-slate-200 px-4 py-3"
              />
            </label>
            <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
              Ubicación del usuario
              <input value={incidentContext.Ubicacion || 'No hay ubicación registrada en el perfil'} readOnly className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-600" />
            </label>
            {incidentContext.Empresas.length > 1 ? (
              <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
                Empresa <span className="text-red-600">*</span>
                <select required value={incidentForm.EmpresaId} onChange={(event) => setIncidentForm({ ...incidentForm, EmpresaId: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                  <option value="">Selecciona una empresa</option>
                  {incidentContext.Empresas.map((company) => <option key={company.Id} value={company.Id}>{company.Nombre}</option>)}
                </select>
              </label>
            ) : (
              <div className="text-sm text-slate-700 sm:col-span-2">
                <span className="font-semibold">Empresa:</span> {incidentContext.Empresas[0]?.Nombre || 'Sin empresa asignada'}
              </div>
            )}
            <label className="text-sm font-semibold text-slate-700 sm:col-span-2">
              Evidencia adjunta <span className="font-normal text-slate-400">(opcional)</span>
              <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z"
                onChange={(event) => setIncidentForm({ ...incidentForm, Adjunto: event.target.files?.[0] ?? null })}
                className="mt-2 block w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"
              />
              <span className="mt-1 block text-xs font-normal text-slate-500">
                Fotos, PDF, documentos y archivos comprimidos. Máximo 10 MB.
                {incidentForm.Adjunto ? ` Seleccionado: ${incidentForm.Adjunto.name}` : ''}
              </span>
            </label>
          </div>
          {categoriasIncidencias.length === 0 && <p className="text-sm text-red-700">No hay categorías activas para registrar el ticket.</p>}
          <div className="flex justify-end">
            <button type="submit" disabled={incidentSaving || categoriasIncidencias.length === 0 || incidentContext.Empresas.length === 0 || (incidentContext.Empresas.length > 1 && !incidentForm.EmpresaId)} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-3 font-semibold text-white hover:bg-green-700 disabled:opacity-50">
              <Send size={16} /> {incidentSaving ? 'Creando...' : 'Crear ticket'}
            </button>
          </div>
        </form>
      )}

      <div className="flex flex-wrap gap-2">
        {['todas', 'nueva', 'pendiente', 'cerrada'].map((status) => (
          <button
            key={status}
            onClick={() => setIncidentStatusFilter(status)}
            className={`rounded-xl px-3 py-2 text-xs font-semibold transition ${incidentStatusFilter === status ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}
          >
            {(status === 'todas' ? 'Todas' : status).replace('_', ' ').toUpperCase()}
          </button>
        ))}
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table className="w-full min-w-[760px]">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Ticket</th>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Incidencia</th>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Prioridad</th>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Estado</th>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Solicitante</th>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Fecha</th>
              <th className="px-5 py-3 text-left text-xs font-semibold text-slate-600">Seguimiento</th>
            </tr>
          </thead>
          <tbody>
            {filteredIncidencias.map((incident) => (
              <tr key={incident.Id} className="border-t border-slate-100 hover:bg-slate-50">
                <td className="px-5 py-4 font-mono text-sm font-semibold text-green-700">{incident.Ticket}</td>
                <td className="px-5 py-4">
                  <button onClick={() => openIncident(incident)} className="text-left">
                    <div className="font-semibold text-slate-800 hover:text-green-700">{incident.Titulo}</div>
                    <div className="mt-1 text-xs text-slate-500">{incident.CategoriaNombre}{incident.Ubicacion ? ` · ${incident.Ubicacion}` : ''}{incident.EmpresaNombre ? ` · ${incident.EmpresaNombre}` : ''}</div>
                  </button>
                </td>
                <td className="px-5 py-4"><span className={`rounded-full px-3 py-1 text-xs font-semibold ${getIncidentPriorityStyle(incident.Prioridad)}`}>{incident.Prioridad}</span></td>
                <td className="px-5 py-4"><span className={`rounded-full border px-3 py-1 text-xs font-semibold ${getIncidentStatusStyle(incident.Estado)}`}>{incident.Estado.replace('_', ' ')}</span></td>
                <td className="px-5 py-4 text-sm text-slate-600">{incident.SolicitanteNombre} {incident.SolicitanteApellidos}</td>
                <td className="px-5 py-4 text-sm text-slate-600">{new Date(incident.FechaCreacion).toLocaleDateString()}</td>
                <td className="px-5 py-4">
                  <button onClick={() => openIncident(incident)} className="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                    <MessageSquare size={14} /> {incident.Seguimientos ?? 0}
                  </button>
                </td>
              </tr>
            ))}
            {filteredIncidencias.length === 0 && (
              <tr><td colSpan="7" className="px-5 py-12 text-center text-slate-500">No hay tickets que coincidan con la búsqueda o el estado seleccionado.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );

  const renderLicencias = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">Licencias de Software</h2>
          <p className="mt-1 text-sm text-slate-600">Licencias activas y vencimientos próximos (30 días).</p>
        </div>
        {canManageLicenses && (
          <button onClick={() => { setLicenseError(''); setLicenseAssetSearch(''); setEditingLicenseId(null); setLicenseForm({ Nombre: '', Tipo: 'comercial', EmpresaId: licenseOptions.Empresas[0]?.Id ? String(licenseOptions.Empresas[0].Id) : '', AreaId: licenseOptions.Areas[0]?.Id ? String(licenseOptions.Areas[0].Id) : '', Proveedor: '', NumeroLicencia: '', Cantidad: '1', FechaVencimiento: '', Notas: '', ActivosIds: [] }); setShowLicenseForm(true); }} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 transition">
            <Plus size={16} /> Nueva Licencia
          </button>
        )}
      </div>
      {licenseError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{licenseError}</div>}
      {actionMessage && <div role="status" className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{actionMessage}</div>}
      {filteredLicencias.length === 0 ? (
        <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500">
          {licencias.length ? 'No hay licencias que coincidan con la búsqueda.' : 'No hay licencias activas registradas.'}
        </div>
      ) : (
        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        {filteredLicencias.map((license) => (
            <article key={license.Id} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <h3 className="text-lg font-bold text-slate-800">{license.Nombre}</h3>
                  <p className="mt-1 text-sm text-slate-500">{license.Tipo}</p>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${license.EstadoVencimiento === 'vencida' ? 'bg-red-50 text-red-700' : license.EstadoVencimiento === 'proxima' ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700'}`}>
                  {license.EstadoVencimiento === 'vencida' ? 'Vencida' : license.EstadoVencimiento === 'proxima' ? 'Próxima a vencer' : 'Vigente'}
                </span>
              </div>
              <dl className="mt-4 space-y-2 text-sm text-slate-600">
                <div><dt className="inline font-semibold">Empresa:</dt> <dd className="inline">{license.EmpresaNombre}</dd></div>
                <div><dt className="inline font-semibold">Área:</dt> <dd className="inline">{license.AreaNombre}</dd></div>
                <div><dt className="inline font-semibold">Proveedor:</dt> <dd className="inline">{license.Proveedor || '-'}</dd></div>
                <div><dt className="inline font-semibold">Vencimiento:</dt> <dd className="inline">{license.FechaVencimiento ? new Date(`${license.FechaVencimiento}T00:00:00`).toLocaleDateString() : 'Sin fecha de vencimiento'}</dd></div>
                <div><dt className="inline font-semibold">Licencias:</dt> <dd className="inline">{license.CantidadUsada} de {license.Cantidad}</dd></div>
              </dl>
              <div className="mt-4 border-t border-slate-100 pt-4">
                <h4 className="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700"><Package size={15} /> Activos asociados</h4>
                {license.Activos.length ? (
                  <ul className="space-y-1 text-xs text-slate-600">
                    {license.Activos.map((asset) => <li key={asset.ActivoId}>{asset.CodigoInventario} · {asset.TipoActivo}{asset.Placa ? ` · ${asset.Placa}` : ''}</li>)}
                  </ul>
                ) : <p className="text-xs text-slate-500">Sin activos asociados</p>}
              </div>
              {canManageLicenses && (
                <button
                  type="button"
                  onClick={() => openLicenseEditor(license)}
                  className="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                >
                  <Edit size={15} /> Renovar / actualizar
                </button>
              )}
            </article>
          ))}
        </div>
      )}
    </div>
  );

  return (
    <div className="p-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-slate-800 mb-2">Gestión Tecnológica</h1>
        <p className="text-slate-600">Administración de activos tecnológicos, mantenimientos e incidencias</p>
      </div>

      <div className="flex gap-4 mb-6">
        {canViewAllManagement && (
          <>
            <button
              onClick={() => setActiveTab('activos')}
              className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
                activeTab === 'activos' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              }`}
            >
              <Package size={18} />
              Activos
            </button>
            <button
              onClick={() => setActiveTab('mantenimiento')}
              className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
                activeTab === 'mantenimiento' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              }`}
            >
              <Wrench size={18} />
              Mantenimiento
            </button>
          </>
        )}
        {canViewIncidents && (
          <button
            onClick={() => setActiveTab('incidencias')}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
              activeTab === 'incidencias' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`}
          >
            <AlertCircle size={18} />
            Incidencias
          </button>
        )}
        {canViewAllManagement && (
          <button
            onClick={() => setActiveTab('licencias')}
            className={`flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition ${
              activeTab === 'licencias' ? 'bg-green-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            }`}
          >
            <CheckCircle size={18} />
            Licencias
          </button>
        )}
      </div>

      <div className="flex gap-4 mb-6">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={18} />
          <input
            type="text"
            placeholder={{
              activos: 'Buscar activos...',
              mantenimiento: 'Buscar por activo, empresa o período...',
              incidencias: 'Buscar por ticket, título o solicitante...',
              licencias: 'Buscar por software, empresa, área o activo...',
            }[activeTab]}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-green-500"
          />
        </div>
      </div>

      {loading ? (
        <div className="text-center py-12 text-slate-500">Cargando...</div>
      ) : (
        <>
          {canViewAllManagement && activeTab === 'activos' && renderActivos()}
          {canViewAllManagement && activeTab === 'mantenimiento' && renderMantenimiento()}
          {activeTab === 'incidencias' && renderIncidencias()}
          {canViewAllManagement && activeTab === 'licencias' && renderLicencias()}
        </>
      )}

      {assigningActivo && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) setAssigningActivo(null); }}>
          <form onSubmit={handleAssign} role="dialog" aria-modal="true" aria-labelledby="assign-asset-title" className="w-full max-w-md space-y-5 rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="assign-asset-title" className="text-xl font-bold text-slate-800">Asignar activo</h2>
                <p className="mt-1 text-sm text-slate-600">{assigningActivo.CategoriaNombre} · {assigningActivo.CodigoInventario}</p>
              </div>
              <button type="button" onClick={() => setAssigningActivo(null)} aria-label="Cerrar" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>
            {actionError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{actionError}</div>}
            <div>
              <label htmlFor="asset-user" className="mb-2 block text-sm font-semibold text-slate-700">Usuario <span className="text-red-600">*</span></label>
              <input
                id="asset-user"
                type="search"
                required
                value={userSearch}
                onChange={(event) => { setUserSearch(event.target.value); setSelectedUser(''); }}
                placeholder="Buscar por nombre, apellido o correo..."
                autoComplete="off"
                className="w-full rounded-xl border border-slate-200 px-4 py-3"
              />
              {userSearch && !selectedUser && (
                <ul className="mt-2 max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white">
                  {users
                    .filter((user) => `${user.Nombres} ${user.Apellidos} ${user.EmailCorporativo}`.toLocaleLowerCase().includes(userSearch.toLocaleLowerCase()))
                    .slice(0, 10)
                    .map((user) => (
                      <li key={user.Id}>
                        <button type="button" onClick={() => { setSelectedUser(String(user.Id)); setUserSearch(`${user.Nombres} ${user.Apellidos} — ${user.EmailCorporativo}`); }} className="w-full px-4 py-2 text-left text-sm hover:bg-slate-50">
                          {user.Nombres} {user.Apellidos} — {user.EmailCorporativo}
                        </button>
                      </li>
                    ))}
                  {users.filter((user) => `${user.Nombres} ${user.Apellidos} ${user.EmailCorporativo}`.toLocaleLowerCase().includes(userSearch.toLocaleLowerCase())).length === 0 && (
                    <li className="px-4 py-3 text-sm text-slate-500">No se encontraron usuarios.</li>
                  )}
                </ul>
              )}
              {selectedUser && <p className="mt-2 text-xs text-green-700">Usuario seleccionado. La asignación se completará después de su aceptación.</p>}
            </div>
            {users.length === 0 && <p className="text-sm text-amber-700">No hay usuarios activos disponibles para asignar.</p>}
            <div className="flex justify-end gap-3">
              <button type="button" onClick={() => setAssigningActivo(null)} className="rounded-xl border border-slate-200 px-4 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
              <button type="submit" disabled={savingAssignment || !selectedUser} className="rounded-xl bg-green-600 px-4 py-2.5 font-semibold text-white hover:bg-green-700 disabled:opacity-50">{savingAssignment ? 'Enviando...' : 'Enviar solicitud'}</button>
            </div>
          </form>
        </div>
      )}

      {showCreateForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget && !savingAsset) setShowCreateForm(false); }}>
          <form onSubmit={handleCreateAsset} role="dialog" aria-modal="true" aria-labelledby="create-asset-title" className="my-auto w-full max-w-2xl space-y-5 rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="create-asset-title" className="text-xl font-bold text-slate-800">{editingAsset ? 'Editar activo' : 'Registrar activo'}</h2>
                <p className="mt-1 text-sm text-slate-600">{editingAsset ? `Actualiza los datos de ${editingAsset.CodigoInventario}.` : 'El nuevo activo quedará disponible, sin asignar, hasta que se entregue a un usuario.'}</p>
              </div>
              <button type="button" onClick={() => setShowCreateForm(false)} aria-label="Cerrar" disabled={savingAsset} className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>
            {actionError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{actionError}</div>}
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="asset-category" className="mb-2 block text-sm font-semibold text-slate-700">Tipo de activo *</label>
                <select id="asset-category" required value={assetForm.CategoriaId} onChange={(event) => setAssetForm({ ...assetForm, CategoriaId: event.target.value })} className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                  {categorias.map((categoria) => <option key={categoria.Id} value={categoria.Id}>{categoria.Nombre}</option>)}
                </select>
              </div>
              <label className="text-sm font-semibold text-slate-700">Empresa *
                <select required value={assetForm.EmpresaId} onChange={(event) => setAssetForm({ ...assetForm, EmpresaId: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                  {empresas.map((company) => <option key={company.Id} value={company.Id}>{company.Nombre}</option>)}
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-700">Tipo de propiedad *
                <select required value={assetForm.TipoPropiedad} onChange={(event) => setAssetForm({ ...assetForm, TipoPropiedad: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                  <option value="propio">Propio</option>
                  <option value="arriendo">En arriendo</option>
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-700">Proveedor
                <select value={assetForm.ProveedorId} onChange={(event) => setAssetForm({ ...assetForm, ProveedorId: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                  <option value="">Sin proveedor</option>
                  {proveedores.map((provider) => <option key={provider.Id} value={provider.Id}>{provider.Nombre}</option>)}
                </select>
                {canManageAssets && (
                  <div className="mt-2">
                    <input maxLength={255} value={newSupplierName} onChange={(event) => setNewSupplierName(event.target.value)} placeholder="Agregar tienda o proveedor" className="w-full rounded-xl border border-slate-200 px-4 py-2.5 font-normal" />
                    <button type="button" onClick={handleCreateSupplier} disabled={addingSupplier || !newSupplierName.trim()} className="mt-2 text-xs font-semibold text-green-700 hover:text-green-800 disabled:opacity-50">{addingSupplier ? 'Agregando...' : '+ Agregar proveedor al listado'}</button>
                  </div>
                )}
              </label>
              <label className="text-sm font-semibold text-slate-700">Número de factura o contrato
                <input maxLength={150} value={assetForm.NumeroFacturaContrato} onChange={(event) => setAssetForm({ ...assetForm, NumeroFacturaContrato: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Fecha de compra o inicio del contrato
                <input type="date" value={assetForm.FechaInicio} onChange={(event) => setAssetForm({ ...assetForm, FechaInicio: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Fecha fin de garantía o contrato
                <input type="date" value={assetForm.FechaFin} onChange={(event) => setAssetForm({ ...assetForm, FechaFin: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Placa
                <input maxLength={100} value={assetForm.Placa} onChange={(event) => setAssetForm({ ...assetForm, Placa: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <div>
                <label htmlFor="asset-brand" className="mb-2 block text-sm font-semibold text-slate-700">Marca</label>
                <input id="asset-brand" value={assetForm.Marca} onChange={(event) => setAssetForm({ ...assetForm, Marca: event.target.value })} className="w-full rounded-xl border border-slate-200 px-4 py-3" />
              </div>
              <div>
                <label htmlFor="asset-model" className="mb-2 block text-sm font-semibold text-slate-700">Modelo</label>
                <input id="asset-model" value={assetForm.Modelo} onChange={(event) => setAssetForm({ ...assetForm, Modelo: event.target.value })} className="w-full rounded-xl border border-slate-200 px-4 py-3" />
              </div>
              <div>
                <label htmlFor="asset-serial" className="mb-2 block text-sm font-semibold text-slate-700">Número de serie</label>
                <input id="asset-serial" value={assetForm.NumeroSerie} onChange={(event) => setAssetForm({ ...assetForm, NumeroSerie: event.target.value })} className="w-full rounded-xl border border-slate-200 px-4 py-3" />
              </div>
              <div className="sm:col-span-2">
                <label htmlFor="asset-notes" className="mb-2 block text-sm font-semibold text-slate-700">Observaciones</label>
                <textarea id="asset-notes" rows={3} maxLength={10000} value={assetForm.Observaciones} onChange={(event) => setAssetForm({ ...assetForm, Observaciones: event.target.value })} className="w-full rounded-xl border border-slate-200 px-4 py-3" />
              </div>
            </div>
            <div className="flex justify-end gap-3">
              <button type="button" disabled={savingAsset} onClick={() => setShowCreateForm(false)} className="rounded-xl border border-slate-200 px-4 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
              <button type="submit" disabled={savingAsset || categorias.length === 0 || empresas.length === 0} className="rounded-xl bg-green-600 px-4 py-2.5 font-semibold text-white hover:bg-green-700 disabled:opacity-50">{savingAsset ? 'Guardando...' : editingAsset ? 'Guardar cambios' : 'Registrar activo'}</button>
            </div>
          </form>
        </div>
      )}

      {selectedMaintenance && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
          <form onSubmit={handleCompleteMaintenance} role="dialog" aria-modal="true" aria-labelledby="complete-maintenance-title" className="w-full max-w-lg space-y-5 rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="complete-maintenance-title" className="text-xl font-bold text-slate-800">Registrar mantenimiento</h2>
                <p className="mt-1 text-sm text-slate-600">{selectedMaintenance.TipoActivo} · {selectedMaintenance.CodigoInventario} · Semestre {selectedMaintenance.Semestre} de {selectedMaintenance.Anio}</p>
              </div>
              <button type="button" onClick={() => setSelectedMaintenance(null)} aria-label="Cerrar" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>
            {maintenanceError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{maintenanceError}</div>}
            <label className="block text-sm font-semibold text-slate-700">Realizado por
              <select value={maintenanceForm.Ejecutor} onChange={(event) => setMaintenanceForm({ ...maintenanceForm, Ejecutor: event.target.value, ProveedorId: '' })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                <option value="ti">Equipo de TI</option>
                <option value="proveedor">Proveedor</option>
              </select>
            </label>
            {maintenanceForm.Ejecutor === 'proveedor' && (
              <label className="block text-sm font-semibold text-slate-700">Proveedor
                <select required value={maintenanceForm.ProveedorId} onChange={(event) => setMaintenanceForm({ ...maintenanceForm, ProveedorId: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3">
                  <option value="">Selecciona un proveedor</option>
                  {proveedores.map((provider) => <option key={provider.Id} value={provider.Id}>{provider.Nombre}</option>)}
                </select>
              </label>
            )}
            {maintenanceForm.Ejecutor === 'proveedor' && proveedores.length === 0 && <p className="text-sm text-amber-700">Registra primero un proveedor para seleccionar esta opción.</p>}
            <div className="flex justify-end gap-3">
              <button type="button" disabled={maintenanceSaving} onClick={() => setSelectedMaintenance(null)} className="rounded-xl border border-slate-200 px-4 py-2.5 font-semibold text-slate-700">Cancelar</button>
              <button type="submit" disabled={maintenanceSaving || (maintenanceForm.Ejecutor === 'proveedor' && (proveedores.length === 0 || !maintenanceForm.ProveedorId))} className="rounded-xl bg-green-600 px-4 py-2.5 font-semibold text-white disabled:opacity-50">{maintenanceSaving ? 'Guardando...' : 'Confirmar realizado'}</button>
            </div>
          </form>
        </div>
      )}

      {showLicenseForm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
          <form onSubmit={handleCreateLicense} role="dialog" aria-modal="true" aria-labelledby="create-license-title" className="my-auto max-h-[92vh] w-full max-w-3xl space-y-5 overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="create-license-title" className="text-xl font-bold text-slate-800">{editingLicenseId ? 'Renovar / actualizar licencia' : 'Registrar licencia de software'}</h2>
                <p className="mt-1 text-sm text-slate-600">{editingLicenseId ? 'Actualiza sus datos de vigencia, proveedor, cantidad y activos asociados.' : 'Asocia la licencia a una empresa, un área y uno o más activos.'}</p>
              </div>
              <button type="button" disabled={licenseSaving} onClick={() => setShowLicenseForm(false)} aria-label="Cerrar" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>
            {licenseError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{licenseError}</div>}
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="text-sm font-semibold text-slate-700">Software *
                <input required maxLength={255} value={licenseForm.Nombre} onChange={(event) => setLicenseForm({ ...licenseForm, Nombre: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Tipo
                <select value={licenseForm.Tipo} onChange={(event) => setLicenseForm({ ...licenseForm, Tipo: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-normal">
                  <option value="comercial">Comercial</option>
                  <option value="opensource">Código abierto</option>
                  <option value="personalizado">Personalizado</option>
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-700">Empresa *
                <select required value={licenseForm.EmpresaId} onChange={(event) => setLicenseForm((current) => ({
                  ...current,
                  EmpresaId: event.target.value,
                  ActivosIds: current.ActivosIds.filter((id) => licenseOptions.Activos.some((asset) => String(asset.Id) === id && String(asset.EmpresaId) === event.target.value)),
                }))} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-normal">
                  {licenseOptions.Empresas.map((company) => <option key={company.Id} value={company.Id}>{company.Nombre}</option>)}
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-700">Área *
                <select required value={licenseForm.AreaId} onChange={(event) => setLicenseForm({ ...licenseForm, AreaId: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-normal">
                  {licenseOptions.Areas.map((area) => <option key={area.Id} value={area.Id}>{area.Nombre}</option>)}
                </select>
              </label>
              <label className="text-sm font-semibold text-slate-700">Proveedor de software
                <input maxLength={255} value={licenseForm.Proveedor} onChange={(event) => setLicenseForm({ ...licenseForm, Proveedor: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Número de licencia
                <input maxLength={100} value={licenseForm.NumeroLicencia} onChange={(event) => setLicenseForm({ ...licenseForm, NumeroLicencia: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Cantidad de licencias
                <input type="number" min="1" required value={licenseForm.Cantidad} onChange={(event) => setLicenseForm({ ...licenseForm, Cantidad: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <label className="text-sm font-semibold text-slate-700">Fecha de vencimiento
                <input type="date" value={licenseForm.FechaVencimiento} onChange={(event) => setLicenseForm({ ...licenseForm, FechaVencimiento: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
              <fieldset className="sm:col-span-2">
                <legend className="text-sm font-semibold text-slate-700">Activos asociados *</legend>
                <input
                  type="search"
                  value={licenseAssetSearch}
                  onChange={(event) => setLicenseAssetSearch(event.target.value)}
                  placeholder="Buscar por placa, tipo o código de inventario..."
                  aria-label="Buscar activos para asociar a la licencia"
                  className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                />
                <div className="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                  {licenseOptions.Activos
                    .filter((asset) => String(asset.EmpresaId) === licenseForm.EmpresaId)
                    .filter((asset) => `${asset.CodigoInventario} ${asset.TipoActivo} ${asset.Placa ?? ''}`.toLocaleLowerCase().includes(licenseAssetSearch.toLocaleLowerCase()))
                    .map((asset) => (
                    <label key={asset.Id} className="flex items-center gap-3 text-sm text-slate-700">
                      <input
                        type="checkbox"
                        checked={licenseForm.ActivosIds.includes(String(asset.Id))}
                        onChange={(event) => setLicenseForm((current) => ({
                          ...current,
                          ActivosIds: event.target.checked
                            ? [...current.ActivosIds, String(asset.Id)]
                            : current.ActivosIds.filter((id) => id !== String(asset.Id)),
                        }))}
                      />
                      {asset.CodigoInventario} · {asset.TipoActivo}{asset.Placa ? ` · ${asset.Placa}` : ''}
                    </label>
                  ))}
                  {licenseOptions.Activos
                    .filter((asset) => String(asset.EmpresaId) === licenseForm.EmpresaId)
                    .filter((asset) => `${asset.CodigoInventario} ${asset.TipoActivo} ${asset.Placa ?? ''}`.toLocaleLowerCase().includes(licenseAssetSearch.toLocaleLowerCase())).length === 0 && <p className="text-sm text-slate-500">No hay activos que coincidan con la búsqueda.</p>}
                  {licenseOptions.Activos.filter((asset) => String(asset.EmpresaId) === licenseForm.EmpresaId).length === 0 && <p className="text-sm text-slate-500">No hay activos disponibles para esta empresa.</p>}
                </div>
              </fieldset>
              <label className="text-sm font-semibold text-slate-700 sm:col-span-2">Observaciones
                <textarea rows={3} maxLength={10000} value={licenseForm.Notas} onChange={(event) => setLicenseForm({ ...licenseForm, Notas: event.target.value })} className="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal" />
              </label>
            </div>
            <div className="flex justify-end gap-3">
              <button type="button" disabled={licenseSaving} onClick={() => { setShowLicenseForm(false); setEditingLicenseId(null); }} className="rounded-xl border border-slate-200 px-4 py-2.5 font-semibold text-slate-700">Cancelar</button>
              <button type="submit" disabled={licenseSaving || !licenseOptions.Activos.some((asset) => String(asset.EmpresaId) === licenseForm.EmpresaId) || !licenseForm.ActivosIds.length} className="rounded-xl bg-green-600 px-4 py-2.5 font-semibold text-white disabled:opacity-50">{licenseSaving ? 'Guardando...' : editingLicenseId ? 'Guardar renovación' : 'Registrar licencia'}</button>
            </div>
          </form>
        </div>
      )}

      {selectedIncident && (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget && !incidentSaving) setSelectedIncident(null); }}>
          <div role="dialog" aria-modal="true" aria-labelledby="incident-detail-title" className="my-auto max-h-[90vh] w-full max-w-3xl space-y-5 overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
              <div>
                <div className="mb-1 flex items-center gap-2 text-sm font-mono font-semibold text-green-700"><Ticket size={16} />{selectedIncident.Ticket}</div>
                <h2 id="incident-detail-title" className="text-xl font-bold text-slate-800">{selectedIncident.Titulo}</h2>
                <p className="mt-1 text-sm text-slate-500">Creado {new Date(selectedIncident.FechaCreacion).toLocaleString()}</p>
              </div>
              <button type="button" onClick={() => setSelectedIncident(null)} aria-label="Cerrar detalle" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
            </div>

            {incidentError && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{incidentError}</div>}

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="rounded-xl bg-slate-50 p-4">
                <div className="text-xs font-semibold uppercase text-slate-500">Solicitante</div>
                <div className="mt-1 font-medium text-slate-800">{selectedIncident.SolicitanteNombre} {selectedIncident.SolicitanteApellidos}</div>
              </div>
              <div className="rounded-xl bg-slate-50 p-4">
                <div className="text-xs font-semibold uppercase text-slate-500">Ubicación</div>
                <div className="mt-1 font-medium text-slate-800">{selectedIncident.Ubicacion || 'No indicada'}</div>
              </div>
              <div className="sm:col-span-2">
                <div className="mb-1 text-sm font-semibold text-slate-700">Descripción del problema</div>
                <p className="whitespace-pre-wrap text-sm text-slate-600">{selectedIncident.Descripcion}</p>
              </div>
            </div>
            {selectedIncident.ArchivoAdjunto && (
              <div>
                <h3 className="mb-2 text-sm font-semibold text-slate-700">Evidencia adjunta</h3>
                <button type="button" onClick={downloadIncidentAttachment} className="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                  <Download size={15} /> {selectedIncident.ArchivoAdjuntoNombre || 'Descargar evidencia'}
                </button>
              </div>
            )}

            <div>
              <h3 className="mb-3 text-sm font-semibold text-slate-700">Historial y seguimiento</h3>
              {selectedIncident.Historial?.length ? (
                <ol className="space-y-3 border-l-2 border-slate-200 pl-4">
                  {selectedIncident.Historial.map((entry) => (
                    <li key={entry.Id} className="relative">
                      <span className="absolute -left-[22px] top-1 h-2.5 w-2.5 rounded-full bg-green-600" />
                      <div className="text-sm font-semibold text-slate-800">
                        {entry.UsuarioNombre} {entry.UsuarioApellidos}
                        {entry.EstadoNuevo && <span className="font-normal"> · {entry.EstadoAnterior?.replace('_', ' ')} → {entry.EstadoNuevo.replace('_', ' ')}</span>}
                        {entry.TipoCambio === 'asignacion' && <span className="font-normal"> · Actualizó la asignación</span>}
                      </div>
                      <div className="text-xs text-slate-500">{new Date(entry.FechaCreacion).toLocaleString()}</div>
                      {entry.Comentario && <p className="mt-1 whitespace-pre-wrap text-sm text-slate-600">{entry.Comentario}</p>}
                    </li>
                  ))}
                </ol>
              ) : (
                <p className="text-sm text-slate-500">Todavía no hay actualizaciones.</p>
              )}
            </div>

            {canManageIncidents && !isIncidentRequester && selectedIncident.Estado !== 'cerrada' ? (
              <form onSubmit={handleUpdateIncident} className="space-y-4 border-t border-slate-100 pt-4">
                <h3 className="text-sm font-semibold text-slate-700">Actualizar ticket</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="text-sm font-medium text-slate-700">
                    Estado
                    <select value={selectedIncident.Estado} onChange={(event) => setSelectedIncident({ ...selectedIncident, Estado: event.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                      <option value="nueva">Nueva</option>
                      <option value="pendiente">Solucionado</option>
                    </select>
                  </label>
                  <label className="text-sm font-medium text-slate-700">
                    Prioridad
                    <select value={selectedIncident.Prioridad} onChange={(event) => setSelectedIncident({ ...selectedIncident, Prioridad: event.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                      <option value="baja">Baja</option>
                      <option value="media">Media</option>
                      <option value="alta">Alta</option>
                      <option value="critica">Crítica</option>
                    </select>
                  </label>
                  <label className="text-sm font-medium text-slate-700 sm:col-span-2">
                    Comentario de seguimiento
                    <textarea
                      rows={3}
                      value={selectedIncident.NuevoComentario ?? ''}
                      onChange={(event) => setSelectedIncident({ ...selectedIncident, NuevoComentario: event.target.value })}
                      placeholder="Describe el trabajo realizado o el próximo paso"
                      className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5"
                    />
                  </label>
                </div>
                <div className="flex justify-end">
                  <button type="submit" disabled={incidentSaving} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                    <Send size={15} /> {incidentSaving ? 'Guardando...' : 'Guardar actualización'}
                  </button>
                </div>
              </form>
            ) : (
              <div className="flex flex-wrap gap-3 border-t border-slate-100 pt-4 text-sm">
                <span className={`rounded-full border px-3 py-1 font-semibold ${getIncidentStatusStyle(selectedIncident.Estado)}`}>{selectedIncident.Estado === 'pendiente' ? 'Pendiente por cerrar' : selectedIncident.Estado.replace('_', ' ')}</span>
                <span className={`rounded-full px-3 py-1 font-semibold ${getIncidentPriorityStyle(selectedIncident.Prioridad)}`}>{selectedIncident.Prioridad}</span>
                <span className="inline-flex items-center gap-1 text-slate-600"><User size={14} />{selectedIncident.AsignadoNombre ? `${selectedIncident.AsignadoNombre} ${selectedIncident.AsignadoApellidos}` : 'Sin asignar'}</span>
              </div>
            )}
            {isIncidentRequester && selectedIncident.Estado === 'pendiente' && (
              <form onSubmit={handleCloseIncident} className="space-y-4 border-t border-slate-100 pt-4">
                <div>
                  <h3 className="text-sm font-semibold text-slate-700">Encuesta de satisfacción</h3>
                  <p className="mt-1 text-sm text-slate-600">Cuéntanos cómo fue la atención. Al enviarla, la incidencia quedará cerrada.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-3">
                  {[
                    ['SatisfaccionTiempoSolucion', 'Tiempo de solución del requerimiento'],
                    ['SatisfaccionCalidadAtencion', 'Calidad de la atención recibida'],
                    ['SatisfaccionEficaciaSolucion', 'Eficacia de la solución recibida'],
                  ].map(([field, label]) => (
                    <label key={field} className="text-sm font-medium text-slate-700">
                      {label}
                      <select
                        required
                        value={selectedIncident[field] ?? ''}
                        onChange={(event) => setSelectedIncident({ ...selectedIncident, [field]: event.target.value })}
                        className="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"
                      >
                        <option value="">Selecciona una opción</option>
                        <option value="muy_satisfecho">Muy satisfecho</option>
                        <option value="satisfecho">Satisfecho</option>
                        <option value="insatisfecho">Insatisfecho</option>
                        <option value="muy_insatisfecho">Muy insatisfecho</option>
                      </select>
                    </label>
                  ))}
                </div>
                <label className="block text-sm font-medium text-slate-700">
                  Comentarios y/o sugerencias
                  <textarea
                    maxLength={5000}
                    rows={3}
                    value={selectedIncident.ComentarioSatisfaccion ?? ''}
                    onChange={(event) => setSelectedIncident({ ...selectedIncident, ComentarioSatisfaccion: event.target.value })}
                    placeholder="Escribe aquí tus comentarios o sugerencias (opcional)"
                    className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5"
                  />
                </label>
                <div className="flex justify-end">
                  <button type="submit" disabled={incidentSaving} className="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                    <CheckCircle size={15} /> {incidentSaving ? 'Cerrando...' : 'Enviar encuesta y cerrar'}
                  </button>
                </div>
              </form>
            )}
            {isIncidentRequester && selectedIncident.Estado === 'cerrada' && selectedIncident.SatisfaccionTiempoSolucion && (
              <section className="space-y-3 border-t border-slate-100 pt-4">
                <h3 className="text-sm font-semibold text-slate-700">Encuesta enviada</h3>
                {[
                  ['Tiempo de solución del requerimiento', selectedIncident.SatisfaccionTiempoSolucion],
                  ['Calidad de la atención recibida', selectedIncident.SatisfaccionCalidadAtencion],
                  ['Eficacia de la solución recibida', selectedIncident.SatisfaccionEficaciaSolucion],
                ].map(([label, value]) => (
                  <p key={label} className="text-sm text-slate-600">
                    <span className="font-medium text-slate-700">{label}:</span>{' '}
                    {satisfactionLabels[value] ?? value}
                  </p>
                ))}
                {selectedIncident.ComentarioSatisfaccion && (
                  <p className="whitespace-pre-wrap text-sm text-slate-600">
                    <span className="font-medium text-slate-700">Comentarios y/o sugerencias:</span>{' '}
                    {selectedIncident.ComentarioSatisfaccion}
                  </p>
                )}
              </section>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default GestionPage;