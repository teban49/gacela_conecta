import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom';
import { LoginPage, VerifyPage } from '../modules/auth';
import DashboardPage from '../modules/dashboard/pages/DashboardPage';
import { useAuth } from '../contexts/AuthContext';
import { IntranetHomePage } from '../modules/intranet/pages/IntranetHomePage';
import DirectoryPage from '../modules/intranet/pages/DirectoryPage';
import SecurityPage from '../modules/security/pages/SecurityPage';
import SolicitudesPage from '../modules/solicitudes/pages/SolicitudesPage';
import GestionPage from '../modules/gestion/pages/GestionPage';
import DotacionPage from '../modules/dotacion/pages/DotacionPage';
import HorarioPage from '../modules/horario/pages/HorarioPage';
import PermissionRoute from './PermissionRoute';
import MainLayout from '../components/MainLayout';
import { usesExternalGestion, GESTION_EXTERNAL_URL } from '../utils/gestionAccess';

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
        <Outlet />
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