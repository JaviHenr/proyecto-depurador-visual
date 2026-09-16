<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';


type EstadoSeccion = 'activa' | 'finalizada';
type Publicacion = 'borrador' | 'publicada';
type Avance = 'pendiente' | 'en_curso' | 'completada';
type Formulario = 'seccion' | 'actividad' | 'estudiante';

interface Estudiante {
    id_usuario: number;
    nombre_usuario: string;
    email?: string;
}

interface Actividad {
    id: number;
    seccionId: number;
    titulo: string;
    descripcion: string;
    instrucciones: string[];
    publicacion: Publicacion;
    miEstado?: Avance; // Avance del estudiante actual, no un estado común a todo el curso.
    urlDepurador?: string | null;
}

interface Seccion {
    id: number;
    profesorId: number;
    nombre: string;
    codigo: string;
    numero: string;
    descripcion: string;
    docente: string;
    periodo: string;
    horario: string;
    estado: EstadoSeccion;
    estudiantes: Estudiante[];
    actividades: Actividad[];
}

const props = defineProps<{ secciones?: Seccion[]; estudiantesDisponibles?: Estudiante[] }>();
const page = usePage<{
    auth?: { user?: { id_usuario?: number; id?: number; nombre_usuario?: string; name?: string; rol?: string } };
}>();

const nombre = computed(() => page.props.auth?.user?.nombre_usuario || page.props.auth?.user?.name || 'Estudiante');
const inicial = computed(() => nombre.value.trim().charAt(0).toUpperCase() || 'E');
const rol = computed(() => page.props.auth?.user?.rol || 'estudiante');
const esProfesor = computed(() => rol.value === 'profesor');
const usandoEjemplos = computed(() => props.secciones === undefined);
const usuarioId = computed(() => {
    const id = Number(page.props.auth?.user?.id_usuario ?? page.props.auth?.user?.id);
    if (Number.isSafeInteger(id) && id > 0) return id;
    return usandoEjemplos.value ? (esProfesor.value ? 900 : 101) : null;
});

// Ya no existe una entrada general de Actividades: se abren dentro de la sección.
const rutas = { inicio: '/dashboard', secciones: '/secciones', ajustes: null as string | null, cerrarSesion: '/logout' };
const menu = [
    { texto: 'Inicio', icono: '✨', href: rutas.inicio, activo: false },
    { texto: 'Secciones', icono: '📖', href: rutas.secciones, activo: true },
    { texto: 'Ajustes', icono: '⚙️', href: rutas.ajustes, activo: false },
];

function directorioEjemplo(): Estudiante[] {
    const lista: Estudiante[] = [
        { id_usuario: 101, nombre_usuario: 'Ana Pérez', email: 'ana@example.test' },
        { id_usuario: 102, nombre_usuario: 'Diego Ruiz', email: 'diego@example.test' },
        { id_usuario: 103, nombre_usuario: 'Camila Soto', email: 'camila@example.test' },
        { id_usuario: 104, nombre_usuario: 'Tomás Vera', email: 'tomas@example.test' },
        { id_usuario: 105, nombre_usuario: 'Valentina Díaz', email: 'valentina@example.test' },
    ];
    if (!esProfesor.value && usuarioId.value !== null) {
        return [{ id_usuario: usuarioId.value, nombre_usuario: nombre.value }, ...lista.filter(e => e.id_usuario !== usuarioId.value)];
    }
    return lista.filter(e => e.id_usuario !== usuarioId.value);
}

function seccionesEjemplo(): Seccion[] {
    const alumnos = directorioEjemplo();
    const profesorId = esProfesor.value ? usuarioId.value! : 900;
    const docente = esProfesor.value ? nombre.value : 'Docente de ejemplo';
    return [
        {
            id: 1, profesorId, docente, nombre: 'Programación I', codigo: 'PROG-101', numero: '01',
            descripcion: 'Estudia ciclos, variables y arreglos mediante actividades de depuración visual.',
            periodo: 'Segundo semestre 2026', horario: 'Lunes y miércoles · 10:00 a 11:30', estado: 'activa',
            estudiantes: alumnos.slice(0, 2),
            actividades: [
                { id: 1, seccionId: 1, titulo: 'Primeros pasos con for', descripcion: 'Identifica el contador y la condición de un ciclo for.', instrucciones: ['Reconoce el valor inicial del contador.', 'Predice cuántas veces se repetirá el ciclo.'], publicacion: 'publicada', miEstado: 'completada' },
                { id: 2, seccionId: 1, titulo: 'Recorrido de arreglos', descripcion: 'Relaciona el índice con el valor de cada posición.', instrucciones: ['Identifica la longitud del arreglo.', 'Anota el índice y el valor en cada iteración.'], publicacion: 'publicada', miEstado: 'en_curso' },
                { id: 3, seccionId: 1, titulo: 'Ciclos anidados', descripcion: 'Ejercicio que el profesor está preparando.', instrucciones: ['Compara los contadores del ciclo externo e interno.'], publicacion: 'borrador' },
            ],
        },
        {
            id: 2, profesorId, docente, nombre: 'Taller de Algoritmos', codigo: 'ALGO-102', numero: '02',
            descripcion: 'Practica condiciones y acumuladores con ejercicios guiados.',
            periodo: 'Segundo semestre 2026', horario: 'Martes · 14:00 a 15:30', estado: 'activa',
            estudiantes: [alumnos[0], alumnos[2]],
            actividades: [
                { id: 4, seccionId: 2, titulo: 'Condiciones con while', descripcion: 'Observa cuándo cambia el resultado de una condición.', instrucciones: ['Identifica la condición del ciclo.', 'Explica cuándo deja de cumplirse.'], publicacion: 'publicada', miEstado: 'pendiente' },
            ],
        },
    ];
}

