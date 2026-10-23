import { ref } from 'vue';

const isDark = ref(false);
let initialized = false;

export function useTheme() {
    const initTheme = () => {
        if (typeof window === 'undefined') return;
        if (initialized) return;

        const saved = localStorage.getItem('theme');
        if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            isDark.value = true;
            document.documentElement.classList.add('dark');
        } else {
            isDark.value = false;
            document.documentElement.classList.remove('dark');
        }
        initialized = true;
    };

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    };

    // Inicializar de inmediato si estamos en el navegador
    if (typeof window !== 'undefined' && !initialized) {
        initTheme();
    }

    return {
        isDark,
        initTheme,
        toggleTheme,
    };
}
