import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Bell, CheckCheck } from 'lucide-react';
import api from '../services/api';

const POLL_MS = 60000;

const formatWhen = (value) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
};

const NotificationBell = () => {
  const navigate = useNavigate();
  const containerRef = useRef(null);
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState([]);
  const [unread, setUnread] = useState(0);

  const load = useCallback(async () => {
    try {
      const response = await api.get('/notificaciones');
      const data = response.data?.data ?? {};
      setItems(data.Items ?? []);
      setUnread(Number(data.NoLeidas ?? 0));
    } catch {
      // El centro de notificaciones no debe interrumpir la navegación.
    }
  }, []);

  useEffect(() => {
    load();
    const timer = setInterval(load, POLL_MS);
    return () => clearInterval(timer);
  }, [load]);

  useEffect(() => {
    if (!open) return undefined;
    const onClick = (event) => {
      if (containerRef.current && !containerRef.current.contains(event.target)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, [open]);

  const handleOpen = () => {
    setOpen((current) => !current);
    if (!open) load();
  };

  const handleClick = async (item) => {
    setOpen(false);
    if (item.Persistente && item.Id && !item.Leida) {
      try {
        await api.post(`/notificaciones/${item.Id}/leer`);
      } catch {
        // Se navega aunque no se pueda marcar como leída.
      }
    }
    if (item.Enlace) navigate(item.Enlace);
    load();
  };

  const markAll = async () => {
    try {
      await api.post('/notificaciones/leer-todas');
    } finally {
      load();
    }
  };

  return (
    <div className="relative" ref={containerRef}>
      <button
        type="button"
        onClick={handleOpen}
        aria-label="Notificaciones"
        className="relative rounded-xl border border-gray-200 p-2.5 text-gray-600 hover:bg-gray-50"
      >
        <Bell size={20} />
        {unread > 0 && (
          <span className="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-red-600 px-1 text-center text-[11px] font-bold leading-[18px] text-white">
            {unread > 99 ? '99+' : unread}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 z-[60] mt-2 w-80 max-w-[90vw] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl sm:w-96">
          <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <span className="font-semibold text-gray-900">Notificaciones</span>
            <button
              type="button"
              onClick={markAll}
              className="inline-flex items-center gap-1 text-xs font-semibold text-orange-600 hover:text-orange-700"
            >
              <CheckCheck size={14} /> Marcar leídas
            </button>
          </div>
          <div className="max-h-96 overflow-y-auto">
            {items.length === 0 ? (
              <p className="px-4 py-8 text-center text-sm text-gray-500">No tienes novedades pendientes.</p>
            ) : items.map((item) => (
              <button
                key={item.Clave}
                type="button"
                onClick={() => handleClick(item)}
                className={`block w-full border-b border-gray-50 px-4 py-3 text-left hover:bg-gray-50 ${item.Leida ? '' : 'bg-orange-50/60'}`}
              >
                <div className="flex items-start gap-2">
                  {!item.Leida && <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-orange-500" />}
                  <div className="min-w-0">
                    <div className="text-sm font-semibold text-gray-900">{item.Titulo}</div>
                    {item.Mensaje && <div className="mt-0.5 text-xs text-gray-600">{item.Mensaje}</div>}
                    <div className="mt-1 text-[11px] text-gray-400">{formatWhen(item.Fecha)}</div>
                  </div>
                </div>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
