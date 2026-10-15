import React from "react";
import {
  Mail,
  Users,
  Phone,
  CalendarDays,
  FolderOpen,
  ShieldCheck,
  ArrowRight
} from "lucide-react";
import { intranetImages } from '../../../utils/images';

// Arreglo de ítems interno del componente
const quickLinks = [
  {
    title: "Calidad - SG",
    description: "Accede a tu correo institucional.",
    icon: intranetImages.Icono_Calidad,
    color: "bg-blue-100 text-blue-600",
    href: "#"
  },
  {
    title: "Directorio",
    description: "Consulta contactos internos.",
    icon: intranetImages.Icono_Directorio,
    color: "bg-green-100 text-green-600",
    href: "#"
  },
  {
    title: "Manuales",
    description: "Formatos y documentación.",
    icon: intranetImages.Icono_Manuales,
    color: "bg-purple-100 text-purple-600",
    href: "#"
  },
  {
    title: "Pildoras",
    description: "Eventos y actividades.",
    icon: intranetImages.Icono_pildoras,
    color: "bg-orange-100 text-orange-600",
    href: "#"
  },
  {
    title: "Links Aplicativos",
    description: "Manuales y procedimientos.",
    icon: intranetImages.Icono_Linksaplicativos,
    color: "bg-red-100 text-red-600",
    href: "#"
  },
  {
    title: "Administracion",
    description: "Normatividad corporativa.",
    icon: intranetImages.Icono_Administracion,
    color: "bg-cyan-100 text-cyan-600",
    href: "#"
  },
  {
    title: "Formatos",
    description: "Normatividad corporativa.",
    icon: intranetImages.Icono_Formatos,
    color: "bg-cyan-100 text-cyan-600",
    href: "#"
  },
  {
    title: "Documentación",
    description: "Normatividad corporativa.",
    icon: intranetImages.Icono_Documentacion,
    color: "bg-cyan-100 text-cyan-600",
    href: "#"
  },
  {
    title: "Varios",
    description: "Normatividad corporativa.",
    icon: intranetImages.Icono_Varios,
    color: "bg-cyan-100 text-cyan-600",
    href: "#"
  }
];

const QuickAccessCard = () => {
  return (
    <div className="bg-white rounded-3xl shadow-sm border border-gray-100">
      <div className="px-7 py-6 border-b border-gray-100">
        <h2 className="text-xl font-bold text-gray-800">
          Accesos rápidos
        </h2>
        <p className="text-gray-500 mt-1">
          Herramientas de uso frecuente.
        </p>
      </div>

      {/* Grid responsivo para los enlaces dentro de la tarjeta */}
      <div className="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {quickLinks.map((item) => {
          const Icon = item.icon;

          return (
            <a
              key={item.title}
              href={item.href}
              className="group flex items-center justify-between rounded-2xl border border-gray-100 hover:border-orange-300 hover:shadow-md transition-all duration-300 p-4"
            >
              <div className="flex items-center gap-4">
                <div
                  className={`w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden shrink-0 ${
                    typeof item.icon === "string" ? "" : item.color
                  }`}
                >
                  {typeof item.icon === "string" ? (
                    <img
                      src={item.icon}
                      alt={item.title}
                      className="w-full h-full object-cover rounded-xl"
                    />
                  ) : (
                    Icon && <Icon size={22} />
                  )}
                </div>

                <div>
                  <h3 className="font-semibold text-gray-800">
                    {item.title}
                  </h3>
                  <p className="text-sm text-gray-500">
                    {item.description}
                  </p>
                </div>
              </div>

              <ArrowRight
                size={20}
                className="text-gray-400 group-hover:text-orange-500 transition"
              />
            </a>
          );
        })}
      </div>
    </div>
  );
};

export default QuickAccessCard;