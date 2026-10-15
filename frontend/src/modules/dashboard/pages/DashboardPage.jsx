// src/modules/dashboard/pages/DashboardPage.jsx
import { useAuth } from "../../../contexts/AuthContext";
import WelcomeSection from "../components/WelcomeSection";
import BannerSection from "../components/BannerSection";
import ApplicationsSection from "../components/ApplicationsSection";
import ProfileSection from "../components/ProfileSection";

const DashboardPage = () => {
    const { user } = useAuth();

    if (!user) {
        return null;
    }

    return (
        <div className="max-w-7xl mx-auto px-6 py-8 space-y-8">
            <WelcomeSection user={user} />
            <BannerSection />
            <ApplicationsSection
                user={user}
                permissions={user.permissions ?? []}
                roles={user.roles ?? []}
            />
            <ProfileSection user={user} />
        </div>
    );
};

export default DashboardPage;