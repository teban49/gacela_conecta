import { ChevronRight } from "lucide-react";
import { useNavigate } from "react-router-dom";

const ApplicationCard = ({
    title,
    description,
    icon: Icon,
    color = "#3b82f6",
    to = "#",
    disabled = false,
    external = false
}) => {

    const navigate = useNavigate();

    const handleClick = () => {
        if (disabled) return;
        navigate(to);
    };

    const className = `
        group
        w-full
        rounded-2xl
        bg-white
        border
        border-gray-200
        shadow-sm
        p-6
        text-left
        transition-all
        duration-300
        hover:shadow-xl
        hover:-translate-y-1
        disabled:opacity-50
        disabled:cursor-not-allowed
    `;

    const content = (
        <div className="flex items-start justify-between">
            <div>
                <div
                    className="w-14 h-14 rounded-xl flex items-center justify-center mb-5"
                    style={{ backgroundColor: `${color}20` }}
                >
                    <Icon size={28} style={{ color }} />
                </div>
                <h3 className="text-lg font-bold text-slate-800">{title}</h3>
                <p className="mt-2 text-sm text-gray-500 leading-relaxed">{description}</p>
            </div>
            <ChevronRight
                size={24}
                className="text-gray-300 transition-transform duration-300 group-hover:translate-x-1"
            />
        </div>
    );

    if (external) {
        return (
            <a href={to} target="_blank" rel="noopener noreferrer" className={className}>
                {content}
            </a>
        );
    }

    return (
        <button onClick={handleClick} disabled={disabled} className={className}>
            {content}
        </button>
    );

};

export default ApplicationCard;