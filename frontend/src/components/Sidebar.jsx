import React, { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Home, ShieldCheck, BookOpen, Phone, FileText, Headphones, Shield, ClipboardList, Package, LayoutDashboard, Laptop, Clock, UsersRound } from 'lucide-react';
import { canViewHorario } from '../utils/roleAccess';
import { useAuth } from '../contexts/AuthContext';
import { applicationsAPI } from '../services/applicationsAPI';
import { GESTION_EXTERNAL_URL, usesExternalGestion } from '../utils/gestionAccess';

// Módulos aún no implementados: se ocultan del menú, sin eliminar sus definiciones ni iconos
const HIDDEN_APP_CODES = ['incidencias', 'inventario', 'seguridad', 'configuracion', 'fondo_reposicion', 'formatos', 'documentacion'];
const HIDDEN_APP_ROUTES = ['/incidencias', '/inventario', '/seguridad', '/security', '/configuracion', '/fondo-reposicion', '/formatos', '/documentacion'];
export const Sidebar = () => {
  const location = useLocation();
  const { user, hasPermission } = useAuth();
  const [applications, setApplications] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const isSecurityAdmin = user?.roles?.some((r) => {
      const code = typeof r === 'object' ? r.Codigo : r;
      const name = typeof r === 'object' ? r.Nombre : '';
      return code === 'admin' || code === 'superadmin' || name === 'Administrador';
    });
    if (!isSecurityAdmin && !user?.permissions?.includes('seguridad.ver')) {
      setApplications([]);
      setLoading(false);
      return;
    }
    loadApplications();
  }, [user]);

  const loadApplications = async () => {
    try {
      const response = await applicationsAPI.getApplications();
      const apps = response.data?.data ?? [];
      setApplications(apps);
    } catch (error) {
      console.error('Error loading applications:', error);
      // Fallback to hardcoded menu if API fails
      setApplications([]);
    } finally {
      setLoading(false);
    }
  };

  const isSuperAdmin = user?.roles?.some((r) => {
    const code = typeof r === 'object' ? r.Codigo : r;
    const name = typeof r === 'object' ? r.Nombre : '';
    return code === 'admin' || code === 'superadmin' || name === 'Administrador';
  });

  // Convertir aplicaciones de BD a items de menú
  const menuItems = applications
    .filter(app => app.Estado && app.Visible)
    .filter(app => !HIDDEN_APP_CODES.includes(app.Codigo) && !HIDDEN_APP_ROUTES.includes(app.Ruta))
    .map(app => ({
      title: app.Nombre,
      href: app.Ruta || `/${app.Codigo}`,
      icon: getIconForApp(app.Icono),
      permission: app.Codigo === 'gestion' || app.Ruta === '/gestion'
        ? ['gestion.ver', 'gestion.incidencias.ver']
        : (app.Codigo === 'dashboard' || app.Ruta === '/dashboard' ? undefined : `${app.Codigo}.ver`)
    }));

  // Si no hay aplicaciones en BD, usar menú hardcoded como fallback
  const fallbackMenuItems = [
    { title: 'Home Principal', href: '/dashboard', icon: Home },
    { title: 'Intranet', href: '/intranet', icon: BookOpen, permission: 'intranet.ver' },
    { title: 'Solicitudes', href: '/solicitudes', icon: ClipboardList, permission: 'solicitudes.ver' },
    { title: 'Gestión Tecnológica', href: '/gestion', icon: Laptop, permission: ['gestion.ver', 'gestion.incidencias.ver'] },
  ];

  const configuredMenuItems = menuItems.length > 0 ? menuItems : fallbackMenuItems;
  const finalMenuItems = [
    ...configuredMenuItems,
    ...(!configuredMenuItems.some((item) => item.href === '/dotacion')
      ? [{ title: 'Dotación', href: '/dotacion', icon: Package, permission: 'solicitudes.ver' }]
      : []),
    ...(canViewHorario(user) ? [{ title: 'Horario', href: '/horario', icon: Clock }] : []),
    ...(isSuperAdmin ? [{ title: 'Administración', href: '/administracion', icon: UsersRound }] : []),
  ].map((item) => (
    item.href === '/gestion' && usesExternalGestion(user)
      ? { ...item, href: GESTION_EXTERNAL_URL, external: true }
      : item
  ));

  const visibleItems = finalMenuItems.filter((item) => {
    if (!item.permission) return true;
    if (isSuperAdmin) return true;
    return Array.isArray(item.permission)
      ? item.permission.some(hasPermission)
      : hasPermission(item.permission);
  });

  function getIconForApp(icono) {
    const iconMap = {
      'layout-dashboard': LayoutDashboard,
      'dashboard': LayoutDashboard,
      'book': BookOpen,
      'newspaper': BookOpen,
      'clipboard-list': ClipboardList,
      'file-text': FileText,
      'phone': Phone,
      'headphones': Headphones,
      'shield': Shield,
      'shield-check': ShieldCheck,
      'package': Package,
      'laptop': Laptop,
    };
    return iconMap[icono] || LayoutDashboard;
  }

  if (loading) {
    return (
      <aside className="w-64 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)] p-4 space-y-2">
        <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">
          Cargando menú...
        </div>
        <div className="flex items-center justify-center py-8">
          <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-orange-600"></div>
        </div>
      </aside>
    );
  }

  return (
    <aside className="w-64 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)] p-4 space-y-2">
      <div className="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">
        Navegación
      </div>
      {visibleItems.map((item) => {
        const Icon = item.icon;
        const isActive = !item.external && location.pathname.startsWith(item.href);
        const className = `flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all ${
          isActive
            ? 'bg-orange-50 text-orange-600 font-semibold'
            : 'text-gray-600 hover:bg-gray-100'
        }`;

        if (item.external) {
          return (
            <a key={item.title} href={item.href} target="_blank" rel="noopener noreferrer" className={className}>
              <Icon size={18} />
              <span>{item.title}</span>
            </a>
          );
        }

        return (
          <Link key={item.title} to={item.href} className={className}>
            <Icon size={18} />
            <span>{item.title}</span>
          </Link>
        );
      })}
    </aside>
  );
};

export default Sidebar;