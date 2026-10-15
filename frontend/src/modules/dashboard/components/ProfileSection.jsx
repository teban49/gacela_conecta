import {
    Mail,
    Phone,
    Building2,
    Briefcase,
    MapPin,
    UserCircle2
} from "lucide-react";

import { getCompanyTheme } from "../../../constants/companyTheme";

const ProfileSection = ({ user }) => {

    const organization = user.organization || {};

    const companies = organization.EmpresasNombres
        ? organization.EmpresasNombres
            .split(",")
            .map(c => c.trim())
        : [];

    return (

        <section className="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">

            <div className="flex items-center gap-3 mb-8">

                <div className="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">

                    <UserCircle2
                        size={28}
                        className="text-slate-600"
                    />

                </div>

                <div>

                    <h2 className="text-2xl font-bold text-slate-800">

                        Mi Perfil

                    </h2>

                    <p className="text-gray-500">

                        Información de tu cuenta corporativa.

                    </p>

                </div>

            </div>

            <div className="grid lg:grid-cols-2 gap-8">

                {/* Información Personal */}

                <div className="space-y-5">

                    <div className="flex items-center gap-4">

                        <Mail
                            className="text-slate-500"
                            size={20}
                        />

                        <div>

                            <p className="text-xs uppercase text-gray-400">

                                Correo Corporativo

                            </p>

                            <p className="font-medium">

                                {user.email}

                            </p>

                        </div>

                    </div>

                    <div className="flex items-center gap-4">

                        <Phone
                            className="text-slate-500"
                            size={20}
                        />

                        <div>

                            <p className="text-xs uppercase text-gray-400">

                                Teléfono

                            </p>

                            <p className="font-medium">

                                {user.telefono || "No registrado"}

                            </p>

                        </div>

                    </div>

                    <div className="flex items-center gap-4">

                        <Briefcase
                            className="text-slate-500"
                            size={20}
                        />

                        <div>

                            <p className="text-xs uppercase text-gray-400">

                                Cargo

                            </p>

                            <p className="font-medium">

                                {organization.CargoNombre ?? "-"}

                            </p>

                        </div>

                    </div>

                </div>

                {/* Organización */}

                <div className="space-y-5">

                    <div className="flex items-center gap-4">

                        <Building2
                            className="text-slate-500"
                            size={20}
                        />

                        <div>

                            <p className="text-xs uppercase text-gray-400">

                                Área

                            </p>

                            <p className="font-medium">

                                {organization.AreaNombre ?? "-"}

                            </p>

                        </div>

                    </div>

                    <div className="flex items-center gap-4">

                        <MapPin
                            className="text-slate-500"
                            size={20}
                        />

                        <div>

                            <p className="text-xs uppercase text-gray-400">

                                Agencia

                            </p>

                            <p className="font-medium">

                                {organization.AgenciaNombre ?? "-"}

                            </p>

                            <p className="text-sm text-gray-500">

                                {organization.AgenciaCiudad ?? ""}

                            </p>

                        </div>

                    </div>

                    <div>

                        <p className="text-xs uppercase text-gray-400 mb-3">
                            Empresas
                        </p>

                        <div className="flex flex-wrap gap-2">

                            {companies.length > 0 ? (

                                companies.map((company) => {

                                    const theme = getCompanyTheme(company);

                                    return (

                                        <span
                                            key={company}
                                            className={`
                                                ${theme.bg}
                                                px-4
                                                py-2
                                                rounded-full
                                                text-sm
                                                font-semibold
                                                text-white
                                                shadow-sm
                                            `}
                                        >
                                            {theme.shortName}
                                        </span>

                                    );

                                })

                            ) : (

                                <span className="text-gray-400">
                                    Sin empresas asignadas
                                </span>

                            )}

                        </div>

                    </div>

                </div>

            </div>

        </section>

    );

};

export default ProfileSection;