// ==========================================================================
// OBSERA SOLUTIONS - MASTER TAILWIND CONFIGURATION
// ==========================================================================

tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                'sans': ['Inter', 'sans-serif'],
                'display': ['Space Grotesk', 'sans-serif'],
                'oswald': ['Oswald', 'sans-serif'],
            },
            colors: {
                'brand-dark': '#0f172a',
                'brand-panel': '#1e293b',
                'brand-accent': '#38bdf8',
                'brand-glow': '#0ea5e9',
            },
            animation: {
                blob: "blob 7s infinite",
                'scroll': 'scroll 20s linear infinite',
            },
            keyframes: {
                blob: {
                    "0%": { transform: "translate(0px, 0px) scale(1)" },
                    "33%": { transform: "translate(30px, -50px) scale(1.1)" },
                    "66%": { transform: "translate(-20px, 20px) scale(0.9)" },
                    "100%": { transform: "translate(0px, 0px) scale(1)" },
                },
                scroll: {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-100%)' },
                }
            },
        }
    }
}