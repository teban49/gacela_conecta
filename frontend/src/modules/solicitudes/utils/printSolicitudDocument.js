const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

const formatDate = (value) => {
  if (!value) return '—';
  return new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('es-CO');
};

const formatDateTime = (value) => (value ? new Date(String(value).replace(' ', 'T')).toLocaleString('es-CO') : '—');

const STAGES = { 1: 'Jefe directo', 2: 'Recursos Humanos', 3: 'Gerencia', 4: 'Nómina' };

// Imágenes en frontend/public/media/solicitudes/; si no existen, se ocultan.
const IMAGE_BASE = '/media/solicitudes';
const STAGE_SIGNATURE_FILES = { 1: 'firma-jefe.png', 2: 'firma-rrhh.png', 3: 'firma-gerencia.png' };
const optionalImage = (file, className) => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  return `<img class="${className}" src="${origin}${IMAGE_BASE}/${file}" alt="" onerror="this.style.display='none'" />`;
};

const companyLogoFile = (companyName) => {
  const name = String(companyName ?? '').toLowerCase();
  if (name.includes('flota')) return 'logo_flota.jpeg';
  if (name.includes('rapido') || name.includes('rápido')) return 'logo_rapido.jpeg';
  if (name.includes('expreso') || name.includes('paz')) return 'logo_expreso.jpeg';
  return null;
};

export const buildSolicitudDocumentHtml = (request) => {
  const code = String(request.TipoCodigo ?? '').toLowerCase();
  if (code === 'vacaciones') return buildVacationFormHtml(request);
  if (code === 'permiso' || code === 'licencia') return buildPermissionFormHtml(request);
  return buildGenericDocumentHtml(request);
};

const FORM_STYLES = `
  @page { size: letter; margin: 14mm; }
  body { font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 11px; margin: 0; }
  table { width: 100%; border-collapse: collapse; }
  td, th { border: 1px solid #000; padding: 2px 6px; text-align: left; vertical-align: middle; height: 16px; }
  th { background: #f2f2f2; font-weight: bold; }
  .head td { text-align: center; font-weight: bold; padding: 3px; }
  .head .logo-cell { width: 34%; text-align: center; font-weight: normal; }
  .head .meta td { font-weight: normal; }
  .logo { max-height: 46px; max-width: 100%; }
  .section { text-align: center; background: #f2f2f2; font-weight: bold; }
  .sigs { display: flex; gap: 20px; margin-top: 18px; }
  .sig { flex: 1; text-align: center; font-size: 10px; font-weight: bold; }
  .sig .line { border-top: 1px solid #000; margin-bottom: 4px; }
  .sig .who { font-weight: normal; min-height: 12px; }
  .cut { border-top: 1px dashed #000; margin: 14px 0; }
  .mark { text-align: center; font-weight: bold; font-size: 14px; width: 12%; }
  .record { margin-top: 22px; font-size: 9px; color: #333; }
  .sign-img { display: block; max-height: 36px; margin: 0 auto 2px; }
`;

const NUMBER_WORDS = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez',
  'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte'];
const TENS = { 30: 'treinta', 40: 'cuarenta', 50: 'cincuenta', 60: 'sesenta', 70: 'setenta', 80: 'ochenta', 90: 'noventa' };

const numberToWords = (n) => {
  if (n <= 20) return NUMBER_WORDS[n];
  if (n < 30) return `veinti${NUMBER_WORDS[n - 20]}`.replace('veintiuno', 'veintiún');
  if (n < 100) return n % 10 === 0 ? TENS[n] : `${TENS[n - (n % 10)]} y ${NUMBER_WORDS[n % 10]}`;
  return String(n);
};

const daysBetween = (start, end) => {
  if (!start || !end) return null;
  const a = new Date(`${String(start).slice(0, 10)}T12:00:00`);
  const b = new Date(`${String(end).slice(0, 10)}T12:00:00`);
  const days = Math.round((b - a) / 86400000) + 1;
  return Number.isFinite(days) && days > 0 ? days : null;
};

