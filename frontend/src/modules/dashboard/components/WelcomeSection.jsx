import { Building2, Briefcase, MapPin } from "lucide-react";
import { getCompanyTheme } from "../../../constants/companyTheme";

const WelcomeSection = ({ user }) => {
    const organization = user.organization ?? {};

    const fullName = `${user.nombres ?? ""} ${user.apellidos ?? ""}`.trim();

    const hour = new Date().getHours();

    const greeting =
        hour < 12
            ? "Buenos días"
            : hour < 18
            ? "Buenas tardes"
            : "Buenas noches";

    const companies = organization.EmpresasNombres
        ? organization.EmpresasNombres
              .split(",")
              .map((company) => company.trim())
        : [];

    return (
        <section className="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">

                {/* Información */}

                <div>

                    <p className="text-lg text-gray-500 mb-2">
                        👋 {greeting},
                    </p>

                    <h2 className="text-4xl font-bold text-slate-800">
                        {fullName}
                    </h2>

                    <div className="mt-6 flex flex-col gap-3 text-gray-600">

                        <div className="flex items-center gap-3">

                            <Briefcase
                                size={20}
                                className="text-slate-500"
                            />

                            <span>

                                <strong>
                                    {organization.CargoNombre}
                                </strong>

                                {" · "}

                                {organization.AreaNombre}

                            </span>

                        </div>

                        <div className="flex items-center gap-3">

                            <MapPin
                                size={20}
                                className="text-slate-500"
                            />

                            <span>

                                {organization.AgenciaNombre}

                                {" · "}

                                {organization.AgenciaCiudad}

                            </span>

                        </div>

                    </div>

                </div>

                                {/* Empresas */}

                <div className="lg:text-right">

                    <p className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                        Empresas asignadas
                    </p>

                    <div className="flex flex-wrap justify-start lg:justify-end gap-3">

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
                                            text-white
                                            font-semibold
                                            shadow-sm
                                            flex
                                            items-center
                                            gap-2
                                        `}
                                    >
                                        <Building2 size={16} />

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

        </section>
    );
};

export default WelcomeSection;