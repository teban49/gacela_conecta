import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export const PermissionRoute = ({ children, requiredPermission }) => {
  const { user, isAuthenticated, loading, hasPermission } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (requiredPermission) {
    const isSuperAdmin = user?.roles?.some((r) => {
      const code = typeof r === 'object' ? r.Codigo : r;
      const name = typeof r === 'object' ? r.Nombre : '';
      return code === 'admin' || code === 'superadmin' || name === 'Administrador';
    });

    const isAllowed = isSuperAdmin || hasPermission(requiredPermission);

    if (!isAllowed) {
      return (
        <div className="p-8 max-w-2xl mx-auto mt-12 bg-white border border-red-200 rounded-2xl shadow-sm text-center">
          <div className="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-2xl">
            🔒
          </div>
          <h2 className="text-2xl font-bold text-slate-800 mb-2">Acceso Denegado</h2>
          <p className="text-gray-600 mb-6">
            No tienes los permisos necesarios ({requiredPermission}) para acceder a este módulo. Contacta al administrador del sistema.
          </p>
          <a
            href="/dashboard"
            className="inline-flex items-center justify-center px-6 py-2.5 bg-orange-600 text-white font-medium rounded-xl hover:bg-orange-700 transition-colors"
          >
            Volver al Dashboard
          </a>
        </div>
      );
    }
  }

  return children;
};

export default PermissionRoute;