const seccionesLocales = ref<Seccion[]>([]);
const seleccionadaId = ref<number | null>(null);
const actividadId = ref<number | null>(null);
const pestana = ref<'actividades' | 'estudiantes'>('actividades');
const busqueda = ref('');
const filtroEstado = ref<EstadoSeccion | 'todas'>('todas');
const formulario = ref<Formulario | null>(null);
const destinoFormulario = ref<number | null>(null);
const error = ref('');
const aviso = ref('');
const cerrandoSesion = ref(false);

const datosSeccion = reactive({ nombre: '', codigo: '', numero: '', descripcion: '', periodo: '', horario: '' });
const datosActividad = reactive({ titulo: '', descripcion: '', instrucciones: '', publicacion: 'borrador' as Publicacion });
const estudianteId = ref<number | ''>('');

watch(() => ({ datos: props.secciones, id: usuarioId.value, rol: rol.value }), ({ datos }) => {
    seccionesLocales.value = JSON.parse(JSON.stringify(datos ?? seccionesEjemplo()));
    seleccionadaId.value = null;
    actividadId.value = null;
    formulario.value = null;
    aviso.value = '';
}, { immediate: true });

const normalizar = (texto: string) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
const esDocenteDe = (seccion: Seccion) => esProfesor.value && usuarioId.value !== null && seccion.profesorId === usuarioId.value;
const puedeModificar = (seccion: Seccion) => esDocenteDe(seccion) && seccion.estado === 'activa';
const esMiembro = (seccion: Seccion) => usuarioId.value !== null && seccion.estudiantes.some(e => e.id_usuario === usuarioId.value);
const misSecciones = computed(() => seccionesLocales.value.filter(s => esProfesor.value ? esDocenteDe(s) : esMiembro(s)));
const seleccionada = computed(() => misSecciones.value.find(s => s.id === seleccionadaId.value) ?? null);
const filtradas = computed(() => misSecciones.value.filter(s => {
    const coincideEstado = filtroEstado.value === 'todas' || s.estado === filtroEstado.value;
    return coincideEstado && normalizar(`${s.nombre} ${s.codigo} ${s.numero} ${s.docente}`).includes(normalizar(busqueda.value.trim()));
}));

function actividadesDe(seccion: Seccion) {
    return seccion.actividades.filter(a => a.seccionId === seccion.id && (esDocenteDe(seccion) || a.publicacion === 'publicada'));
}

const actividadesVisibles = computed(() => seleccionada.value ? actividadesDe(seleccionada.value) : []);
const actividadSeleccionada = computed(() => actividadesVisibles.value.find(a => a.id === actividadId.value) ?? null);
const directorio = computed(() => props.estudiantesDisponibles ?? (usandoEjemplos.value ? directorioEjemplo() : []));
const disponibles = computed(() => directorio.value.filter(e => !seleccionada.value?.estudiantes.some(inscrito => inscrito.id_usuario === e.id_usuario)));
const etiquetasAvance: Record<Avance, string> = { pendiente: 'Pendiente', en_curso: 'En curso', completada: 'Completada' };

function cerrarFormulario() {
    formulario.value = null;
    destinoFormulario.value = null;
    error.value = '';
}

function abrirSeccion(id: number) {
    if (!misSecciones.value.some(s => s.id === id)) return;
    cerrarFormulario();
    seleccionadaId.value = id;
    actividadId.value = null;
    pestana.value = 'actividades';
    aviso.value = '';
}

function volver() {
    cerrarFormulario();
    seleccionadaId.value = null;
    actividadId.value = null;
    aviso.value = '';
}

function cambiarPestana(valor: 'actividades' | 'estudiantes') {
    cerrarFormulario();
    pestana.value = valor;
    actividadId.value = null;
}

