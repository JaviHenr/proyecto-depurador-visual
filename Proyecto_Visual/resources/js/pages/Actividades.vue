<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';

/*
 * Ubicación: resources/js/pages/Actividades.vue
 * Página independiente para Laravel + Inertia + Vue 3.
 * Si Laravel no envía la prop actividades, se muestran ejemplos locales.
 * Para mostrar una lista vacía real, envía actividades: [].
 * La búsqueda, los filtros y los detalles funcionan en esta misma página.
 * Más adelante puedes enviar los registros de tu base de datos por Inertia.
 */

type EstadoActividad = 'pendiente' | 'en_curso' | 'completada';

interface Actividad {
    id: number;
    titulo: string;
    descripcion: string;
    tema: string;
    estado: EstadoActividad;
    instrucciones: string[];
    actualizadaEl?: string | null; // Fecha ISO 8601.
    url?: string | null; // Ruta opcional para abrir la actividad real.
}

const props = defineProps<{ actividades?: Actividad[] }>();

const page = usePage<{
    auth?: { user?: { nombre_usuario?: string; name?: string; rol?: string } };
}>();

const nombre = computed(() => page.props.auth?.user?.nombre_usuario || page.props.auth?.user?.name || 'Estudiante');
const inicial = computed(() => nombre.value.trim().charAt(0).toUpperCase() || 'E');
const rol = computed(() => page.props.auth?.user?.rol || 'estudiante');

// Ajusta estos enlaces a las rutas que ya existen en tu proyecto.
// Las secciones con null quedan deshabilitadas hasta que agregues su ruta.
const rutas = {
    cerrarSesion: '/logout',
};

const ejemplos: Actividad[] = [
    {
        id: 1,
        titulo: 'Primeros pasos con for',
        descripcion: 'Observa cómo cambia el contador en cada iteración de un ciclo for.',
        tema: 'Ciclos for',
        estado: 'pendiente',
        instrucciones: [
            'Lee el código y reconoce el valor inicial del contador.',
            'Identifica la condición que permite continuar el ciclo.',
            'Predice cuántas iteraciones se realizarán antes de ejecutar el programa.',
        ],
        actualizadaEl: '2026-09-16T10:30:00-03:00',
    },
    {
        id: 2,
        titulo: 'Recorrido de arreglos',
        descripcion: 'Relaciona cada índice con el elemento del arreglo que se está recorriendo.',
        tema: 'Arreglos',
        estado: 'en_curso',
        instrucciones: [
            'Identifica la cantidad de elementos del arreglo.',
            'Relaciona el índice de cada iteración con el valor de su posición.',
            'Explica por qué el último índice es menor que la longitud del arreglo.',
        ],
        actualizadaEl: '2026-09-15T16:00:00-03:00',
    },
    {
        id: 3,
        titulo: 'Condiciones con while',
        descripcion: 'Comprende cómo la condición y los cambios en una variable controlan un ciclo.',
        tema: 'Ciclos while',
        estado: 'completada',
        instrucciones: [
            'Reconoce la variable que participa en la condición.',
            'Describe cómo cambia su valor dentro del ciclo.',
            'Indica cuándo la condición se vuelve falsa.',
        ],
        actualizadaEl: '2026-09-14T11:00:00-03:00',
    },
    {
        id: 4,
        titulo: 'Suma paso a paso',
        descripcion: 'Sigue los cambios de un acumulador mientras se suman los valores de un arreglo.',
        tema: 'Variables y acumuladores',
        estado: 'pendiente',
        instrucciones: [
            'Identifica el valor inicial del acumulador.',
            'Anota el resultado de cada suma.',
            'Compara tu predicción con el resultado final del programa.',
        ],
    },
];

const etiquetas: Record<EstadoActividad, string> = {
    pendiente: 'Pendiente',
    en_curso: 'En curso',
    completada: 'Completada',
};

const actividades = computed(() => props.actividades ?? ejemplos);
const usandoEjemplos = computed(() => props.actividades === undefined);
const busqueda = ref('');
const filtroEstado = ref<EstadoActividad | 'todas'>('todas');
const seleccionadaId = ref<number | null>(null);
const cerrandoSesion = ref(false);

const normalizar = (texto: string) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');

const filtradas = computed(() => {
    const consulta = normalizar(busqueda.value.trim());
    return actividades.value.filter(actividad => {
        const coincideEstado = filtroEstado.value === 'todas' || actividad.estado === filtroEstado.value;
        const coincideTexto = normalizar(`${actividad.titulo} ${actividad.descripcion} ${actividad.tema}`).includes(consulta);
        return coincideEstado && coincideTexto;
    });
});

const seleccionada = computed(() => actividades.value.find(actividad => actividad.id === seleccionadaId.value) ?? null);

const recientes = computed(() => actividades.value
    .filter(actividad => actividad.actualizadaEl && !Number.isNaN(Date.parse(actividad.actualizadaEl)))
    .slice()
    .sort((a, b) => Date.parse(b.actualizadaEl!) - Date.parse(a.actualizadaEl!))
    .slice(0, 3));

function fechaLegible(fecha: string | null | undefined) {
    if (!fecha) return '';
    return new Intl.DateTimeFormat('es-CL', {
        day: 'numeric', month: 'short', year: 'numeric', timeZone: 'America/Santiago',
    }).format(new Date(fecha));
}

function limpiarFiltros() {
    busqueda.value = '';
    filtroEstado.value = 'todas';
}

function cerrarSesion() {
    if (cerrandoSesion.value) return;
    cerrandoSesion.value = true;
    // Usa la ruta POST /logout de tu sistema de autenticación existente.
    router.post(rutas.cerrarSesion, {}, { onFinish: () => { cerrandoSesion.value = false; } });
}
</script>

