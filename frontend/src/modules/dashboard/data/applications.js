import {
    Building2,
    Ticket,
    ClipboardList,
    Wallet,
    Users,
    FileText,
    Boxes
} from "lucide-react";

const applications = [
    {
        id: "intranet",
        title: "Intranet",
        description: "Noticias, comunicados y contenido corporativo.",
        icon: Building2,
        color: "bg-orange-500",
        route: "/intranet",
        enabled: true
    },
    {
        id: "incidencias",
        title: "Incidencias",
        description: "Reporte y seguimiento de solicitudes de soporte.",
        icon: Ticket,
        color: "bg-blue-600",
        route: "/incidencias",
        enabled: true
    },
    {
        id: "solicitudes",
        title: "Solicitudes",
        description: "Gestión de procesos y requerimientos internos.",
        icon: ClipboardList,
        color: "bg-emerald-600",
        route: "/solicitudes",
        enabled: true
    },
    {
        id: "reposicion",
        title: "Fondo de Reposición",
        description: "Administración del fondo de reposición.",
        icon: Wallet,
        color: "bg-purple-600",
        route: "/reposicion",
        enabled: true
    },

    // Próximos módulos

    {
        id: "talento",
        title: "Talento Humano",
        description: "Vacaciones, certificados y novedades.",
        icon: Users,
        color: "bg-pink-500",
        route: "",
        enabled: false
    },
    {
        id: "documental",
        title: "Gestión Documental",
        description: "Consulta y administración documental.",
        icon: FileText,
        color: "bg-cyan-600",
        route: "",
        enabled: false
    },
    {
        id: "inventario",
        title: "Inventario",
        description: "Consulta y gestión de activos tecnológicos.",
        icon: Boxes,
        color: "bg-amber-600",
        route: "",
        enabled: false
    }
];

export default applications;