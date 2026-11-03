const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;');

import { companyLogoFile, companyLogoSrc } from '../../../utils/companyLogos';

const formatDate = (value = new Date()) => {
  const date = value instanceof Date ? value : new Date(`${String(value).slice(0, 10)}T12:00:00`);
  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
};

const logoHtml = (empresa) => {
  const file = companyLogoFile(empresa);
  return file
    ? `<img class="logo" src="${companyLogoSrc(file)}" alt="${escapeHtml(empresa)}" />`
    : `<strong>${escapeHtml(empresa)}</strong>`;
};

const openPrintWindow = (title, body, styles) => {
  const printWindow = window.open('', '_blank');
  if (!printWindow) return false;
  printWindow.document.open();
  printWindow.document.write(`<!doctype html>
    <html lang="es"><head><meta charset="utf-8" /><title>${escapeHtml(title)}</title>
    <style>${styles}</style></head>
    <body>${body}<script>window.onload=function(){window.focus();window.print();}</script></body></html>`);
  printWindow.document.close();
  return true;
};

const styles = `
  @page { size: letter; margin: 18mm; }
  * { box-sizing: border-box; }
  body { margin: 0; color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 11pt; }
  .header { border: 1px solid #222; display: grid; grid-template-columns: 30% 1fr 22%; min-height: 72px; }
  .logo-wrap, .title, .meta { display: flex; align-items: center; justify-content: center; text-align: center; padding: 8px; }
  .logo-wrap { border-right: 1px solid #222; }
  .title { flex-direction: column; font-weight: bold; }
  .meta { border-left: 1px solid #222; font-size: 8pt; }
  .logo { max-width: 100%; max-height: 54px; object-fit: contain; }
  h1 { margin: 18px 0 24px; text-align: center; font-size: 15pt; }
  .date { margin: 24px 0; }
  .fields { display: grid; grid-template-columns: 82px 1fr; gap: 7px 10px; }
  .label { font-weight: bold; }
  .intro { margin: 24px 0 12px; line-height: 1.5; }
  table { width: 100%; border-collapse: collapse; margin: 12px 0; }
  th, td { border: 1px solid #222; padding: 7px; text-align: left; }
  th { background: #f0f0f0; }
  .signature { margin-top: 55px; font-weight: bold; line-height: 1.4; }
  .signature span { display: block; font-weight: normal; }
  .receipt-intro { margin: 18px 0 10px; line-height: 2; }
  .note { border: 1px solid #222; background: #f0f0f0; padding: 9px; font-size: 8.5pt; line-height: 1.35; }
  .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 42px; }
  .line { border-top: 1px solid #222; margin-top: 42px; padding-top: 5px; }
  .vehicle { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }
  .vehicle div { border: 1px solid #222; min-height: 40px; padding: 7px; }
`;

export const printDotacionAuthorization = (request) => {
  const today = formatDate();
  const items = (request.Articulos ?? []).map((item) => `
    <tr><td>${escapeHtml(item.Cantidad)}</td><td>${escapeHtml(item.Referencia)}${item.Talla ? ` - Talla ${escapeHtml(item.Talla)}` : ''}</td></tr>
  `).join('');
  const body = `
    <div class="header">
      <div class="logo-wrap">${logoHtml(request.EmpresaNombre)}</div>
      <div class="title">GESTIÓN HUMANA<br />AUTORIZACIÓN ENTREGA DE DOTACIÓN</div>
      <div class="meta">HUM-FOR-004<br />Versión: 06<br />Fecha elaboración: 11/11/2020<br />Fecha aprobación: 19/11/2020</div>
    </div>
    <p class="date">Bogotá D.C., ${escapeHtml(today)}</p>
    <div class="fields">
      <div class="label">DE:</div><div>JEFE DPTO. GESTIÓN HUMANA</div>
      <div class="label">PARA:</div><div>${escapeHtml(`${request.CoordinadorNombres ?? ''} ${request.CoordinadorApellidos ?? ''}`.trim())}</div>
      <div class="label">ASUNTO:</div><div>AUTORIZACIÓN ENTREGA DE DOTACIÓN</div>
    </div>
    <p class="intro">Comedidamente me permito autorizar entrega de dotación, de acuerdo al cargo; según se menciona a continuación:</p>
    <div class="fields">
      <div class="label">Nombres:</div><div>${escapeHtml(`${request.SolicitanteNombres ?? ''} ${request.SolicitanteApellidos ?? ''}`.trim())}</div>
      <div class="label">Cédula:</div><div>${escapeHtml(request.SolicitanteCedula)}</div>
      <div class="label">Cargo:</div><div>${escapeHtml(request.SolicitanteCargo)}</div>
      <div class="label">C. Costo:</div><div></div>
    </div>
    <table><thead><tr><th style="width:25%">Cantidad</th><th>Referencia</th></tr></thead><tbody>${items}</tbody></table>
    <p class="intro">Cordialmente,</p>
    <div class="signature">${escapeHtml(request.JefeNombreCompleto || `${request.JefeNombres ?? ''} ${request.JefeApellidos ?? ''}`.trim())}<span>Jefe Gestión Humana</span></div>`;
  return openPrintWindow(`Autorización de dotación ${request.Id}`, body, styles);
};