<template>
    <Head title="Actividades - Depurador Visual" />

    <div class="actividades-app">
        <!-- Barra lateral compartida -->
        <BarraLateral pestana-activa="actividades" />

        <main class="contenido">
            <header class="encabezado">
                <div>
                    <h1>¡Hola, {{ nombre }}!</h1>
                    <p>Aquí puedes consultar tus actividades de depuración.</p>
                </div>
                <div class="acciones-cuenta">
                    <span class="avatar" aria-hidden="true">{{ inicial }}</span>
                    <button type="button" class="boton-salir" :disabled="cerrandoSesion" @click="cerrarSesion">
                        {{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}
                    </button>
                </div>
            </header>

            <section aria-labelledby="titulo-actividades">
                <div class="titulo-seccion">
                    <h2 id="titulo-actividades">Mis actividades</h2>
                    <span v-if="usandoEjemplos" class="etiqueta-ejemplo">Datos de ejemplo</span>
                </div>

                <div class="filtros">
                    <div class="buscador">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4 4" />
                        </svg>
                        <label for="buscar-actividad" class="solo-lectores">Buscar actividades</label>
                        <input id="buscar-actividad" v-model="busqueda" type="search" placeholder="Buscar una actividad…" />
                    </div>
                    <div class="filtro-estado">
                        <label for="estado-actividad">Estado</label>
                        <select id="estado-actividad" v-model="filtroEstado">
                            <option value="todas">Todas</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="en_curso">En curso</option>
                            <option value="completada">Completadas</option>
                        </select>
                    </div>
                </div>

                <p class="conteo" aria-live="polite">{{ filtradas.length }} de {{ actividades.length }} actividades</p>

                <div v-if="filtradas.length" class="tarjetas">
                    <article v-for="actividad in filtradas" :key="actividad.id" class="tarjeta"
                        :class="{ 'tarjeta-seleccionada': seleccionadaId === actividad.id }">
                        <button type="button" class="tarjeta-boton" :aria-expanded="seleccionadaId === actividad.id"
                            :aria-controls="seleccionadaId === actividad.id ? 'detalle-actividad' : undefined"
                            @click="seleccionadaId = seleccionadaId === actividad.id ? null : actividad.id">
                            <span class="tema">{{ actividad.tema }}</span>
                            <span class="titulo-tarjeta">{{ actividad.titulo }}</span>
                            <span class="descripcion-tarjeta">{{ actividad.descripcion }}</span>
                            <span class="tarjeta-pie">
                                <span class="estado" :class="`estado-${actividad.estado}`">{{ etiquetas[actividad.estado] }}</span>
                                <span class="ver-detalle">{{ seleccionadaId === actividad.id ? 'Ocultar' : 'Ver actividad' }} <span aria-hidden="true">→</span></span>
                            </span>
                        </button>
                    </article>
                </div>

                <div v-else class="estado-vacio">
                    <span class="vacio-icono" aria-hidden="true">📋</span>
                    <h3>{{ actividades.length ? 'No encontramos actividades' : 'Aún no tienes actividades' }}</h3>
                    <p>{{ actividades.length ? 'Prueba con otra búsqueda o cambia el estado seleccionado.' : 'Las actividades asignadas aparecerán aquí.' }}</p>
                    <button v-if="actividades.length" type="button" class="boton-secundario" @click="limpiarFiltros">Limpiar filtros</button>
                </div>
            </section>

            <!-- Detalles en la misma página; no requiere un controlador adicional -->
            <section v-if="seleccionada" id="detalle-actividad" class="panel detalle" aria-labelledby="titulo-detalle">
                <div class="detalle-encabezado">
                    <div>
                        <span class="tema">{{ seleccionada.tema }}</span>
                        <h2 id="titulo-detalle">{{ seleccionada.titulo }}</h2>
                    </div>
                    <button type="button" class="cerrar-detalle" aria-label="Cerrar detalles de la actividad" @click="seleccionadaId = null">×</button>
                </div>
                <p class="descripcion-detalle">{{ seleccionada.descripcion }}</p>
                <h3 class="titulo-instrucciones">Instrucciones</h3>
                <ol v-if="seleccionada.instrucciones.length" class="instrucciones">
                    <li v-for="(instruccion, indice) in seleccionada.instrucciones" :key="indice">{{ instruccion }}</li>
                </ol>
                <p v-else class="descripcion-detalle">Esta actividad aún no tiene instrucciones.</p>
                <div class="detalle-pie">
                    <span class="estado" :class="`estado-${seleccionada.estado}`">{{ etiquetas[seleccionada.estado] }}</span>
                    <Link v-if="seleccionada.url" :href="seleccionada.url" class="boton-principal">Abrir actividad <span aria-hidden="true">→</span></Link>
                </div>
            </section>

            <!-- Bloque inferior inspirado en “Actividad reciente” de la imagen -->
            <section class="panel recientes" aria-labelledby="titulo-recientes">
                <h2 id="titulo-recientes">Actividad reciente</h2>
                <div v-if="recientes.length" class="lista-recientes">
                    <button v-for="actividad in recientes" :key="actividad.id" type="button" class="fila-reciente" @click="seleccionadaId = actividad.id">
                        <span>{{ actividad.titulo }}</span>
                        <time :datetime="actividad.actualizadaEl ?? undefined">{{ fechaLegible(actividad.actualizadaEl) }}</time>
                    </button>
                </div>
                <p v-else class="sin-recientes">Todavía no hay actividad reciente.</p>
            </section>
        </main>
    </div>
</template>
