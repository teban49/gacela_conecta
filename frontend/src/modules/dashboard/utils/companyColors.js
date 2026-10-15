export const getCompanyColor = (companyName) => {

    const company = companyName.toLowerCase().trim();

    if (company.includes("flota")) {
        return "bg-[#f58634]";
    }

    if (company.includes("expreso")) {
        return "bg-[#1b6eb8]";
    }

    if (company.includes("rápido") || company.includes("rapido")) {
        return "bg-[#ad130b]";
    }

    return "bg-gray-400";
};