function abrirFormulario(tipo: Formulario) {
    if (!esProfesor.value || usuarioId.value === null) return;
    if (tipo !== 'seccion' && (!seleccionada.value || !puedeModificar(seleccionada.value))) return;
    cerrarFormulario();
    formulario.value = tipo;
    destinoFormulario.value = tipo === 'seccion' ? null : seleccionada.value!.id;
    Object.assign(datosSeccion, { nombre: '', codigo: '', numero: '', descripcion: '', periodo: '', horario: '' });
    Object.assign(datosActividad, { titulo: '', descripcion: '', instrucciones: '', publicacion: 'borrador' });
    estudianteId.value = '';
    aviso.value = '';
}

function destinoValido() {
    const destino = seccionesLocales.value.find(s => s.id === destinoFormulario.value);
    if (!destino || destino.id !== seleccionadaId.value || !puedeModificar(destino)) {
        error.value = 'Selecciona una sección activa que puedas gestionar.';
        return null;
    }
    return destino;
}

function crearSeccion() {
    if (formulario.value !== 'seccion' || !esProfesor.value || usuarioId.value === null) return;
    error.value = '';
    const datos = Object.fromEntries(Object.entries(datosSeccion).map(([clave, valor]) => [clave, valor.trim()])) as typeof datosSeccion;
    if (!datos.nombre || !datos.codigo || !datos.numero || !datos.periodo) {
        error.value = 'Completa el nombre, código, número de sección y periodo.'; return;
    }
    if (misSecciones.value.some(s => normalizar(`${s.codigo}|${s.numero}|${s.periodo}`) === normalizar(`${datos.codigo}|${datos.numero}|${datos.periodo}`))) {
        error.value = 'Ya existe una sección con ese código, número y periodo.'; return;
    }
    const id = Math.max(0, ...seccionesLocales.value.map(s => s.id)) + 1;
    // TODO Laravel: POST /secciones. El profesorId debe obtenerse de la sesión en el servidor.
    seccionesLocales.value.push({ ...datos, id, profesorId: usuarioId.value, docente: nombre.value, estado: 'activa', estudiantes: [], actividades: [] });
    abrirSeccion(id);
    aviso.value = 'Sección creada en esta demostración. Puedes agregar estudiantes y actividades.';
}

function crearActividad() {
    if (formulario.value !== 'actividad') return;
    error.value = '';
    const seccion = destinoValido();
    if (!seccion) return;
    const titulo = datosActividad.titulo.trim();
    const descripcion = datosActividad.descripcion.trim();
    if (!titulo || !descripcion) { error.value = 'Completa el título y la descripción de la actividad.'; return; }
    const id = Math.max(0, ...seccionesLocales.value.flatMap(s => s.actividades.map(a => a.id))) + 1;
    // TODO Laravel: POST /secciones/{seccion}/actividades, validando que sea del profesor.
    seccion.actividades.push({
        id, seccionId: seccion.id, titulo, descripcion,
        instrucciones: datosActividad.instrucciones.split('\n').map(linea => linea.trim()).filter(Boolean),
        publicacion: datosActividad.publicacion,
    });
    cerrarFormulario();
    actividadId.value = id;
    aviso.value = 'Actividad agregada a esta sección en la demostración.';
}

function agregarEstudiante() {
    if (formulario.value !== 'estudiante') return;
    error.value = '';
    const seccion = destinoValido();
    if (!seccion) return;
    const estudiante = directorio.value.find(e => e.id_usuario === Number(estudianteId.value));
    if (!estudiante) { error.value = 'Selecciona un estudiante disponible.'; return; }
    if (seccion.estudiantes.some(e => e.id_usuario === estudiante.id_usuario)) {
        error.value = 'Este estudiante ya pertenece a la sección.'; return;
    }
    // TODO Laravel: POST /secciones/{seccion}/estudiantes; se asocia un usuario existente.
    seccion.estudiantes.push({ ...estudiante });
    cerrarFormulario();
    aviso.value = 'Estudiante agregado a esta sección en la demostración.';
}

function cambiarPublicacion(actividad: Actividad) {
    const seccion = seleccionada.value;
    if (!seccion || !puedeModificar(seccion) || !seccion.actividades.some(a => a === actividad)) return;
    // TODO Laravel: PATCH /secciones/{seccion}/actividades/{actividad}.
    actividad.publicacion = actividad.publicacion === 'borrador' ? 'publicada' : 'borrador';
    aviso.value = actividad.publicacion === 'publicada' ? 'Actividad publicada en esta demostración.' : 'Actividad guardada como borrador en esta demostración.';
}

function cerrarSesion() {
    if (cerrandoSesion.value) return;
    cerrandoSesion.value = true;
    router.post(rutas.cerrarSesion, {}, { onFinish: () => { cerrandoSesion.value = false; } });
}
</script>

