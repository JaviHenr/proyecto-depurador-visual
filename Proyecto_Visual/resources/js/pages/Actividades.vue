<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    inicio: '/dashboard',
    secciones: '/secciones',
    actividades: '/actividades',
    ajustes: null as string | null,
    cerrarSesion: '/logout',
};

const menu = [
    { texto: 'Inicio', icono: '✨', href: rutas.inicio, activo: false },
    { texto: 'Secciones', icono: '📖', href: rutas.secciones, activo: true },
    { texto: 'Actividades', icono: '📋', href: rutas.actividades, activo: false },
    { texto: 'Ajustes', icono: '⚙️', href: rutas.ajustes, activo: false },
];

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
        <!-- Menú lateral, siguiendo la referencia visual -->
        <aside class="barra-lateral" aria-label="Barra lateral">
            <Link :href="rutas.inicio" class="marca">Depurador Visual</Link>

            <nav class="menu" aria-label="Menú principal">
                <template v-for="item in menu" :key="item.texto">
                    <Link v-if="item.href" :href="item.href" class="menu-item" :class="{ 'menu-activo': item.activo }"
                        :aria-current="item.activo ? 'page' : undefined">
                        <span class="menu-icono" aria-hidden="true">{{ item.icono }}</span>{{ item.texto }}
                    </Link>
                    <button v-else type="button" class="menu-item menu-pendiente" disabled :aria-label="`${item.texto}: próximamente`">
                        <span class="menu-icono" aria-hidden="true">{{ item.icono }}</span>{{ item.texto }}
                    </button>
                </template>
            </nav>

            <div class="cuenta-lateral">
                <div class="perfil">
                    <span class="avatar avatar-pequeno" aria-hidden="true">{{ inicial }}</span>
                    <div class="perfil-texto"><strong>{{ nombre }}</strong><span>{{ rol }}</span></div>
                </div>
                <button type="button" class="salir-lateral" :disabled="cerrandoSesion" @click="cerrarSesion">
                    {{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}
                </button>
            </div>
        </aside>

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

<style scoped>
/* Estilos contenidos en este archivo: no necesitas modificar app.css. */
.actividades-app {
    --violeta: #5131f8;
    --texto: #414454;
    --secundario: #79798d;
    display: grid;
    grid-template-columns: 232px minmax(0, 1fr);
    min-height: 100vh;
    color: var(--texto);
    background: #faf7f3;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 14px;
    line-height: 1.55;
}
.actividades-app *, .actividades-app *::before, .actividades-app *::after { box-sizing: border-box; }
.actividades-app h1, .actividades-app h2, .actividades-app h3, .actividades-app p { margin: 0; }
.actividades-app button, .actividades-app input, .actividades-app select { font: inherit; }
.actividades-app button { cursor: pointer; }
.actividades-app button:disabled { cursor: not-allowed; }
.actividades-app a { text-decoration: none; }
.actividades-app :focus-visible { outline: 2px solid #6b4dff; outline-offset: 4px; }
.barra-lateral { position: sticky; top: 0; display: flex; flex-direction: column; height: 100vh; height: 100dvh; overflow-y: auto; padding: 20px 12px 12px; background: #fff; }
.marca { display: block; padding: 0 8px; color: #7455ff; font-size: 20px; font-weight: 700; white-space: nowrap; }
.menu { display: grid; gap: 8px; margin-top: 32px; }
.menu-item { display: flex; align-items: center; gap: 14px; min-height: 46px; width: 100%; padding: 10px 16px; border: 0; border-radius: 12px; background: transparent; color: #293649; text-align: left; }
.menu-item:hover:not(:disabled) { background: #f5f0ff; }
.menu-activo, .menu-activo:hover:not(:disabled) { background: #ece4fd; color: var(--violeta); font-weight: 600; }
.menu-icono { width: 18px; text-align: center; font-size: 14px; }
.menu-pendiente { color: #8d8b99; }
.cuenta-lateral { margin-top: auto; padding-top: 30px; }
.perfil { display: flex; align-items: center; gap: 10px; padding: 26px 8px 18px; border-top: 1px solid #efedf1; }
.perfil-texto { display: grid; min-width: 0; }
.perfil-texto strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #243044; font-size: 13px; font-weight: 500; }
.perfil-texto > span { color: #9290a0; font-size: 12px; text-transform: capitalize; }
.avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; color: #0095e7; background: #dff2ff; font-size: 14px; font-weight: 700; }
.avatar-pequeno { width: 34px; height: 34px; font-size: 12px; }
.salir-lateral { margin-left: 8px; padding: 9px 8px; border: 0; background: transparent; color: #c12626; }
.contenido { width: 100%; max-width: 1470px; min-width: 0; margin: 0 auto; padding: 28px 36px 48px 48px; }
.encabezado { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 32px; }
.encabezado h1 { font-size: 30px; font-weight: 700; line-height: 1.3; letter-spacing: -.6px; overflow-wrap: anywhere; }
.encabezado p { margin-top: 7px; color: #9390a2; }
.acciones-cuenta { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
.boton-salir { min-height: 38px; padding: 8px 17px; border: 1px solid #8bbafa; border-radius: 12px; background: transparent; color: #373cd3; font-size: 12px !important; }
.boton-salir:hover { background: #f0f5ff; }
.boton-salir:disabled, .salir-lateral:disabled { opacity: .6; }
.titulo-seccion { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.titulo-seccion h2 { font-size: 19px; font-weight: 600; }
.etiqueta-ejemplo { padding: 3px 9px; border: 1px solid #e6dff3; border-radius: 20px; background: #f4effa; color: #82738e; font-size: 11px; }
.filtros { display: flex; align-items: center; gap: 16px; margin-bottom: 10px; }
.buscador { position: relative; flex: 1; min-width: 0; }
.buscador svg { position: absolute; top: 13px; left: 14px; width: 18px; height: 18px; color: #a19aae; pointer-events: none; }
.buscador input { width: 100%; min-height: 44px; padding: 10px 16px 10px 41px; border: 1px solid #e9e4ef; border-radius: 12px; background: #fff; color: var(--texto); }
.buscador input::placeholder { color: #a29aac; }
.filtro-estado { display: flex; align-items: center; gap: 10px; }
.filtro-estado label { color: #797287; font-size: 13px; }
.filtro-estado select { min-height: 44px; min-width: 142px; padding: 9px 12px; border: 1px solid #e9e4ef; border-radius: 12px; background: #fff; color: var(--texto); }
.conteo { margin-bottom: 18px !important; color: #9991a4; font-size: 12px; }
.tarjetas { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; align-items: stretch; }
.tarjeta { min-width: 0; overflow: hidden; border: 1px solid #ddcfff; border-radius: 20px; background: #ebe4fa; box-shadow: 0 10px 28px rgba(78, 53, 112, .035); transition: border-color .15s, background .15s; }
.tarjeta:hover, .tarjeta-seleccionada { border-color: #b9a2ff; background: #e6dcfb; }
.tarjeta-boton { display: flex; flex-direction: column; align-items: flex-start; width: 100%; height: 100%; min-height: 213px; padding: 22px 23px; border: 0; border-radius: inherit; background: transparent; color: inherit; text-align: left; }
.tarjeta-boton:focus-visible { outline-offset: -4px; }
.tema { color: #8a72b5; font-size: 11px; }
.titulo-tarjeta { margin-top: 10px; color: var(--violeta); font-size: 18px; font-weight: 700; line-height: 1.35; overflow-wrap: anywhere; }
.descripcion-tarjeta { margin-top: 10px; margin-bottom: 22px; color: #6c667e; font-size: 13px; line-height: 1.65; }
.tarjeta-pie { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; width: 100%; margin-top: auto; }
.estado { display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 7px; font-size: 10px; font-weight: 500; white-space: nowrap; }
.estado::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: currentColor; }
.estado-pendiente { background: #fff5e1; color: #976a23; }
.estado-en_curso { background: #e6efff; color: #4468a4; }
.estado-completada { background: #e1f3e9; color: #347c58; }
.ver-detalle { color: #6741d4; font-size: 11px; white-space: nowrap; }
.panel { margin-top: 34px; padding: 28px; border-radius: 24px; background: #fff; box-shadow: 0 10px 30px rgba(62, 41, 86, .035); }
.panel h2 { font-size: 18px; font-weight: 500; }
.detalle { border: 1px solid #e6dbff; }
.detalle-encabezado { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; }
.detalle-encabezado h2 { margin-top: 6px; color: var(--violeta); font-weight: 600; }
.cerrar-detalle { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 30px; height: 30px; border: 0; border-radius: 9px; background: #f6f2fb; color: #84748f; font-size: 22px !important; }
.descripcion-detalle { margin-top: 14px !important; color: #787083; }
.titulo-instrucciones { margin-top: 20px !important; font-size: 13px; font-weight: 600; }
.instrucciones { margin: 10px 0 0; padding-left: 22px; color: #787083; }
.instrucciones li + li { margin-top: 8px; }
.detalle-pie { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-top: 24px; }
.boton-principal, .boton-secundario { display: inline-flex; align-items: center; justify-content: center; gap: 12px; padding: 10px 16px; border: 1px solid #7455ef; border-radius: 10px; background: #7455ef; color: white; font-size: 13px; }
.boton-secundario { border-color: #e1d7f6; background: #f0eafa; color: #6741d4; }
.lista-recientes { display: grid; gap: 12px; margin-top: 20px; }
.fila-reciente { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; min-height: 52px; padding: 13px 18px; border: 0; border-radius: 13px; background: #faf7f3; color: #455168; text-align: left; }
.fila-reciente:hover { background: #f3edf9; }
.fila-reciente > span { min-width: 0; overflow-wrap: anywhere; }
.fila-reciente time { flex-shrink: 0; color: #9891a5; font-size: 12px; }
.estado-vacio { padding: 44px 24px; border: 1px dashed #dfd5ed; border-radius: 20px; background: #fff; text-align: center; }
.vacio-icono { font-size: 25px; }
.estado-vacio h3 { margin-top: 12px; font-size: 17px; font-weight: 600; }
.estado-vacio p { margin: 8px 0 18px; color: #898194; }
.sin-recientes { margin-top: 18px !important; color: #9991a4; }
.solo-lectores { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
@media (min-width: 1450px) { .tarjetas { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 1100px) {
    .contenido { padding-right: 28px; padding-left: 32px; }
    .encabezado { align-items: flex-start; }
    .encabezado h1 { font-size: 27px; }
    .acciones-cuenta { gap: 10px; }
    .tarjeta-boton { padding: 21px 19px; }
    .tarjeta-pie { gap: 8px; }
}
@media (max-width: 950px) {
    .actividades-app { grid-template-columns: 205px minmax(0, 1fr); }
    .marca { font-size: 18px; }
    .tarjetas { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .acciones-cuenta .avatar { display: none; }
    .contenido { padding: 26px 24px 40px; }
}
@media (max-width: 700px) {
    .actividades-app { grid-template-columns: minmax(0, 1fr); }
    .barra-lateral { position: static; height: auto; padding: 18px 16px 12px; }
    .marca { padding: 0 4px; }
    .menu { display: flex; gap: 6px; margin-top: 18px; overflow-x: auto; }
    .menu-item { flex: 0 0 auto; width: auto; min-height: 40px; padding: 8px 12px; gap: 8px; font-size: 13px; }
    .cuenta-lateral { display: none; }
    .contenido { padding: 25px 20px 32px; }
    .encabezado { gap: 12px; margin-bottom: 26px; }
    .encabezado h1 { font-size: 24px; }
    .encabezado p { font-size: 13px; }
    .boton-salir { padding: 8px 10px; font-size: 11px !important; }
    .filtros { align-items: stretch; flex-direction: column; gap: 10px; }
    .filtro-estado select { flex: 1; }
    .tarjetas { gap: 14px; }
    .panel { margin-top: 24px; padding: 22px; }
}
@media (max-width: 480px) {
    .tarjetas { grid-template-columns: minmax(0, 1fr); }
    .tarjeta-boton { min-height: 0; padding: 23px; }
    .descripcion-tarjeta { margin-bottom: 18px; }
    .fila-reciente { align-items: flex-start; flex-direction: column; gap: 4px; }
    .encabezado { flex-direction: column; }
}
</style>
