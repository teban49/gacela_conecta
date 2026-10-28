import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import { intranetAPI } from '../../../services/intranetAPI';

const ORG_AREAS = [
  { id: 'operaciones', label: 'Operaciones' },
  { id: 'sistemas', label: 'Sistemas' },
  { id: 'vinculaciones', label: 'Vinculaciones' },
  { id: 'juridica', label: 'Jurídica' },
  { id: 'tesoreria', label: 'Tesorería', onlyCompanyCodes: ['flota_magdalena'] },
  { id: 'contabilidad', label: 'Contabilidad' },
  {
    id: 'gestion-humana',
    label: 'Gestión Humana',
    children: [
      { id: 'rrhh', label: 'RRHH' },
      { id: 'calidad', label: 'Calidad' },
      { id: 'sst', label: 'SST' },
    ],
  },
];

const Connector = () => <div className="mx-auto h-6 w-px bg-slate-300" aria-hidden="true" />;

const PersonCard = ({ puesto, canEdit, onEdit, onDelete, tone = 'bg-white border-slate-300' }) => (
  <div className={`group relative min-w-[9rem] rounded-xl border px-4 py-3 text-center shadow-sm ${tone}`}>
    <p className="text-sm font-bold text-slate-900">{puesto.Nombre}</p>
    {puesto.Cargo && <p className="text-xs text-slate-500">{puesto.Cargo}</p>}
    {canEdit && (
      <div className="absolute -right-2 -top-2 hidden gap-1 group-hover:flex group-focus-within:flex">
        <button type="button" onClick={() => onEdit(puesto)} aria-label={`Editar ${puesto.Nombre}`} className="rounded-full border border-slate-200 bg-white p-1 text-slate-600 shadow hover:text-orange-600">
          <Pencil size={12} />
        </button>
        <button type="button" onClick={() => onDelete(puesto)} aria-label={`Eliminar ${puesto.Nombre}`} className="rounded-full border border-slate-200 bg-white p-1 text-slate-600 shadow hover:text-red-600">
          <Trash2 size={12} />
        </button>
      </div>
    )}
  </div>
);

const AddButton = ({ onClick, label }) => (
  <button type="button" onClick={onClick} className="inline-flex items-center gap-1 rounded-full border border-dashed border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-orange-400 hover:text-orange-600">
    <Plus size={12} />
    {label}
  </button>
);

