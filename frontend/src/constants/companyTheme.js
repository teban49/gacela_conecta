export const COMPANY_THEME = {

    FLOTA_MAGDALENA: {
        code: "FM",
        name: "Flota Magdalena",
        shortName: "FM",
        color: "#f58634",
        bg: "bg-[#f58634]",
        text: "text-[#f58634]",
        border: "border-[#f58634]"
    },

    EXPRESO_PAZ_RIO: {
        code: "PDR",
        name: "Expreso Paz de Río",
        shortName: "PDR",
        color: "#1b6eb8",
        bg: "bg-[#1b6eb8]",
        text: "text-[#1b6eb8]",
        border: "border-[#1b6eb8]"
    },

    RAPIDO_DUITAMA: {
        code: "RD",
        name: "El Rápido Duitama",
        shortName: "RD",
        color: "#ad130b",
        bg: "bg-[#ad130b]",
        text: "text-[#ad130b]",
        border: "border-[#ad130b]"
    }

};

/**
 * Obtiene el tema de una empresa a partir de su nombre.
 *
 * @param {string} companyName
 * @returns {Object}
 */
export const getCompanyTheme = (companyName = "") => {

    const company = companyName.toLowerCase();

    if (company.includes("flota")) {
        return COMPANY_THEME.FLOTA_MAGDALENA;
    }

    if (
        company.includes("paz") ||
        company.includes("expreso")
    ) {
        return COMPANY_THEME.EXPRESO_PAZ_RIO;
    }

    if (
        company.includes("rápido") ||
        company.includes("rapido") ||
        company.includes("duitama")
    ) {
        return COMPANY_THEME.RAPIDO_DUITAMA;
    }

    return {
        code: "",
        name: companyName,
        shortName: companyName,
        color: "#9ca3af",
        bg: "bg-gray-400",
        text: "text-gray-500",
        border: "border-gray-300"
    };

};