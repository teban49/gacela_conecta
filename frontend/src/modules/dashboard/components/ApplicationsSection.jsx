import {
  Newspaper,
  ClipboardList,
  Ticket,
  Boxes,
  Shield,
  Laptop,
  Package,
  Clock
} from "lucide-react";
import { canViewHorario } from "../../../utils/roleAccess";
import ApplicationCard from "./ApplicationCard";
import { useAuth } from "../../../contexts/AuthContext";
import { GESTION_EXTERNAL_URL, usesExternalGestion } from "../../../utils/gestionAccess";

const ApplicationsSection = () => {
  const { user, hasPermission } = useAuth();

  const isSuperAdmin = user?.roles?.some((r) => {
    const code = typeof r === 'object' ? r.Codigo : r;
    const name = typeof r === 'object' ? r.Nombre : '';
    return code === 'admin' || code === 'superadmin' || name === 'Administrador';
  });

  const userPermissions = user?.permissions || [];

  const applications = [
    {
      title: "Intranet",
      description: "Noticias, comunicados, documentos y organigrama corporativo.",
      icon: Newspaper,
      color: "#1b6eb8",
      to: "/intranet",
      permission: "intranet.ver"
    },
    {
      title: "Solicitudes",
      description: "Vacaciones, licencias, permisos y certificaciones.",
      icon: ClipboardList,
      color: "#f58634",
      to: "/solicitudes",
      permission: "solicitudes.ver"
    },
    {
      title: "Dotación",
      description: "Solicitud, aprobación y entrega de dotación.",
      icon: Package,
      color: "#8b5cf6",
      to: "/dotacion",
      permission: "solicitudes.ver"
    },
    {
      title: "Horario",
      description: "Control de entradas, salidas y ausencias del personal.",
      icon: Clock,
      color: "#0ea5e9",
      to: "/horario",
      visible: canViewHorario(user)
    },
    {
      title: "Gestión Tecnológica",
      description: "Consulta y crea tickets de incidencias tecnológicas.",
      icon: Laptop,
      color: "#10b981",
      to: usesExternalGestion(user) ? GESTION_EXTERNAL_URL : "/gestion",
      external: usesExternalGestion(user),
      permission: ["gestion.ver", "gestion.incidencias.ver"]
    },
    {
      title: "Inventario",
      description: "Inventario tecnológico y control de activos.",
      icon: Boxes,
      color: "#10b981",
      to: "/inventario",
      permission: "inventario.ver",
      comingSoon: true
    },
    {
      title: "Seguridad",
      description: "Administración de usuarios, roles, permisos y aplicaciones.",
      icon: Shield,
      color: "#ec4899",
      to: "/security",
      permission: "seguridad.ver",
      comingSoon: true
    }
  ];

  // Inventario y Seguridad se ocultan hasta que se implementen.
  const visibleApplications = applications.filter(app => {
    if (app.comingSoon) return false;
    if (app.visible !== undefined) return app.visible;
    if (!app.permission) return true;
    if (isSuperAdmin) return true;
    const requiredPermissions = Array.isArray(app.permission) ? app.permission : [app.permission];
    return requiredPermissions.some((permission) => (
      hasPermission(permission) || userPermissions.includes(permission)
    ));
  });

  return (
    <section>
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-slate-800">
          🚀 Tus Aplicaciones
        </h2>
        <p className="text-gray-500 mt-1">
          Accede rápidamente a los módulos habilitados para tu perfil.
        </p>
      </div>

      <div className="grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
        {visibleApplications.map((application) => (
          <ApplicationCard key={application.title} {...application} />
        ))}
      </div>
    </section>
  );
};

export default ApplicationsSection;