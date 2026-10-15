import { useEffect, useState } from "react";
import { CalendarDays, Sparkles } from "lucide-react";

import heroSlides from "../utils/heroSlides";

const IntranetHero = ({ user }) => {

    const [currentSlide, setCurrentSlide] = useState(0);

    useEffect(() => {

        const interval = setInterval(() => {

            setCurrentSlide((previous) =>
                previous === heroSlides.length - 1
                    ? 0
                    : previous + 1
            );

        }, 6000);

        return () => clearInterval(interval);

    }, []);

    const currentDate = new Date().toLocaleDateString("es-CO", {

        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"

    });

    return (

        <section
            className="
                relative
                overflow-hidden
                rounded-3xl
                bg-white
                shadow-sm
                border
                border-slate-200
            "
        >

            {/* Banner */}

            <div className="relative h-72 overflow-hidden">

                {heroSlides.map((slide, index) => (

                    <img
                        key={slide.id}
                        src={slide.image}
                        alt={slide.title}
                        className={`
                            absolute
                            inset-0
                            w-full
                            h-full
                            object-cover
                            transition-all
                            duration-700
                            ${currentSlide === index
                                ? "opacity-100 scale-100"
                                : "opacity-0 scale-105"
                            }
                        `}
                    />

                ))}

                <div className="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-900/40 to-transparent" />

                <div className="absolute inset-0 flex items-center">

                    <div className="max-w-3xl px-10">

                        <span
                            className="
                                inline-flex
                                items-center
                                gap-2
                                bg-white/15
                                backdrop-blur-md
                                text-white
                                px-4
                                py-2
                                rounded-full
                                text-sm
                                mb-6
                            "
                        >

                            <Sparkles size={16} />

                            Portal Corporativo

                        </span>

                        <h1 className="text-5xl font-black text-white leading-tight">

                            Bienvenido,

                            <br />

                            {user?.nombres ?? "Colaborador"}

                        </h1>

                        <p className="text-white/80 mt-5 max-w-xl">

                            Mantente informado de las últimas novedades,
                            comunicados corporativos y herramientas disponibles
                            para todo el Grupo Gacela.

                        </p>

                        <div
                            className="
                                mt-8
                                flex
                                items-center
                                gap-3
                                text-white/80
                            "
                        >

                            <CalendarDays size={18} />

                            {currentDate}

                        </div>

                    </div>

                </div>

            </div>

            {/* Indicadores */}

            <div
                className="
                    absolute
                    bottom-5
                    right-8
                    flex
                    gap-2
                "
            >

                {heroSlides.map((_, index) => (

                    <button
                        key={index}
                        onClick={() => setCurrentSlide(index)}
                        className={`
                            h-2
                            rounded-full
                            transition-all
                            duration-300
                            ${currentSlide === index
                                ? "w-8 bg-white"
                                : "w-2 bg-white/40"
                            }
                        `}
                    />

                ))}

            </div>

        </section>

    );

};

export default IntranetHero;