<template>
    <Head title="Secciones - Depurador Visual" />
    <div class="secciones-app">
        <aside class="barra-lateral" aria-label="Barra lateral">
            <Link :href="rutas.inicio" class="marca">Depurador Visual</Link>
            <nav class="menu" aria-label="Menú principal">
                <template v-for="item in menu" :key="item.texto">
                    <Link v-if="item.href" :href="item.href" class="menu-item" :class="{ 'menu-activo': item.activo }" :aria-current="item.activo ? 'page' : undefined">
                        <span class="menu-icono" aria-hidden="true">{{ item.icono }}</span>{{ item.texto }}
                    </Link>
                    <button v-else type="button" class="menu-item menu-pendiente" disabled :aria-label="`${item.texto}: próximamente`"><span class="menu-icono" aria-hidden="true">{{ item.icono }}</span>{{ item.texto }}</button>
                </template>
            </nav>
            <div class="cuenta-lateral">
                <div class="perfil"><span class="avatar avatar-pequeno" aria-hidden="true">{{ inicial }}</span><div class="perfil-texto"><strong>{{ nombre }}</strong><span>{{ rol }}</span></div></div>
                <button type="button" class="salir-lateral" :disabled="cerrandoSesion" @click="cerrarSesion">{{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}</button>
            </div>
        </aside>

        <main class="contenido">
            <header class="encabezado">
                <div><h1>¡Hola, {{ nombre }}!</h1><p>{{ esProfesor ? 'Organiza tus secciones, estudiantes y actividades.' : 'Encuentra tus actividades dentro de cada sección.' }}</p></div>
                <div class="acciones-cuenta"><span class="avatar" aria-hidden="true">{{ inicial }}</span><button type="button" class="boton-salir" :disabled="cerrandoSesion" @click="cerrarSesion">{{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}</button></div>
            </header>

            <div class="nota-prototipo"><strong>Demostración de interfaz.</strong> Los cambios son temporales y se pierden al recargar o salir. <span v-if="usandoEjemplos">Se muestran datos de ejemplo.</span></div>
            <div v-if="aviso" class="aviso-local" role="status">{{ aviso }}</div>

            <!-- Primera vista: secciones del profesor o del estudiante actual -->
            <section v-if="!seleccionada" aria-labelledby="titulo-secciones">
                <div class="titulo-seccion cabecera-listado">
                    <h2 id="titulo-secciones">Mis secciones</h2>
                    <button v-if="esProfesor && usuarioId !== null" type="button" class="boton-principal" @click="abrirFormulario('seccion')">+ Crear sección</button>
                </div>

                <form v-if="formulario === 'seccion'" class="formulario-local" @submit.prevent="crearSeccion">
                    <div class="detalle-encabezado"><h3>Nueva sección</h3><button type="button" class="cerrar-detalle" aria-label="Cerrar formulario" @click="cerrarFormulario">×</button></div>
                    <p v-if="error" class="error-formulario" role="alert">{{ error }}</p>
                    <div class="campos-formulario">
                        <label class="campo">Nombre de la asignatura<input v-model="datosSeccion.nombre" required maxlength="120" placeholder="Ej.: Programación I" /></label>
                        <label class="campo">Código de la asignatura<input v-model="datosSeccion.codigo" required maxlength="30" placeholder="Ej.: PROG-101" /></label>
                        <label class="campo">Número de sección<input v-model="datosSeccion.numero" required maxlength="15" placeholder="Ej.: 01" /></label>
                        <label class="campo">Periodo<input v-model="datosSeccion.periodo" required maxlength="80" placeholder="Ej.: Segundo semestre 2026" /></label>
                        <label class="campo campo-completo">Horario <span class="opcional">(opcional)</span><input v-model="datosSeccion.horario" maxlength="160" placeholder="Ej.: Lunes · 10:00 a 11:30" /></label>
                        <label class="campo campo-completo">Descripción <span class="opcional">(opcional)</span><textarea v-model="datosSeccion.descripcion" rows="2" maxlength="2000" placeholder="Describe el propósito de la sección." /></label>
                    </div>
                    <div class="acciones-formulario"><button type="button" class="boton-secundario" @click="cerrarFormulario">Cancelar</button><button class="boton-principal" type="submit">Crear sección</button></div>
                </form>

                <div class="filtros">
                    <div class="buscador"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4 4" /></svg><label for="buscar-seccion" class="solo-lectores">Buscar sección</label><input id="buscar-seccion" v-model="busqueda" type="search" placeholder="Buscar sección, código o docente…" /></div>
                    <div class="filtro-estado"><label for="estado-seccion">Estado</label><select id="estado-seccion" v-model="filtroEstado"><option value="todas">Todas</option><option value="activa">Activas</option><option value="finalizada">Finalizadas</option></select></div>
                </div>
                <p class="conteo" aria-live="polite">{{ filtradas.length }} de {{ misSecciones.length }} secciones</p>

                <div v-if="filtradas.length" class="tarjetas">
                    <article v-for="seccion in filtradas" :key="seccion.id" class="tarjeta">
                        <button type="button" class="tarjeta-boton" @click="abrirSeccion(seccion.id)">
                            <span class="tema">{{ seccion.codigo }} · Sección {{ seccion.numero }}</span><span class="titulo-tarjeta">{{ seccion.nombre }}</span>
                            <span class="descripcion-tarjeta">{{ seccion.descripcion || 'Consulta los estudiantes y las actividades de esta sección.' }}</span>
                            <span class="docente-tarjeta">{{ seccion.docente }}</span><span class="periodo-tarjeta">{{ seccion.periodo }}</span>
                            <span class="resumen-seccion"><span>{{ seccion.estudiantes.length }} estudiantes</span><span>{{ actividadesDe(seccion).length }} actividades</span></span>
                            <span class="tarjeta-pie"><span class="estado" :class="`estado-${seccion.estado}`">{{ seccion.estado === 'activa' ? 'Activa' : 'Finalizada' }}</span><span class="ver-detalle">Abrir sección →</span></span>
                        </button>
                    </article>
                </div>
                <div v-else class="estado-vacio"><span class="vacio-icono" aria-hidden="true">📖</span><h3>{{ misSecciones.length ? 'No encontramos secciones' : 'Aún no tienes secciones' }}</h3><p>{{ misSecciones.length ? 'Prueba con otra búsqueda.' : (esProfesor ? 'Crea tu primera sección para agregar estudiantes y actividades.' : 'Aquí aparecerán las secciones a las que te incorpore tu profesor.') }}</p><button v-if="misSecciones.length" type="button" class="boton-secundario" @click="busqueda = ''; filtroEstado = 'todas'">Limpiar filtros</button></div>
            </section>

            <!-- Segunda vista: contenido de UNA sección, conservando el contexto -->
            <section v-else aria-labelledby="titulo-seccion-abierta">
                <nav class="migas" aria-label="Ubicación"><button type="button" @click="volver">Mis secciones</button><span aria-hidden="true">/</span><span>{{ seleccionada.nombre }} · {{ seleccionada.numero }}</span></nav>
                <div class="panel panel-seccion">
                    <div class="detalle-encabezado"><div><span class="tema">{{ seleccionada.codigo }} · Sección {{ seleccionada.numero }}</span><h2 id="titulo-seccion-abierta">{{ seleccionada.nombre }}</h2></div><span class="estado" :class="`estado-${seleccionada.estado}`">{{ seleccionada.estado === 'activa' ? 'Activa' : 'Finalizada' }}</span></div>
                    <p v-if="seleccionada.descripcion" class="descripcion-detalle">{{ seleccionada.descripcion }}</p>
                    <dl class="datos-seccion"><div><dt>Profesor</dt><dd>{{ seleccionada.docente }}</dd></div><div><dt>Periodo</dt><dd>{{ seleccionada.periodo }}</dd></div><div><dt>Horario</dt><dd>{{ seleccionada.horario || 'Sin definir' }}</dd></div></dl>
                    <nav class="pestanas-seccion" aria-label="Contenido de la sección">
                        <button type="button" :class="{ 'pestana-activa': pestana === 'actividades' }" :aria-pressed="pestana === 'actividades'" @click="cambiarPestana('actividades')">Actividades <span>{{ actividadesVisibles.length }}</span></button>
                        <button type="button" :class="{ 'pestana-activa': pestana === 'estudiantes' }" :aria-pressed="pestana === 'estudiantes'" @click="cambiarPestana('estudiantes')">Estudiantes <span>{{ seleccionada.estudiantes.length }}</span></button>
                    </nav>
                </div>

                <section v-if="pestana === 'actividades'" class="contenido-seccion" aria-labelledby="titulo-actividades">
                    <div class="titulo-seccion cabecera-listado"><h3 id="titulo-actividades">Actividades de la sección</h3><button v-if="puedeModificar(seleccionada)" type="button" class="boton-principal" @click="abrirFormulario('actividad')">+ Crear actividad</button></div>

                    <form v-if="formulario === 'actividad'" class="formulario-local" @submit.prevent="crearActividad">
                        <div class="detalle-encabezado"><h3>Nueva actividad</h3><button type="button" class="cerrar-detalle" aria-label="Cerrar formulario" @click="cerrarFormulario">×</button></div>
                        <p class="destino-formulario">Se agregará a {{ seleccionada.nombre }} · Sección {{ seleccionada.numero }}</p>
                        <p v-if="error" class="error-formulario" role="alert">{{ error }}</p>
                        <div class="campos-formulario">
                            <label class="campo campo-completo">Título<input v-model="datosActividad.titulo" required maxlength="120" placeholder="Ej.: Recorrido de arreglos" /></label>
                            <label class="campo campo-completo">Descripción<textarea v-model="datosActividad.descripcion" required rows="2" maxlength="2000" placeholder="¿Qué aprenderán los estudiantes?" /></label>
                            <label class="campo campo-completo">Instrucciones <span class="opcional">(una por línea)</span><textarea v-model="datosActividad.instrucciones" rows="3" maxlength="6000" placeholder="Lee el código.&#10;Identifica el contador.&#10;Predice el resultado." /></label>
                            <label class="campo">Publicación<select v-model="datosActividad.publicacion"><option value="borrador">Borrador: solo el profesor</option><option value="publicada">Publicada: visible para estudiantes</option></select></label>
                        </div>
                        <div class="acciones-formulario"><button type="button" class="boton-secundario" @click="cerrarFormulario">Cancelar</button><button type="submit" class="boton-principal">Crear actividad</button></div>
                    </form>

                    <div v-if="actividadesVisibles.length" class="lista-actividades">
                        <article v-for="actividad in actividadesVisibles" :key="actividad.id" class="actividad-en-seccion">
                            <button type="button" class="abrir-actividad" :aria-expanded="actividadId === actividad.id" :aria-controls="`contenido-actividad-${actividad.id}`" @click="actividadId = actividadId === actividad.id ? null : actividad.id">
                                <span><strong>{{ actividad.titulo }}</strong><span class="descripcion-fila">{{ actividad.descripcion }}</span></span>
                                <span class="estado" :class="esDocenteDe(seleccionada) ? `estado-${actividad.publicacion}` : `estado-${actividad.miEstado ?? 'pendiente'}`">{{ esDocenteDe(seleccionada) ? (actividad.publicacion === 'publicada' ? 'Publicada' : 'Borrador') : etiquetasAvance[actividad.miEstado ?? 'pendiente'] }}</span>
                            </button>
                            <div v-show="actividadSeleccionada?.id === actividad.id" :id="`contenido-actividad-${actividad.id}`" class="contenido-actividad">
                                <h4>Instrucciones</h4><ol v-if="actividad.instrucciones.length"><li v-for="(instruccion, i) in actividad.instrucciones" :key="i">{{ instruccion }}</li></ol><p v-else>Sin instrucciones adicionales.</p>
                                <div class="acciones-formulario">
                                    <button v-if="puedeModificar(seleccionada)" type="button" class="boton-secundario" @click="cambiarPublicacion(actividad)">{{ actividad.publicacion === 'borrador' ? 'Publicar actividad' : 'Pasar a borrador' }}</button>
                                    <Link v-if="actividad.urlDepurador" :href="actividad.urlDepurador" class="boton-principal">Abrir en depurador →</Link>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div v-else class="estado-vacio"><h3>No hay actividades {{ esProfesor ? 'en esta sección' : 'publicadas' }}</h3><p>{{ puedeModificar(seleccionada) ? 'Crea una actividad para los estudiantes de esta sección.' : 'Las actividades publicadas por el profesor aparecerán aquí.' }}</p></div>
                </section>

                <section v-else class="contenido-seccion" aria-labelledby="titulo-estudiantes">
                    <div class="titulo-seccion cabecera-listado"><h3 id="titulo-estudiantes">Estudiantes de la sección</h3><button v-if="puedeModificar(seleccionada)" type="button" class="boton-principal" @click="abrirFormulario('estudiante')">+ Agregar estudiante</button></div>
                    <form v-if="formulario === 'estudiante'" class="formulario-local" @submit.prevent="agregarEstudiante">
                        <div class="detalle-encabezado"><h3>Agregar estudiante</h3><button type="button" class="cerrar-detalle" aria-label="Cerrar formulario" @click="cerrarFormulario">×</button></div>
                        <p class="destino-formulario">Selecciona un usuario estudiante para {{ seleccionada.nombre }} · Sección {{ seleccionada.numero }}.</p>
                        <p v-if="error" class="error-formulario" role="alert">{{ error }}</p>
                        <label class="campo" style="margin-top: 16px">Estudiante<select v-model="estudianteId" required :disabled="!disponibles.length"><option disabled value="">Selecciona un estudiante</option><option v-for="estudiante in disponibles" :key="estudiante.id_usuario" :value="estudiante.id_usuario">{{ estudiante.nombre_usuario }}{{ estudiante.email ? ` · ${estudiante.email}` : '' }}</option></select></label>
                        <p v-if="!disponibles.length" class="destino-formulario">No hay estudiantes disponibles para agregar.</p>
                        <div class="acciones-formulario"><button type="button" class="boton-secundario" @click="cerrarFormulario">Cancelar</button><button type="submit" class="boton-principal" :disabled="!estudianteId">Agregar a la sección</button></div>
                    </form>
                    <div v-if="seleccionada.estudiantes.length" class="lista-actividades">
                        <div v-for="estudiante in seleccionada.estudiantes" :key="estudiante.id_usuario" class="fila-estudiante"><span class="avatar avatar-pequeno" aria-hidden="true">{{ estudiante.nombre_usuario.charAt(0).toUpperCase() }}</span><div><strong>{{ estudiante.nombre_usuario }}</strong><p v-if="esDocenteDe(seleccionada) && estudiante.email">{{ estudiante.email }}</p></div><span class="etiqueta-ejemplo">{{ estudiante.id_usuario === usuarioId ? 'Tú' : 'Estudiante' }}</span></div>
                    </div>
                    <div v-else class="estado-vacio"><h3>Aún no hay estudiantes</h3><p>Agrega estudiantes a esta sección para que puedan acceder a sus actividades.</p></div>
                </section>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* Estilos contenidos en este archivo: no necesitas modificar app.css. */
.secciones-app {
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
.secciones-app *, .secciones-app *::before, .secciones-app *::after { box-sizing: border-box; }
.secciones-app h1, .secciones-app h2, .secciones-app h3, .secciones-app p { margin: 0; }
.secciones-app button, .secciones-app input, .secciones-app select { font: inherit; }
.secciones-app button { cursor: pointer; }
.secciones-app button:disabled { cursor: not-allowed; }
.secciones-app a { text-decoration: none; }
.secciones-app :focus-visible { outline: 2px solid #6b4dff; outline-offset: 4px; }
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
.detalle-pie { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-top: 24px; }
.boton-principal, .boton-secundario { display: inline-flex; align-items: center; justify-content: center; gap: 12px; padding: 10px 16px; border: 1px solid #7455ef; border-radius: 10px; background: #7455ef; color: white; font-size: 13px; }
.boton-secundario { border-color: #e1d7f6; background: #f0eafa; color: #6741d4; }
.estado-vacio { padding: 44px 24px; border: 1px dashed #dfd5ed; border-radius: 20px; background: #fff; text-align: center; }
.vacio-icono { font-size: 25px; }
.estado-vacio h3 { margin-top: 12px; font-size: 17px; font-weight: 600; }
.estado-vacio p { margin: 8px 0 18px; color: #898194; }
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
    .secciones-app { grid-template-columns: 205px minmax(0, 1fr); }
    .marca { font-size: 18px; }
    .tarjetas { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .acciones-cuenta .avatar { display: none; }
    .contenido { padding: 26px 24px 40px; }
}
@media (max-width: 700px) {
    .secciones-app { grid-template-columns: minmax(0, 1fr); }
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
    .encabezado { flex-direction: column; }
}

/* Información específica de cada sección. */
.docente-tarjeta { color: #716483; font-size: 12px; }
.periodo-tarjeta { margin-top: 4px; color: #9485a7; font-size: 11px; }
.progreso-seccion { display: grid; gap: 8px; width: 100%; margin: 20px 0; }
.progreso-etiqueta { display: flex; justify-content: space-between; gap: 10px; color: #8a79a6; font-size: 10px; }
.progreso-pista { display: block; height: 5px; overflow: hidden; border-radius: 10px; background: #dbcef2; }
.progreso-pista > span { display: block; height: 100%; border-radius: inherit; background: #9c7aec; }
.estado-activa { background: #e1f3e9; color: #347c58; }
.estado-finalizada { background: #f0ecf5; color: #80718d; }
.datos-seccion { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin: 22px 0 26px; }
.datos-seccion dt { margin-bottom: 5px; color: #9991a4; font-size: 11px; }
.datos-seccion dd { margin: 0; color: #60566e; font-size: 13px; overflow-wrap: anywhere; }
.titulo-lista { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.titulo-lista h3 { font-size: 14px; font-weight: 600; }
.titulo-lista > span { color: #9991a4; font-size: 12px; }
.lista-actividades { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
.fila-actividad { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 18px; border-radius: 13px; background: #faf7f3; }
.nombre-actividad { min-width: 0; overflow-wrap: anywhere; color: #455168; font-size: 13px; }
.acciones-actividad { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.enlace-actividad { color: #6741d4; font-size: 12px; }
.sin-actividades { padding: 16px 0; color: #9991a4; font-size: 13px; }
.seleccion-vacia { padding: 14px 0; text-align: center; }
.seleccion-vacia h2 { margin-top: 10px; }
.seleccion-vacia p { margin-top: 8px; color: #9991a4; font-size: 13px; }
@media (max-width: 700px) { .datos-seccion { grid-template-columns: minmax(0, 1fr); gap: 14px; } }
@media (max-width: 480px) { .fila-actividad { align-items: flex-start; flex-direction: column; gap: 9px; } .acciones-actividad { flex-wrap: wrap; } }


/* Sección abierta y formularios de la demostración. */
.secciones-app textarea { font: inherit; }
.secciones-app button:disabled { opacity: .55; }
.nota-prototipo { margin-bottom: 24px; padding: 12px 16px; border: 1px solid #e2d8f4; border-radius: 12px; background: #f2ecfc; color: #81718f; font-size: 12px; }
.aviso-local { margin-bottom: 20px; padding: 12px 16px; border: 1px solid #c8e7d5; border-radius: 12px; background: #eef8f1; color: #3c7657; font-size: 13px; }
.cabecera-listado { justify-content: space-between; }
.cabecera-listado h3 { font-size: 17px; font-weight: 600; }
.resumen-seccion { display: flex; gap: 12px; flex-wrap: wrap; margin: 20px 0; color: #8a79a6; font-size: 11px; }
.migas { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; color: #97909e; font-size: 12px; }
.migas button { padding: 0; border: 0; background: transparent; color: #7250d3; }
.panel-seccion { margin-top: 0; padding-bottom: 0; }
.panel-seccion h2 { margin-top: 6px; color: var(--violeta); font-size: 23px; font-weight: 600; }
.pestanas-seccion { display: flex; gap: 24px; border-top: 1px solid #f0eaf6; }
.pestanas-seccion button { display: flex; align-items: center; gap: 8px; min-height: 54px; padding: 13px 0; border: 0; border-bottom: 2px solid transparent; background: transparent; color: #8b7a9f; }
.pestanas-seccion button.pestana-activa { border-bottom-color: #8967e8; color: #6340c9; font-weight: 600; }
.pestanas-seccion button > span { padding: 1px 6px; border-radius: 6px; background: #f1eafa; font-size: 11px; }
.contenido-seccion { margin-top: 26px; }
.formulario-local { margin: 20px 0; padding: 24px; border: 1px solid #dfd0f8; border-radius: 20px; background: #fff; }
.formulario-local h3 { font-size: 17px; font-weight: 600; color: #6240b9; }
.campos-formulario { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 20px; }
.campo { display: flex; flex-direction: column; gap: 6px; min-width: 0; color: #71647c; font-size: 12px; }
.campo-completo { grid-column: 1 / -1; }
.campo input, .campo select, .campo textarea { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid #e4dbee; border-radius: 10px; background: #fcfafe; color: #4f435f; font-size: 13px; }
.campo textarea { resize: vertical; }
.campo .opcional { color: #a396af; font-size: 11px; }
.destino-formulario { margin-top: 12px !important; color: #9b8cab; font-size: 12px; }
.acciones-formulario { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
.error-formulario { margin-top: 14px !important; padding: 10px 12px; border-radius: 10px; background: #fff0f0; color: #a64444; font-size: 12px; }
.actividad-en-seccion { overflow: hidden; border: 1px solid #ece3f5; border-radius: 16px; background: #fff; }
.abrir-actividad { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; padding: 20px; border: 0; background: transparent; color: #554563; text-align: left; }
.abrir-actividad:hover { background: #fcf9ff; }
.abrir-actividad strong { color: #6946bb; font-size: 14px; font-weight: 600; }
.descripcion-fila { display: block; margin-top: 5px; color: #9688a2; font-size: 12px; }
.estado-publicada { background: #e1f3e9; color: #347c58; }
.estado-borrador { background: #fff5e1; color: #976a23; }
.contenido-actividad { padding: 20px; border-top: 1px solid #f0eaf6; color: #81738f; font-size: 13px; }
.contenido-actividad h4 { margin: 0 0 10px; color: #5f506c; font-size: 13px; font-weight: 600; }
.contenido-actividad ol { padding-left: 22px; margin: 0; }
.contenido-actividad li + li { margin-top: 7px; }
.fila-estudiante { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border: 1px solid #eee6f6; border-radius: 14px; background: #fff; }
.fila-estudiante > div { min-width: 0; flex: 1; overflow-wrap: anywhere; }
.fila-estudiante strong { color: #64546e; font-size: 13px; font-weight: 500; }
.fila-estudiante p { margin-top: 3px; color: #9a8ba6; font-size: 12px; }
@media (max-width: 700px) { .campos-formulario { grid-template-columns: minmax(0, 1fr); } .formulario-local { padding: 20px; } }
@media (max-width: 480px) { .abrir-actividad { align-items: flex-start; flex-direction: column; gap: 10px; } .fila-estudiante { flex-wrap: wrap; } .pestanas-seccion { gap: 18px; } }
</style>