export const printDotacionReceipt = (request) => {
  const now = request.FechaActualizacion
    ? new Date(String(request.FechaActualizacion).replace(' ', 'T'))
    : new Date();
  const day = now.toLocaleDateString('es-CO', { day: 'numeric' });
  const month = now.toLocaleDateString('es-CO', { month: 'long' });
  const year = now.getFullYear();
  const items = (request.Articulos ?? []).map((item, index) => `
    <tr><td>${index + 1}</td><td>${escapeHtml(item.Referencia)}</td>
      <td>${escapeHtml(item.Cantidad)}</td><td>${escapeHtml(item.Talla)}</td><td></td></tr>
  `).join('');
  const isDriver = String(request.SolicitanteCargo ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase() === 'conductor';
  const vehicleFields = isDriver
    ? '<div class="vehicle"><div>PLACA DEL VEHÍCULO<br /><br /></div><div>No. INTERNO<br /><br /></div></div>'
    : '';
  const body = `
    <div class="header">
      <div class="logo-wrap">${logoHtml(request.EmpresaNombre)}</div>
      <div class="title">ENTREGA DE DOTACIÓN</div>
    </div>
    <p class="receipt-intro">
      Yo <u>${escapeHtml(`${request.SolicitanteNombres ?? ''} ${request.SolicitanteApellidos ?? ''}`.trim())}</u>
      identificado(a) con cédula de ciudadanía No. <u>${escapeHtml(request.SolicitanteCedula)}</u><br />
      expedida en <u>${escapeHtml(request.CedulaExpedidaEn)}</u>, certifico que recibí de ${escapeHtml(request.EmpresaNombre)}
      la siguiente dotación correspondiente a <u>${escapeHtml(request.SolicitanteCargo)}</u>.<br />
      El día <u>${day}</u> del mes <u>${escapeHtml(month)}</u>. Año <u>${year}</u>
    </p>
    <table>
      <thead><tr><th>No.</th><th>Descripción</th><th>Cantidad</th><th>Talla</th><th>Observaciones</th></tr></thead>
      <tbody>${items}</tbody>
    </table>
    <div class="note">
      <strong>Nota para el personal</strong>
      <p>Se informa a todo el personal que la dotación entregada es de uso obligatorio durante el desempeño de sus funciones. Las prendas deben llevarse de manera decorosa, limpia y en buen estado.</p>
      <p>Es imperativo que el uniforme sea acompañado únicamente por un saco o chaqueta de color negro sin ningún distintivo visible. El calzado debe ser negro y estar bien lustrado. Es imprescindible evitar el uso de pantalones jean (de cualquier color) y tenis mientras se encuentre en servicio.</p>
      <p>Para el personal femenino, es necesario mantener el cabello limpio, bien peinado o recogido, utilizar maquillaje suave y evitar accesorios excesivamente llamativos.</p>
      <p>Para el personal masculino, el porte de la corbata es fundamental, y el uso de aretes, piercings u otros accesorios similares está prohibido.</p>
      <p>Se recuerda que estas directrices están contempladas en el Código de Trabajo y deben cumplirse rigurosamente para mantener una imagen profesional y el orden dentro de nuestras empresas.</p>
      <p>Agradecemos su compromiso y colaboración.</p>
    </div>
    <div class="signatures">
      <div class="line">FIRMA DE QUIEN RECIBE<br />${escapeHtml(`${request.SolicitanteNombres ?? ''} ${request.SolicitanteApellidos ?? ''}`.trim())}<br />Cargo: ${escapeHtml(request.SolicitanteCargo)}</div>
      <div class="line">QUIEN ENTREGA<br />${escapeHtml(`${request.CoordinadorNombres ?? ''} ${request.CoordinadorApellidos ?? ''}`.trim())}<br />Cargo: ${escapeHtml(request.CoordinadorCargo)}</div>
    </div>
    ${vehicleFields}`;
  return openPrintWindow(`Entrega de dotación ${request.Id}`, body, styles);
};
