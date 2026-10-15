import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom';
import { LoginPage, VerifyPage } from '../modules/auth';
import DashboardPage from '../modules/dashboard/pages/DashboardPage';
import { useAuth } from '../contexts/AuthContext';
import { IntranetHomePage } from '../modules/intranet/pages/IntranetHomePage';
import DirectoryPage from '../modules/intranet/pages/DirectoryPage';
import SecurityPage from '../modules/security/pages/SecurityPage';
import SolicitudesPage from '../modules/solicitudes/pages/SolicitudesPage';
import GestionPage from '../modules/gestion/pages/GestionPage';
import PermissionRoute from './PermissionRoute';
import MainLayout from '../components/MainLayout';

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
          <PermissionRoute requiredPermission="dashboard.ver">
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
        path: '/gestion',
        element: (
          <PermissionRoute requiredPermission="gestion.incidencias.ver">
            <GestionPage />
          </PermissionRoute>
        ),
      },
    ],
  },
]);

export default router;