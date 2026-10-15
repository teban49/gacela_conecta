import { Building2, CalendarDays, Newspaper } from "lucide-react";
import { useAuth } from "../../../contexts/AuthContext";

const WelcomeCard = () => {

    const { user } = useAuth();

    const organization = user?.organization ?? {};

    const today = new Date();

    const date = today.toLocaleDateString("es-CO", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
    });

    return (

        <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">

            <div className="bg-gradient-to-r from-orange-500 to-orange-400 px-8 py-6 text-white">

                <h2 className="text-3xl font-bold">

                    ¡Bienvenido nuevamente, {user?.nombres}!

                </h2>

                <p className="mt-2 opacity-90">

                    Nos alegra tenerte nuevamente en Gacela Conecta.

                </p>

            </div>

            <div className="grid lg:grid-cols-3 gap-8 p-8">

                {/* Organización */}

                <div className="flex gap-4">

                    <div className="bg-orange-100 rounded-2xl p-4 h-fit">

                        <Building2
                            className="text-orange-600"
                            size={28}
                        />

                    </div>

                    <div>

                        <h3 className="font-semibold text-gray-800">

                            Organización

                        </h3>

                        <p className="text-gray-500 mt-2">

                            {organization.CargoNombre}

                        </p>

                        <p className="text-gray-500">

                            {organization.AreaNombre}

                        </p>

                        <p className="text-gray-400 text-sm mt-1">

                            {organization.AgenciaNombre}

                        </p>

                    </div>

                </div>

                {/* Fecha */}

                <div className="flex gap-4">

                    <div className="bg-blue-100 rounded-2xl p-4 h-fit">

                        <CalendarDays
                            className="text-blue-600"
                            size={28}
                        />

                    </div>

                    <div>

                        <h3 className="font-semibold text-gray-800">

                            Hoy es

                        </h3>

                        <p className="text-gray-500 capitalize mt-2">

                            {date}

                        </p>

                    </div>

                </div>

                {/* Resumen */}

                <div className="flex gap-4">

                    <div className="bg-green-100 rounded-2xl p-4 h-fit">

                        <Newspaper
                            className="text-green-600"
                            size={28}
                        />

                    </div>

                    <div>

                        <h3 className="font-semibold text-gray-800">

                            Intranet

                        </h3>

                        <p className="text-gray-500 mt-2">

                            Consulta noticias, comunicados,

                            documentos institucionales,

                            beneficios y recursos para todos

                            los colaboradores.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    );

};

export default WelcomeCard;