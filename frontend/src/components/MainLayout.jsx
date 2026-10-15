// src/components/MainLayout.jsx
import React from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import DashboardHeader from '../modules/dashboard/components/DashboardHeader'; // Importa tu header actual
import PendingAssetAssignments from '../modules/gestion/components/PendingAssetAssignments';
import Sidebar from './Sidebar';

export const MainLayout = () => {
  const location = useLocation();

  // Verifica si la ruta actual es el Dashboard/Home principal
  const isHomePage = location.pathname === '/dashboard' || location.pathname === '/';

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col">
      {/* Header reutilizado globalmente */}
      <DashboardHeader />

      <div className="flex flex-1">
        {/* Sidebar: Se renderiza en todas las vistas excepto el Home */}
        {!isHomePage && <Sidebar />}

        <main className={`flex-1 ${!isHomePage ? 'p-6 max-w-full' : ''}`}>
          <PendingAssetAssignments />
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default MainLayout;