const documentContext = (request) => {
  const history = request.Historial ?? [];
  const approvalByLevel = (level) => history.find((item) => item.Accion === 'Aprobada' && Number(item.Nivel) === level);
  const personName = (item) => (item ? `${item.Nombres ?? ''} ${item.Apellidos ?? ''}`.trim() : '');
  const logoFile = companyLogoFile(request.EmpresaNombre);
  const approvalRecord = history
    .filter((item) => item.Accion === 'Aprobada')
    .map((item) => `${STAGES[item.Nivel] ?? `Etapa ${item.Nivel}`}: ${personName(item)} (${formatDateTime(item.Fecha)})`)
    .join(' · ');
  return {
    solicitante: `${request.SolicitanteNombres ?? ''} ${request.SolicitanteApellidos ?? ''}`.trim(),
    approvalByLevel,
    personName,
    logo: logoFile ? optionalImage(logoFile, 'logo') : escapeHtml(request.EmpresaNombre ?? ''),
    approvalRecord: `Solicitud No. ${request.Id} · Aprobación registrada en Gacela Conecta. ${approvalRecord}`,
  };
};

const formHeader = ({ logo }, title, code, versionText, dateText) => `
  <table class="head">
    <tr>
      <td class="logo-cell" rowspan="4">${logo}</td>
      <td>Sistema de Gestión de Calidad</td>
    </tr>
    <tr><td>Gestión Humana</td></tr>
    <tr><td>${title}</td></tr>
    <tr><td style="padding:0"><table class="meta" style="border:0"><tr>
      <td style="border:0;border-right:1px solid #000;width:30%">${code}</td>
      <td style="border:0;border-right:1px solid #000;width:25%">${versionText}</td>
      <td style="border:0">${dateText}</td>
    </tr></table></td></tr>
  </table>`;

const wrapForm = (request, body, ctx) => `<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8" />
<title>Solicitud ${escapeHtml(request.Id)} - ${escapeHtml(ctx.solicitante)}</title>
<style>${FORM_STYLES}</style></head>
<body>${body}
<script>window.onload = function () { window.focus(); window.print(); };</script>
</body></html>`;

const signatureBlock = (label, name, level, ctx, request) => {
  const approval = level ? ctx.approvalByLevel(level) : null;
  const file = level ? STAGE_SIGNATURE_FILES[level] : null;
  return `<div class="sig">
    <div class="who" style="min-height:34px;position:relative">${file && approval ? optionalImage(file, 'sign-img') : ''}${escapeHtml(name || (approval ? ctx.personName(approval) : ''))}</div>
    <div class="line"></div>${label}</div>`;
};

const buildPermissionFormHtml = (request) => {
  const ctx = documentContext(request);
  const days = daysBetween(request.FechaInicio, request.FechaFin);
  const inLetters = days ? `${numberToWords(days)} (${days}) ${days === 1 ? 'día' : 'días'}` : '';
  const reason = [request.Asunto, request.Descripcion].filter(Boolean).join(' - ');
  const copy = () => `
    ${formHeader(ctx, 'Solicitud de Permiso Administrativo', 'HUMANA-FOR-010', 'Versión. 07', 'Fecha elaboración. 27/04/2026')}
    <table style="margin-top:6px">
      <tr><th style="width:18%">Fecha:</th><td colspan="3">${escapeHtml(formatDate(request.FechaCreacion))}</td></tr>
      <tr><th>Nombres y Apellidos:</th><td colspan="3">${escapeHtml(ctx.solicitante)}</td></tr>
      <tr><th>Cargo:</th><td colspan="3">${escapeHtml(request.SolicitanteCargo ?? '')}</td></tr>
      <tr><th>Motivo del permiso:</th><td colspan="3">${escapeHtml(reason)}</td></tr>
      <tr><th>Tiempo solicitado en letras:</th><td colspan="3">${escapeHtml(inLetters)}</td></tr>
      <tr><th>Día de iniciación:</th><td>${escapeHtml(formatDate(request.FechaInicio))}</td><th style="width:12%">Hora:</th><td></td></tr>
      <tr><th>Día de presentación:</th><td></td><th>Hora:</th><td></td></tr>
      <tr><th>Tiempo que se repondrá:</th><td colspan="3"></td></tr>
      <tr><td colspan="4" class="section">CONCEPTO</td></tr>
      <tr><th style="text-align:center">Aprobado</th><td class="mark">X</td><th style="text-align:center">Denegado</th><td class="mark"></td></tr>
    </table>
    <div class="sigs">
      ${signatureBlock('Firma del Trabajador', ctx.solicitante, null, ctx, request)}
      ${signatureBlock('Vo.Bo. Jefe Inmediato', '', 1, ctx, request)}
      ${signatureBlock('Vo.Bo. Jefe Gestión Humana', '', 2, ctx, request)}
    </div>`;
  return wrapForm(request, `${copy()}<div class="cut"></div>${copy()}<div class="record">${escapeHtml(ctx.approvalRecord)}</div>`, ctx);
};

