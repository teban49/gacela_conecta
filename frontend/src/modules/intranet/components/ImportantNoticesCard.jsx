import {
    Bell,
    ChevronRight,
    Pin
} from "lucide-react";

const notices = [

    {
        id: 1,
        title: "Actualización del reglamento interno",
        description: "Consulta los cambios publicados por Gestión Humana.",
        date: "01 Ago 2026",
        important: true
    },

    {
        id: 2,
        title: "Mantenimiento programado",
        description: "El sistema estará en mantenimiento el sábado de 8:00 p.m. a 11:00 p.m.",
        date: "31 Jul 2026",
        important: false
    },

    {
        id: 3,
        title: "Nueva política de seguridad",
        description: "Se encuentra disponible la nueva política de seguridad informática.",
        date: "28 Jul 2026",
        important: false
    }

];

const ImportantNoticesCard = () => {

    return (

        <div className="bg-white rounded-3xl shadow-sm border border-slate-200">

            <div className="flex items-center justify-between p-6 border-b">

                <div>

                    <h2 className="text-xl font-bold text-slate-800">

                        Avisos importantes

                    </h2>

                    <p className="text-sm text-slate-500 mt-1">

                        Comunicados corporativos recientes.

                    </p>

                </div>

                <Bell className="text-orange-500" size={26} />

            </div>

            <div className="divide-y">

                {notices.map((notice) => (

                    <button
                        key={notice.id}
                        className="
                            w-full
                            text-left
                            p-5
                            hover:bg-slate-50
                            transition
                        "
                    >

                        <div className="flex justify-between gap-4">

                            <div className="flex gap-3">

                                {notice.important && (

                                    <Pin
                                        size={16}
                                        className="text-red-500 mt-1"
                                    />

                                )}

                                <div>

                                    <h3 className="font-semibold text-slate-800">

                                        {notice.title}

                                    </h3>

                                    <p className="text-sm text-slate-500 mt-1">

                                        {notice.description}

                                    </p>

                                    <span className="text-xs text-slate-400 mt-3 block">

                                        {notice.date}

                                    </span>

                                </div>

                            </div>

                            <ChevronRight
                                size={18}
                                className="text-slate-400 mt-1"
                            />

                        </div>

                    </button>

                ))}

            </div>

            <div className="p-5 border-t">

                <button
                    className="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        py-3
                        font-medium
                        hover:bg-slate-50
                    "
                >

                    Ver todos los comunicados

                </button>

            </div>

        </div>

    );

};

export default ImportantNoticesCard;