export const OrganigramaSection = ({ canEdit }) => {
  const [empresas, setEmpresas] = useState([]);
  const [puestos, setPuestos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [companyId, setCompanyId] = useState('');
  const [dialog, setDialog] = useState(null);
  const [form, setForm] = useState({ Nombre: '', Cargo: '' });
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState('');

  useEffect(() => {
    if (!companyId && empresas.length) setCompanyId(String(empresas[0].Id));
  }, [empresas, companyId]);

  const load = useCallback(async () => {
    try {
      const response = await intranetAPI.getOrganigramaPuestos();
      setPuestos(response.data?.data?.puestos ?? []);
      setEmpresas(response.data?.data?.empresas ?? []);
      setError('');
    } catch (err) {
      setError(err.response?.data?.message || 'No fue posible cargar el organigrama.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const company = empresas.find((item) => String(item.Id) === companyId);
  const areas = ORG_AREAS.filter((area) => !area.onlyCompanyCodes || area.onlyCompanyCodes.includes(company?.Codigo));

  const rows = useMemo(() => {
    const inCompany = (puesto) => puesto.EmpresaId === null || String(puesto.EmpresaId) === companyId;
    return {
      nivel1: puestos.filter((p) => p.Nivel === 'nivel1' && inCompany(p)),
      nivel2: puestos.filter((p) => p.Nivel === 'nivel2' && inCompany(p)),
      gerencia: puestos.filter((p) => p.Nivel === 'gerencia' && String(p.EmpresaId) === companyId),
      area: (areaId) => puestos.filter((p) => p.Nivel === 'area' && p.Area === areaId && String(p.EmpresaId) === companyId),
    };
  }, [puestos, companyId]);

  const openCreate = (nivel, area = null, fila = null) => {
    setForm({ Nombre: '', Cargo: '' });
    setFormError('');
    setDialog({ mode: 'create', nivel, area, fila });
  };

  const openEdit = (puesto) => {
    setForm({ Nombre: puesto.Nombre, Cargo: puesto.Cargo ?? '' });
    setFormError('');
    setDialog({ mode: 'edit', puesto });
  };

  const handleDelete = async (puesto) => {
    if (!window.confirm(`¿Eliminar a ${puesto.Nombre} del organigrama?`)) return;
    try {
      await intranetAPI.deleteOrganigramaPuesto(puesto.Id);
      await load();
    } catch (err) {
      setError(err.response?.data?.message || 'No fue posible eliminar el puesto.');
    }
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setFormError('');
    try {
      if (dialog.mode === 'edit') {
        await intranetAPI.updateOrganigramaPuesto(dialog.puesto.Id, form);
      } else {
        const sharedLevel = dialog.nivel === 'nivel1' || dialog.nivel === 'nivel2';
        await intranetAPI.createOrganigramaPuesto({
          ...form,
          Nivel: dialog.nivel,
          Area: dialog.area,
          Fila: dialog.fila,
          EmpresaId: sharedLevel ? null : Number(companyId),
        });
      }
      setDialog(null);
      await load();
    } catch (err) {
      const errors = err.response?.data?.errors;
      setFormError(errors ? Object.values(errors).flat().join(' ') : (err.response?.data?.message || 'No fue posible guardar el puesto.'));
    } finally {
      setSaving(false);
    }
  };

  const renderRow = (items, nivel, tone) => (
    <div className="flex flex-wrap items-center justify-center gap-3">
      {items.map((puesto) => (
        <PersonCard key={puesto.Id} puesto={puesto} canEdit={canEdit} onEdit={openEdit} onDelete={handleDelete} tone={tone} />
      ))}
      {canEdit && <AddButton onClick={() => openCreate(nivel)} label="Agregar" />}
    </div>
  );

  return (
    <div className="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div>
        <h3 className="text-xl font-bold text-slate-900">Organigrama Corporativo</h3>
        <p className="text-sm text-slate-500">
          Selecciona una empresa para ver su estructura.{canEdit && ' Pasa el cursor sobre un puesto para editarlo o eliminarlo, o usa “Agregar” para sumar más.'}
        </p>
      </div>

      {empresas.length > 1 && <div className="flex flex-wrap gap-2" role="tablist" aria-label="Empresas">
        {empresas.map((item) => (
          <button
            key={item.Id}
            type="button"
            role="tab"
            aria-selected={String(item.Id) === companyId}
            onClick={() => setCompanyId(String(item.Id))}
            className={`rounded-full px-4 py-2 text-xs font-bold transition ${
              String(item.Id) === companyId ? 'bg-orange-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            }`}
          >
            {item.Nombre}
          </button>
        ))}
      </div>}

      {empresas.length === 1 && <p className="text-sm font-semibold text-slate-700">{empresas[0].Nombre}</p>}

      {error && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      {loading ? (
        <div className="p-8 text-center text-slate-500">Cargando organigrama...</div>
      ) : !company ? (
        <div className="p-8 text-center text-slate-500">No hay empresas disponibles.</div>
      ) : (
        <div className="overflow-x-auto pb-2">
          <div className="min-w-fit space-y-0">
            {renderRow(rows.nivel1, 'nivel1', 'border-orange-300 bg-orange-50')}
            <Connector />
            {renderRow(rows.nivel2, 'nivel2', 'border-amber-300 bg-amber-50')}
            <Connector />
            <p className="mb-2 text-center text-xs font-bold uppercase tracking-wider text-slate-400">Gerencia {company.Nombre}</p>
            {renderRow(rows.gerencia, 'gerencia', 'border-blue-300 bg-blue-50')}
            <Connector />
            <div className="border-t border-slate-300 pt-4">
              <div className="flex flex-wrap justify-center gap-4">
                {areas.map((area) => {
                  const renderPuestos = (areaId, label) => {
                    const items = rows.area(areaId);
                    const filas = [...new Set(items.map((p) => p.Fila ?? 1))].sort((a, b) => a - b);
                    const nextFila = (filas[filas.length - 1] ?? 0) + 1;
                    return (
                      <>
                        {filas.map((fila) => (
                          <div key={fila} className="flex flex-wrap items-stretch justify-center gap-2">
                            {items.filter((p) => (p.Fila ?? 1) === fila).map((puesto) => (
                              <PersonCard key={puesto.Id} puesto={puesto} canEdit={canEdit} onEdit={openEdit} onDelete={handleDelete} />
                            ))}
                            {canEdit && (
                              <button
                                type="button"
                                onClick={() => openCreate('area', areaId, fila)}
                                title="Agregar un cargo al lado (mismo nivel)"
                                aria-label={`Agregar cargo al lado en ${label}`}
                                className="self-center rounded-full border border-dashed border-slate-300 p-1.5 text-slate-500 transition hover:border-orange-400 hover:text-orange-600"
                              >
                                <Plus size={12} />
                              </button>
                            )}
                          </div>
                        ))}
                        {items.length === 0 && !canEdit && (
                          <p className="text-center text-xs text-slate-400">Sin puestos registrados</p>
                        )}
                        {canEdit && (
                          <div className="flex justify-center">
                            <AddButton onClick={() => openCreate('area', areaId, nextFila)} label={`Agregar debajo en ${label}`} />
                          </div>
                        )}
                      </>
                    );
                  };
                  return (
                    <section key={area.id} className={`${area.children ? 'min-w-[14rem]' : 'min-w-[11rem]'} w-fit max-w-full space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-3`} aria-label={`Área ${area.label}`}>
                      <h4 className="rounded-lg bg-slate-800 px-3 py-1.5 text-center text-xs font-bold uppercase tracking-wide text-white">{area.label}</h4>
                      {renderPuestos(area.id, area.label)}
                      {area.children && (
                        <div className="space-y-3 border-t border-slate-300 pt-3">
                          {area.children.map((child) => (
                            <div key={child.id} className="space-y-2 rounded-xl border border-slate-200 bg-white p-2" aria-label={`Área ${child.label}`}>
                              <h5 className="rounded-md bg-slate-600 px-2 py-1 text-center text-[11px] font-bold uppercase tracking-wide text-white">{child.label}</h5>
                              {renderPuestos(child.id, child.label)}
                            </div>
                          ))}
                        </div>
                      )}
                    </section>
                  );
                })}
              </div>
            </div>
          </div>
        </div>
      )}

      {dialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-label="Puesto del organigrama">
          <form onSubmit={handleSubmit} className="w-full max-w-md space-y-4 rounded-3xl bg-white p-6 shadow-xl">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-bold text-slate-900">{dialog.mode === 'edit' ? 'Editar puesto' : 'Agregar puesto'}</h3>
              <button type="button" onClick={() => setDialog(null)} aria-label="Cerrar" className="rounded-lg bg-slate-100 p-2 text-slate-600"><X size={16} /></button>
            </div>
            <div>
              <label htmlFor="org-nombre" className="mb-1 block text-xs font-semibold text-slate-600">Nombre</label>
              <input id="org-nombre" required maxLength={150} value={form.Nombre} onChange={(e) => setForm({ ...form, Nombre: e.target.value })} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
            </div>
            <div>
              <label htmlFor="org-cargo" className="mb-1 block text-xs font-semibold text-slate-600">Cargo (opcional)</label>
              <input id="org-cargo" maxLength={150} value={form.Cargo} onChange={(e) => setForm({ ...form, Cargo: e.target.value })} className="w-full rounded-xl border border-slate-300 p-2.5 text-sm" />
            </div>
            {formError && <p role="alert" className="text-sm text-red-600">{formError}</p>}
            <button type="submit" disabled={saving} className="w-full rounded-xl bg-slate-900 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
              {saving ? 'Guardando...' : 'Guardar'}
            </button>
          </form>
        </div>
      )}
    </div>
  );
};

export default OrganigramaSection;
