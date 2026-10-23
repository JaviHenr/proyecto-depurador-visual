<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTheme } from '../lib/useTheme';

const props = withDefaults(
    defineProps<{
        pestanaActiva?: string;
    }>(),
    {
        pestanaActiva: '',
    }
);

interface NavItem {
    id: string;
    etiqueta: string;
    ruta?: string;
    tipoIcono: 'inicio' | 'secciones' | 'actividades' | 'codigos' | 'depurador' | 'ajustes';
}

const page = usePage<{
    auth?: { user?: { nombre_usuario?: string; name?: string; rol?: string } };
    usuario?: { nombre_usuario?: string; name?: string; rol?: string };
    actor?: { id?: number; nombre?: string; rol?: string };
}>();

const { isDark } = useTheme();

const currentUser = computed(() => page.props.usuario ?? page.props.auth?.user ?? null);
const userName = computed(() => currentUser.value?.nombre_usuario || currentUser.value?.name || page.props.actor?.nombre || 'Profesor de prueba');
const userRole = computed(() => currentUser.value?.rol || page.props.actor?.rol || 'Profesor');
const userInitial = computed(() => userName.value.trim().charAt(0).toUpperCase() || 'P');

const activeTab = computed(() => {
    if (props.pestanaActiva) {
        return props.pestanaActiva;
    }
    const path = page.url || '';
    if (path.startsWith('/dashboard')) return 'inicio';
    if (path.startsWith('/secciones')) return 'secciones';
    if (path.startsWith('/actividades')) return 'actividades';
    if (path.startsWith('/codigos')) return 'codigos';
    if (path.startsWith('/depurador') || path.startsWith('/Compilador')) return 'depurador';
    if (path.startsWith('/ajustes')) return 'ajustes';
    return 'inicio';
});

const elementosNav = computed<NavItem[]>(() => [
    { id: 'inicio', etiqueta: 'Inicio', ruta: '/dashboard', tipoIcono: 'inicio' },
    { id: 'secciones', etiqueta: 'Secciones', ruta: '/secciones', tipoIcono: 'secciones' },
    { id: 'actividades', etiqueta: 'Actividades', ruta: '/actividades', tipoIcono: 'actividades' },
    { id: 'codigos', etiqueta: 'Códigos', ruta: '/codigos', tipoIcono: 'codigos' },
    { id: 'depurador', etiqueta: 'Depuración', ruta: '/depurador', tipoIcono: 'depurador' },
    { id: 'ajustes', etiqueta: 'Ajustes', ruta: '/ajustes', tipoIcono: 'ajustes' },
]);

const navegar = (item: NavItem) => {
    if (item.ruta && page.url !== item.ruta) {
        router.visit(item.ruta);
    }
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <!-- Barra lateral de navegación con soporte para modo claro y oscuro -->
    <aside class="barra-lateral" aria-label="Navegación lateral">
        <div class="marca">
            <Link href="/dashboard" class="texto-marca">
                <div class="logo-marca-contenedor">
                    <img
                        :src="isDark ? '/traza-2-oscuro.svg' : '/traza-2-claro.svg'"
                        alt="Depurador Visual Logo"
                        class="logo-marca"
                    />
                </div>
                <div class="marca-texto-apilado">
                    <span class="marca-palabra-1">Depurador</span>
                    <span class="marca-palabra-2">Visual</span>
                </div>
            </Link>
        </div>

        <nav class="menu-navegacion" aria-label="Menú principal">
            <button
                v-for="item in elementosNav"
                :key="item.id"
                type="button"
                class="btn-navegacion"
                :class="{ activo: activeTab === item.id }"
                @click="navegar(item)"
            >
                <span class="contenedor-icono-nav">
                    <!-- Inicio / Home -->
                    <svg
                        v-if="item.tipoIcono === 'inicio'"
                        class="icono-nav"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6h-4v6H4a1 1 0 01-1-1v-9.5z"/>
                    </svg>

                    <!-- Secciones / Stack -->
                    <svg
                        v-else-if="item.tipoIcono === 'secciones'"
                        class="icono-nav"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>

                    <!-- Actividades / List -->
                    <svg
                        v-else-if="item.tipoIcono === 'actividades'"
                        class="icono-nav"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <circle cx="4" cy="6" r="1.5" fill="currentColor"/>
                        <circle cx="4" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="4" cy="18" r="1.5" fill="currentColor"/>
                    </svg>

                    <!-- Códigos / Code -->
                    <svg
                        v-else-if="item.tipoIcono === 'codigos'"
                        class="icono-nav"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>

                    <!-- Depuración / Play -->
                    <svg
                        v-else-if="item.tipoIcono === 'depurador'"
                        class="icono-nav"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>

                    <!-- Ajustes / Sliders -->
                    <svg
                        v-else-if="item.tipoIcono === 'ajustes'"
                        class="icono-nav"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <line x1="4" y1="21" x2="4" y2="14"/>
                        <line x1="4" y1="10" x2="4" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12" y2="3"/>
                        <line x1="20" y1="21" x2="20" y2="16"/>
                        <line x1="20" y1="12" x2="20" y2="3"/>
                        <line x1="1" y1="14" x2="7" y2="14"/>
                        <line x1="9" y1="8" x2="15" y2="8"/>
                        <line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                </span>

                <span class="etiqueta-nav">{{ item.etiqueta }}</span>

                <!-- Indicador circular del elemento activo -->
                <span
                    v-if="activeTab === item.id"
                    class="indicador-activo"
                    aria-hidden="true"
                ></span>
            </button>
        </nav>

        <!-- Pie de barra lateral con información del usuario y botón de cerrar sesión -->
        <div class="pie-barra-lateral">
            <div class="ficha-usuario">
                <div class="avatar-usuario-sm">{{ userInitial }}</div>
                <div class="info-usuario">
                    <span class="nombre-usuario" :title="userName">{{ userName }}</span>
                    <span class="rol-usuario">{{ userRole }}</span>
                </div>
            </div>

            <button
                @click="logout"
                type="button"
                class="btn-cerrar-sesion-nav"
                title="Cerrar sesión"
            >
                <svg
                    class="icono-logout"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Cerrar sesión</span>
            </button>
        </div>
    </aside>
</template>
