import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom';
import { LoginPage, VerifyPage } from '../modules/auth';
import { lazy, Suspense } from 'react';
import { useAuth } from '../contexts/AuthContext';
import PermissionRoute from './PermissionRoute';
import MainLayout from '../components/MainLayout';
import { usesExternalGestion, GESTION_EXTERNAL_URL } from '../utils/gestionAccess';

const DashboardPage = lazy(() => import('../modules/dashboard/pages/DashboardPage'));
const IntranetHomePage = lazy(() => import('../modules/intranet/pages/IntranetHomePage').then((m) => ({ default: m.IntranetHomePage })));
const DirectoryPage = lazy(() => import('../modules/intranet/pages/DirectoryPage'));
const SecurityPage = lazy(() => import('../modules/security/pages/SecurityPage'));
const SolicitudesPage = lazy(() => import('../modules/solicitudes/pages/SolicitudesPage'));
const GestionPage = lazy(() => import('../modules/gestion/pages/GestionPage'));
const DotacionPage = lazy(() => import('../modules/dotacion/pages/DotacionPage'));
const HorarioPage = lazy(() => import('../modules/horario/pages/HorarioPage'));
const AdminUsersPage = lazy(() => import('../modules/admin/pages/AdminUsersPage'));

const PageFallback = () => (
  <div className="flex min-h-[40vh] items-center justify-center">
    <div className="h-10 w-10 animate-spin rounded-full border-b-2 border-orange-600"></div>
  </div>
);

const GestionGuard = ({ children }) => {
  const { user } = useAuth();
  if (usesExternalGestion(user)) {
    window.location.replace(GESTION_EXTERNAL_URL);
    return null;
  }
  return children;
};

const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

const PublicRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>
    );
  }

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
};

const ProtectedLayoutWrapper = () => {
  const { user } = useAuth();

  return (
    <ProtectedRoute>
      <MainLayout user={user}>
        <Suspense fallback={<PageFallback />}>
          <Outlet />
        </Suspense>
      </MainLayout>
    </ProtectedRoute>
  );
};

const router = createBrowserRouter([
  {
    path: '/login',
    element: (
      <PublicRoute>
        <LoginPage />
      </PublicRoute>
    ),
  },
  {
    path: '/auth/verify',
    element: <VerifyPage />,
  },
  {
    path: '/',
    element: <Navigate to="/login" replace />,
  },
  
  /* RUTAS PROTEGIDAS CON LAYOUT GLOBAL (Header + Sidebar) */
  {
    element: <ProtectedLayoutWrapper />,
    children: [
      {
        path: '/dashboard',
        element: (
          <PermissionRoute>
            <DashboardPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/intranet',
        element: (
          <PermissionRoute requiredPermission="intranet.ver">
            <IntranetHomePage />
          </PermissionRoute>
        ),
      },
      {
        path: '/intranet/directorio',
        element: (
          <PermissionRoute requiredPermission="intranet.ver">
            <DirectoryPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/intranet/:tab',
        element: (
          <PermissionRoute requiredPermission="intranet.ver">
            <IntranetHomePage />
          </PermissionRoute>
        ),
      },
      {
        path: '/security',
        element: (
          <PermissionRoute requiredPermission="seguridad.ver">
            <SecurityPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/solicitudes',
        element: (
          <PermissionRoute requiredPermission="solicitudes.ver">
            <SolicitudesPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/dotacion',
        element: (
          <PermissionRoute requiredPermission="solicitudes.ver">
            <DotacionPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/horario',
        element: (
          <PermissionRoute>
            <HorarioPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/administracion',
        element: (
          <PermissionRoute>
            <AdminUsersPage />
          </PermissionRoute>
        ),
      },
      {
        path: '/gestion',
        element: (
          <PermissionRoute requiredPermission="gestion.incidencias.ver">
            <GestionGuard>
              <GestionPage />
            </GestionGuard>
          </PermissionRoute>
        ),
      },
    ],
  },
]);

export default router;