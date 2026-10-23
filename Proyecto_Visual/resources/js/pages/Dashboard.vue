<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Auth, Usuario } from '@/types';
import BarraLateral from '../components/BarraLateral.vue';
import BotonModoOscuro from '../components/BotonModoOscuro.vue';

interface ActividadReciente {
    id: number;
    titulo: string;
    tiempo: string;
    colorPunto: string;
}

const props = defineProps<{
    auth?: Auth;
    usuario?: Usuario;
}>();

const usuarioActual = computed(() => props.usuario ?? props.auth?.user ?? null);
const nombreUsuario = computed(() => usuarioActual.value?.nombre_usuario || 'Profesor de prueba');
const userInitial = computed(() => nombreUsuario.value.trim().charAt(0).toUpperCase() || 'P');
const cerrandoSesion = ref(false);

const cerrarSesion = () => {
    if (cerrandoSesion.value) return;
    cerrandoSesion.value = true;
    router.post('/logout', {}, { onFinish: () => { cerrandoSesion.value = false; } });
};

const irACodigos = () => {
    router.visit('/depurador');
};

const irAActividades = () => {
    router.visit('/actividades');
};

const irAMisCodigos = () => {
    router.visit('/codigos');
};

// Datos para actividad reciente
const actividadReciente = ref<ActividadReciente[]>([
    { id: 1, titulo: 'Documento sin título', tiempo: 'Hace 2 horas', colorPunto: 'punto-primario' },
    { id: 2, titulo: 'Planilla de actividad', tiempo: 'Ayer', colorPunto: 'punto-secundario' }
]);
</script>

<template>
    <Head title="Dashboard - Depurador Visual" />

    <div class="dashboard-app">
        <!-- Barra lateral compartida con logo traza-2-claro/oscuro e indicadores -->
        <BarraLateral pestana-activa="inicio" />

        <!-- Contenido principal -->
        <main class="contenido-principal">
            <!-- Encabezado superior con saludo y botón para alternar Modo Oscuro (Luna / Sol) -->
            <header class="encabezado-superior">
                <div class="saludo">
                    <h1>Hola, {{ nombreUsuario }}</h1>
                    <p>Aquí puedes realizar actividades de depuración.</p>
                </div>
                <div class="acciones-encabezado">
                    <BotonModoOscuro />
                    <span class="avatar-encabezado" aria-hidden="true">{{ userInitial }}</span>
                    <button type="button" class="boton-salir" :disabled="cerrandoSesion" @click="cerrarSesion">
                        {{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}
                    </button>
                </div>
            </header>

            <!-- Tarjeta Hero: CÓDIGOS DISPONIBLES con marca de agua Traza -->
            <section class="tarjeta-hero" aria-labelledby="hero-title">
                <div class="hero-info">
                    <span class="hero-tag">CÓDIGOS DISPONIBLES</span>
                    <h2 id="hero-title" class="hero-titulo">Inicia una sesión de depuración</h2>
                    <p class="hero-descripcion">
                        Consulta los códigos y programas disponibles para iniciar una sesión de depuración.
                        Ingresa a tu depurador y selecciona un código para comenzar.
                    </p>
                    <button
                        type="button"
                        class="btn-hero"
                        @click="irACodigos"
                    >
                        <span>Ver códigos</span>
                        <span class="flecha-btn">→</span>
                    </button>
                </div>

                <!-- Gráfico de traza decorativo a la derecha de la tarjeta -->
                <div class="hero-watermark-wrapper" aria-hidden="true">
                    <svg class="hero-watermark" viewBox="0 0 320 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M20 180 H90 V110 H170 V50 H270"
                            stroke="rgba(255, 255, 255, 0.22)"
                            stroke-width="18"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle cx="170" cy="110" r="38" fill="rgba(215, 196, 255, 0.24)" />
                        <circle cx="170" cy="110" r="20" fill="#D7C4FF" />
                    </svg>
                </div>
            </section>

            <!-- Fila de módulos (Actividades y Mis Códigos) -->
            <section class="fila-modulos">
                <!-- Tarjeta Actividades -->
                <article
                    class="tarjeta-modulo"
                    role="button"
                    tabindex="0"
                    @click="irAActividades"
                    @keydown.enter="irAActividades"
                    @keydown.space.prevent="irAActividades"
                >
                    <div class="icono-modulo-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <circle cx="4" cy="6" r="1.5" fill="currentColor"/>
                            <circle cx="4" cy="12" r="1.5" fill="currentColor"/>
                            <circle cx="4" cy="18" r="1.5" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3 class="tarjeta-modulo-titulo">Actividades</h3>
                    <p class="tarjeta-modulo-desc">
                        Actividades a realizar, se visualizan tus actividades pendientes.
                    </p>
                </article>

                <!-- Tarjeta Mis Códigos -->
                <article
                    class="tarjeta-modulo"
                    role="button"
                    tabindex="0"
                    @click="irAMisCodigos"
                    @keydown.enter="irAMisCodigos"
                    @keydown.space.prevent="irAMisCodigos"
                >
                    <div class="icono-modulo-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                    </div>
                    <h3 class="tarjeta-modulo-titulo">Mis Códigos</h3>
                    <p class="tarjeta-modulo-desc">
                        Accede rápidamente a tus actividades frecuentes.
                    </p>
                </article>
            </section>

            <!-- Bloque inferior: Actividad reciente -->
            <section class="seccion-reciente">
                <h2 class="reciente-encabezado">Actividad reciente</h2>
                <div class="lista-reciente">
                    <div
                        v-for="item in actividadReciente"
                        :key="item.id"
                        class="elemento-reciente"
                    >
                        <div class="info-elemento-reciente">
                            <span class="punto-estado" :class="item.colorPunto" aria-hidden="true"></span>
                            <span class="titulo-actividad">{{ item.titulo }}</span>
                        </div>
                        <span class="tiempo-elemento-reciente">{{ item.tiempo }}</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