const normalizeText = (value) => String(value ?? '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .trim();

const buildVacationFormHtml = (request) => {
  const ctx = documentContext(request);
  const isDriver = normalizeText(request.SolicitanteCargo) === 'conductor';
  const cashDays = Number(request.DiasDinero ?? 0);
  const enjoyDays = Number(request.DiasDisfrute ?? 0);
  const hasSplit = request.DiasDinero != null || request.DiasDisfrute != null;
  const days = hasSplit && cashDays + enjoyDays > 0
    ? cashDays + enjoyDays
    : daysBetween(request.FechaInicio, request.FechaFin);
  const cedula = request.SolicitanteCedula ?? '';
  const vehicleCell = isDriver
    ? '<th>N° interno del vehículo</th><td colspan="2"></td>'
    : '';
  const cargoValueSpan = isDriver ? 2 : 5;
  const vehicleText = isDriver
    ? ` propietario (&nbsp;&nbsp;&nbsp;&nbsp;)/administrador (&nbsp;&nbsp;&nbsp;&nbsp;) del vehículo con
      número interno ________________`
    : '';
  const body = `
    ${formHeader(ctx, 'Solicitud de Vacaciones', 'HUMANA-FOR-018', 'Versión. 07', 'Fecha de Vigencia. 05/09/2025')}
    <table style="margin-top:10px">
      <tr><th style="width:22%">Fecha de solicitud:</th><td colspan="5">${escapeHtml(formatDate(request.FechaCreacion))}</td></tr>
      <tr><th>Nombres y Apellidos</th><td colspan="5">${escapeHtml(ctx.solicitante)}</td></tr>
      <tr><th>Cédula de ciudadanía</th><td colspan="5">${escapeHtml(cedula)}</td></tr>
      <tr><th>Correo electrónico</th><td colspan="2">${escapeHtml(request.SolicitanteEmail ?? '')}</td><th>Número de celular</th><td colspan="2">${escapeHtml(request.SolicitanteTelefono ?? '')}</td></tr>
      <tr><th>Cargo</th><td colspan="${cargoValueSpan}">${escapeHtml(request.SolicitanteCargo ?? '')}</td>${vehicleCell}</tr>
      <tr><th>Periodo solicitado</th><td></td><th>Fecha Ingreso</th><td>${escapeHtml(request.FechaReintegro ? formatDate(request.FechaReintegro) : '')}</td><th>Días solicitados</th><td>${days ?? ''}</td></tr>
      <tr><th>En Dinero</th><td>${hasSplit ? cashDays : ''}</td><th>En Disfrute</th><td>${hasSplit ? enjoyDays : ''}</td><th>Día de inicio</th><td>${escapeHtml(formatDate(request.FechaInicio))}</td></tr>
      <tr><th>Día de Termino</th><td colspan="5">${escapeHtml(formatDate(request.FechaFin))}</td></tr>
      <tr><th>Período pendiente</th><td colspan="5">${escapeHtml(request.PeriodoPendiente ?? '')}</td></tr>
    </table>
    <p style="margin:34px 0 0;line-height:2;text-align:justify">
      Yo <u>${escapeHtml(ctx.solicitante) || '&nbsp;'.repeat(40)}</u>, identificado con número de cédula
      <u>${escapeHtml(cedula) || '&nbsp;'.repeat(30)}</u>${vehicleText} autorizo la presente solicitud de vacaciones.
    </p>
    <div class="sigs" style="margin-top:80px">
      ${signatureBlock('Firma Trabajador', ctx.solicitante, null, ctx, request)}
      ${isDriver ? signatureBlock('Vo.Bo. Propietario y/o Administrador', '', null, ctx, request) : ''}
      ${signatureBlock('Vo. Bo. Jefe inmediato', '', 1, ctx, request)}
      ${signatureBlock('Vo.Bo. Gerente', '', 3, ctx, request)}
    </div>
    <div class="record">${escapeHtml(ctx.approvalRecord)}</div>`;
  return wrapForm(request, body, ctx);
};

const buildGenericDocumentHtml = (request) => {
  const history = request.Historial ?? [];
  const approvals = history.filter((item) => item.Accion === 'Aprobada');
  const rows = history.map((item) => `
    <tr>
      <td>${escapeHtml(STAGES[item.Nivel] ?? `Etapa ${item.Nivel}`)}</td>
      <td>${escapeHtml(`${item.Nombres ?? ''} ${item.Apellidos ?? ''}`.trim())}</td>
      <td>${escapeHtml(item.Accion)}</td>
      <td>${escapeHtml(formatDateTime(item.Fecha))}</td>
      <td>${escapeHtml(item.Comentario || '—')}</td>
    </tr>`).join('');
  const solicitante = `${request.SolicitanteNombres ?? ''} ${request.SolicitanteApellidos ?? ''}`.trim();
  const signatures = approvals.map((item) => `
    <div class="signature">
      ${STAGE_SIGNATURE_FILES[item.Nivel] ? optionalImage(STAGE_SIGNATURE_FILES[item.Nivel], 'sign-img') : ''}
      <div class="line"></div>
      <strong>${escapeHtml(STAGES[item.Nivel] ?? `Etapa ${item.Nivel}`)}</strong><br />
      ${escapeHtml(`${item.Nombres ?? ''} ${item.Apellidos ?? ''}`.trim())}
    </div>`).join('');

  return `<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8" />
<title>Solicitud ${escapeHtml(request.Id)} - ${escapeHtml(solicitante)}</title>
<style>
  body { font-family: Arial, sans-serif; color: #1e293b; margin: 32px; font-size: 13px; }
  h1 { font-size: 20px; margin: 0; } h2 { font-size: 14px; margin: 24px 0 8px; border-bottom: 2px solid #ea580c; padding-bottom: 4px; }
  header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ea580c; padding-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; } td, th { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
  th { background: #f1f5f9; width: 28%; } table.history th { width: auto; }
  .description { white-space: pre-wrap; border: 1px solid #cbd5e1; padding: 8px; min-height: 60px; }
  .signatures { display: flex; gap: 24px; margin-top: 64px; } .signature { flex: 1; text-align: center; }
  .line { border-top: 1px solid #0f172a; margin-bottom: 6px; }
  .logo { max-height: 72px; max-width: 160px; margin-right: 16px; } .sign-img { display: block; max-height: 56px; margin: -60px auto 4px; }
  .stamp { color: #047857; font-weight: bold; border: 2px solid #047857; padding: 4px 10px; border-radius: 6px; }
</style></head>
<body>
  <header>
    <div style="display:flex;align-items:center">${companyLogoFile(request.EmpresaNombre) ? optionalImage(companyLogoFile(request.EmpresaNombre), 'logo') : ''}<div><h1>${escapeHtml(request.EmpresaNombre || 'Grupo Gacela')} - Registro de solicitud laboral</h1><div>Solicitud No. ${escapeHtml(request.Id)}</div></div></div>
    <span class="stamp">APROBADA</span>
  </header>
  <h2>Datos de la solicitud</h2>
  <table>
    <tr><th>Solicitante</th><td>${escapeHtml(solicitante)}</td></tr>
    <tr><th>Tipo de solicitud</th><td>${escapeHtml(request.TipoNombre)}</td></tr>
    <tr><th>Asunto</th><td>${escapeHtml(request.Asunto)}</td></tr>
    <tr><th>Fechas solicitadas</th><td>${escapeHtml(formatDate(request.FechaInicio))} a ${escapeHtml(formatDate(request.FechaFin))}</td></tr>
    <tr><th>Fecha de radicación</th><td>${escapeHtml(formatDateTime(request.FechaCreacion))}</td></tr>
    <tr><th>Fecha de aprobación final</th><td>${escapeHtml(formatDateTime(request.FechaActualizacion))}</td></tr>
  </table>
  <h2>Descripción</h2>
  <div class="description">${escapeHtml(request.Descripcion)}</div>
  <h2>Trazabilidad de aprobaciones</h2>
  <table class="history">
    <tr><th>Etapa</th><th>Responsable</th><th>Decisión</th><th>Fecha</th><th>Comentario</th></tr>
    ${rows}
  </table>
  <div class="signatures">${signatures}
    <div class="signature"><div class="line"></div><strong>Solicitante</strong><br />${escapeHtml(solicitante)}</div>
  </div>
  <script>window.onload = function () { window.focus(); window.print(); };</script>
</body></html>`;
};

export const printSolicitudDocument = (request) => {
  const printWindow = window.open('', '_blank');
  if (!printWindow) return false;
  printWindow.document.open();
  printWindow.document.write(buildSolicitudDocumentHtml(request));
  printWindow.document.close();
  return true;
};
