import { useAuth } from "../../../contexts/AuthContext";
import { LogOut, UserCircle2 } from "lucide-react";

const DashboardHeader = () => {
    const { user, logout } = useAuth();

    const handleLogout = () => {
        logout();
        window.location.href = "/login";
    };

    const fullName = `${user?.nombres ?? ""} ${user?.apellidos ?? ""}`.trim();

    return (
        <header className="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">

            <div className="max-w-7xl mx-auto h-20 px-6 flex items-center justify-between">

                {/* Logo */}

                <div className="flex items-center gap-4">

                    <img
                        src="/src/assets/images/logo-gacela.png"
                        alt="Gacela Conecta"
                        className="h-12 w-auto"
                    />

                    <div>

                        <h1 className="text-xl font-bold text-gray-900">
                            Gacela Conecta
                        </h1>

                        <p className="text-sm text-gray-500">
                            Portal Corporativo
                        </p>

                    </div>

                </div>

                {/* Usuario */}

                <div className="flex items-center gap-5">

                    <div className="hidden md:flex items-center gap-3">

                        <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">

                            <UserCircle2
                                size={28}
                                className="text-slate-500"
                            />

                        </div>

                        <div className="text-right">

                            <p className="font-semibold text-gray-800">

                                {fullName}

                            </p>

                            <p className="text-xs text-gray-500">

                                {user?.email}

                            </p>

                        </div>

                    </div>

                    <button
                        onClick={handleLogout}
                        className="
                            flex
                            items-center
                            gap-2
                            rounded-xl
                            border
                            border-red-200
                            px-4
                            py-2
                            text-red-600
                            hover:bg-red-50
                            transition-all
                        "
                    >
                        <LogOut size={18} />

                        <span className="hidden sm:inline">
                            Cerrar sesión
                        </span>

                    </button>

                </div>

            </div>

        </header>
    );
};

export default DashboardHeader;