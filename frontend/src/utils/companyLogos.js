const LOGO_BASE = '/media/solicitudes';
const cache = {};

export const companyLogoFile = (companyName) => {
  const name = String(companyName ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  if (name.includes('flota')) return 'logo_flota.jpeg';
  if (name.includes('rapido')) return 'logo_rapido.jpeg';
  if (name.includes('expreso') || name.includes('paz')) return 'logo_expreso.jpeg';
  return null;
};

const preload = (file) => {
  if (typeof window === 'undefined' || typeof fetch === 'undefined') return;
  fetch(`${LOGO_BASE}/${file}`)
    .then((response) => (response.ok ? response.blob() : null))
    .then((blob) => {
      if (!blob || !blob.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = () => { cache[file] = reader.result; };
      reader.readAsDataURL(blob);
    })
    .catch(() => {});
};

['logo_flota.jpeg', 'logo_rapido.jpeg', 'logo_expreso.jpeg'].forEach(preload);

// Los logos se incrustan como data URI para que se vean en la ventana de impresión.
export const companyLogoSrc = (file) => {
  if (!file) return '';
  if (cache[file]) return cache[file];
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  return `${origin}${LOGO_BASE}/${file}`;
};
