<script setup lang="ts">
import { useTheme } from '../lib/useTheme';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        class="btn-theme-toggle"
        :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        @click="toggleTheme"
    >
        <!-- Icono Sol cuando está en modo oscuro (para alternar a claro) -->
        <svg
            v-if="isDark"
            class="icono-tema"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <circle cx="12" cy="12" r="5" />
            <line x1="12" y1="1" x2="12" y2="3" />
            <line x1="12" y1="21" x2="12" y2="23" />
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
            <line x1="1" y1="12" x2="3" y2="12" />
            <line x1="21" y1="12" x2="23" y2="12" />
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
        </svg>

        <!-- Icono Luna cuando está en modo claro (para alternar a oscuro) -->
        <svg
            v-else
            class="icono-tema"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    </button>
</template>

<style scoped>
.btn-theme-toggle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background-color: #F1EDFB;
    border: 1px solid #E6DDFB;
    color: #4F26C6;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(79, 38, 198, 0.08);
    flex-shrink: 0;
    padding: 0;
}

.btn-theme-toggle:hover {
    background-color: #EAE1FA;
    transform: scale(1.05);
}

.btn-theme-toggle .icono-tema {
    width: 20px;
    height: 20px;
}

/* Estilos cuando documentElement tiene .dark */
:global(html.dark) .btn-theme-toggle {
    background-color: #201B38;
    border-color: #382E5C;
    color: #C5B6F5;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

:global(html.dark) .btn-theme-toggle:hover {
    background-color: #2B234A;
    color: #FFFFFF;
    border-color: #4B3D7B;
}
</style>
