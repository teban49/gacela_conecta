import {
    ShieldCheck,
    ChevronLeft,
    ChevronRight,
    Lightbulb
} from "lucide-react";

import { useEffect, useState } from "react";

const tips = [

    {
        id: 1,
        title: "Protege tu contraseña",
        description:
            "Nunca compartas tu contraseña con otras personas y cámbiala periódicamente."
    },

    {
        id: 2,
        title: "Bloquea tu equipo",
        description:
            "Cuando te ausentes de tu puesto utiliza Windows + L para bloquear el computador."
    },

    {
        id: 3,
        title: "Cuidado con los correos",
        description:
            "Si un correo solicita información personal o financiera, verifica primero con el remitente."
    },

    {
        id: 4,
        title: "Mantén actualizado tu equipo",
        description:
            "Instala las actualizaciones de Windows cuando el área de TI lo indique."
    },

    {
        id: 5,
        title: "Realiza copias de seguridad",
        description:
            "Guarda la información importante en las ubicaciones autorizadas por la empresa."
    }

];

const TipsCard = () => {

    const [current, setCurrent] = useState(0);

    useEffect(() => {

        const interval = setInterval(() => {

            nextTip();

        }, 7000);

        return () => clearInterval(interval);

    }, [current]);

    const previousTip = () => {

        setCurrent((prev) =>
            prev === 0
                ? tips.length - 1
                : prev - 1
        );

    };

    const nextTip = () => {

        setCurrent((prev) =>
            prev === tips.length - 1
                ? 0
                : prev + 1
        );

    };

    const tip = tips[current];

    return (

        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 h-full">

            <div className="flex items-center justify-between p-6 border-b">

                <div>

                    <h2 className="text-xl font-bold text-slate-800">

                        Tips del Área de Sistemas

                    </h2>

                    <p className="text-sm text-slate-500 mt-1">

                        Recomendaciones para un uso seguro de los recursos tecnológicos.

                    </p>

                </div>

                <ShieldCheck
                    size={28}
                    className="text-[#1b6eb8]"
                />

            </div>

            <div className="p-8">

                <div className="flex items-center gap-3 mb-6">

                    <div
                        className="
                            w-12
                            h-12
                            rounded-2xl
                            bg-blue-100
                            flex
                            items-center
                            justify-center
                        "
                    >

                        <Lightbulb
                            className="text-[#1b6eb8]"
                            size={24}
                        />

                    </div>

                    <div>

                        <h3 className="font-bold text-lg text-slate-800">

                            {tip.title}

                        </h3>

                    </div>

                </div>

                <p className="text-slate-600 leading-relaxed">

                    {tip.description}

                </p>

            </div>

            <div className="border-t p-5 flex justify-between items-center">

                <button
                    onClick={previousTip}
                    className="
                        p-2
                        rounded-xl
                        hover:bg-slate-100
                        transition
                    "
                >

                    <ChevronLeft size={20} />

                </button>

                <div className="flex gap-2">

                    {tips.map((_, index) => (

                        <div
                            key={index}
                            className={`
                                h-2
                                rounded-full
                                transition-all
                                ${index === current
                                    ? "w-8 bg-[#1b6eb8]"
                                    : "w-2 bg-gray-300"
                                }
                            `}
                        />

                    ))}

                </div>

                <button
                    onClick={nextTip}
                    className="
                        p-2
                        rounded-xl
                        hover:bg-slate-100
                        transition
                    "
                >

                    <ChevronRight size={20} />

                </button>

            </div>

        </div>

    );

};

export default TipsCard;