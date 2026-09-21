<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';


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

const rutas = { inicio: '/dashboard', secciones: '/secciones', ajustes: null as string | null, cerrarSesion: '/logout' };

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
        <BarraLateral pestana-activa="secciones" />

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
