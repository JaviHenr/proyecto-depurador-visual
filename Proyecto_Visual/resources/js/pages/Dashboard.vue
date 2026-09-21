<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Auth, Usuario } from '@/types';
import BarraLateral from '../components/BarraLateral.vue';

interface Tarjeta {
    id: number;
    titulo: string;
    descripcion: string;
    ruta?: string;
}

interface Actividad {
    id: number;
    titulo: string;
    tiempo: string;
}

const props = defineProps<{
    auth?: Auth;
    usuario?: Usuario;
}>();

const usuarioActual = computed(() => props.usuario ?? props.auth?.user ?? null);
const nombreUsuario = computed(() => usuarioActual.value?.nombre_usuario || 'Usuario');
const rolUsuario = computed(() => usuarioActual.value?.rol || '');
const inicialUsuario = computed(() => {
    return usuarioActual.value?.nombre_usuario?.trim()?.charAt(0)?.toUpperCase() || 'U';
});

const cerrarSesion = () => {
    router.post('/logout');
};

// Tarjetas principales del dashboard
const tarjetas = ref<Tarjeta[]>([
    {
        id: 1,
        titulo: 'Códigos disponibles',
        descripcion: 'Consulta los códigos y programas disponibles para iniciar una sesión de depuración. Ingresa a tu depurador y selecciona un código para comenzar.',
        ruta: '/depurador'
    },
    {
        id: 2,
        titulo: 'Actividades',
        descripcion: 'Actividades a realizar, se visualizan tus actividades pendientes.',
        ruta: '/actividades'
    },
    {
        id: 3,
        titulo: 'Favoritos',
        descripcion: 'Accede rápidamente a tus actividades frecuentes.'
    }
]);

// Datos de ejemplo para actividad reciente
const actividadReciente = ref<Actividad[]>([
    { id: 1, titulo: 'Documento sin título', tiempo: 'Hace 2 horas' },
    { id: 2, titulo: 'Planilla de actividad', tiempo: 'Ayer' }
]);

const seleccionarTarjeta = (tarjeta: Tarjeta) => {
    if (tarjeta.ruta) {
        router.visit(tarjeta.ruta);
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="dashboard-app">
        <!-- Barra lateral compartida -->
        <BarraLateral pestana-activa="inicio" />

        <!-- Contenido principal -->
        <main class="contenido-principal">
            <!-- Encabezado superior -->
            <header class="encabezado-superior">
                <div class="saludo">
                    <h1>Hola, <span v-if="usuarioActual"> {{ nombreUsuario }}</span></h1>
                    <p>Aquí puedes realizar actividades de depuración.</p>
                </div>
                <div class="acciones-encabezado">
                    <div
                        class="avatar-usuario"
                        :title="usuarioActual ? `${nombreUsuario} (${rolUsuario})` : 'Perfil'"
                    >
                        {{ inicialUsuario }}
                    </div>
                    <button
                        @click="cerrarSesion"
                        type="button"
                        class="btn-cerrar-sesion"
                        title="Cerrar Sesión"
                    >
                        Cerrar Sesión
                    </button>
                </div>
            </header>

            <!-- Cuadrícula de módulos/tarjetas -->
            <section class="cuadricula-tarjetas">
                <article
                    v-for="tarjeta in tarjetas"
                    :key="tarjeta.id"
                    class="tarjeta"
                    :role="tarjeta.ruta ? 'button' : undefined"
                    :tabindex="tarjeta.ruta ? 0 : undefined"
                    @click="seleccionarTarjeta(tarjeta)"
                    @keydown.enter="seleccionarTarjeta(tarjeta)"
                    @keydown.space.prevent="seleccionarTarjeta(tarjeta)"
                >
                    <h3>{{ tarjeta.titulo }}</h3>
                    <p>{{ tarjeta.descripcion }}</p>
                </article>
            </section>

            <!-- Bloque inferior de actividad -->
            <section class="seccion-reciente">
                <h2>Actividad reciente</h2>
                <div class="lista-reciente">
                    <div
                        v-for="item in actividadReciente"
                        :key="item.id"
                        class="elemento-reciente"
                    >
                        <div class="info-elemento-reciente">
                            <span>{{ item.titulo }}</span>
                        </div>
                        <span class="tiempo-elemento-reciente">{{ item.tiempo }}</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
