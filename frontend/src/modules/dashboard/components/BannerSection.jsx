import dashboardBanner from "../../../assets/images/dashboard-banner.png";

const BannerSection = () => {
    return (
        <section className="overflow-hidden rounded-2xl shadow-sm border border-gray-200 bg-white">

            <img
                src={dashboardBanner}
                alt="Banner Institucional Gacela Conecta"
                className="
                    w-full
                    h-auto
                    object-cover
                    transition-transform
                    duration-500
                    hover:scale-[1.01]
                "
            />

        </section>
    );
};

export default BannerSection;