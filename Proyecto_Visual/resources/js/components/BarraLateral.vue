<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        pestanaActiva?: string;
    }>(),
    {
        pestanaActiva: 'inicio',
    }
);

interface NavItem {
    id: string;
    etiqueta: string;
    ruta?: string;
}

const page = usePage<{
    auth?: { user?: { nombre_usuario?: string; name?: string; rol?: string } };
    usuario?: { nombre_usuario?: string; name?: string; rol?: string };
}>();

const currentUser = computed(() => page.props.usuario ?? page.props.auth?.user ?? null);
const userName = computed(() => currentUser.value?.nombre_usuario || currentUser.value?.name || 'Usuario');
const userRole = computed(() => currentUser.value?.rol || 'Estudiante');
const userInitial = computed(() => userName.value.trim().charAt(0).toUpperCase() || 'U');

const elementosNav = computed<NavItem[]>(() => [
    { id: 'inicio', etiqueta: 'Inicio', ruta: '/dashboard' },
    { id: 'secciones', etiqueta: 'Secciones', ruta: '/secciones' },
    { id: 'actividades', etiqueta: 'Actividades', ruta: '/actividades' },
    { id: 'depurador', etiqueta: 'Depuración', ruta: '/depurador' },
    { id: 'ajustes', etiqueta: 'Ajustes' },
]);

const navegar = (item: NavItem) => {
    if (item.ruta && item.id !== props.pestanaActiva) {
        router.visit(item.ruta);
    }
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <!-- Barra lateral de navegación compartida -->
    <aside class="barra-lateral" aria-label="Navegación lateral">
        <div class="marca">
            <Link href="/dashboard" class="texto-marca">
                <span class="titulo-marca">Depurador Visual</span>
            </Link>
        </div>

        <nav class="menu-navegacion" aria-label="Menú principal">
            <button
                v-for="item in elementosNav"
                :key="item.id"
                type="button"
                class="btn-navegacion"
                :class="{ activo: pestanaActiva === item.id }"
                @click="navegar(item)"
            >
                <span>{{ item.etiqueta }}</span>
            </button>
        </nav>

        <!-- Pie con usuario y cerrar sesión -->
        <div class="pie-barra-lateral">
            <div class="ficha-usuario">
                <div class="avatar-usuario-sm">{{ userInitial }}</div>
                <div class="info-usuario">
                    <span class="nombre-usuario">{{ userName }}</span>
                    <span class="rol-usuario">{{ userRole }}</span>
                </div>
            </div>
            <button
                @click="logout"
                type="button"
                class="btn-navegacion btn-cerrar-sesion-nav"
                title="Cerrar Sesión"
            >
                <span>Cerrar Sesión</span>
            </button>
        </div>
    </aside>
</template>
