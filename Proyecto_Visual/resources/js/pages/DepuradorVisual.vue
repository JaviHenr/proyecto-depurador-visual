<script setup lang="ts">
import { insertarIndentacion } from '../lib/indentacionCodigo';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onScopeDispose, ref, watch } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';
import BotonModoOscuro from '../components/BotonModoOscuro.vue';
import ControlesEjecucion from '../components/depurador/ControlesEjecucion.vue';
import VisorCodigo from '../components/depurador/VisorCodigo.vue';
import PanelMemoria from '../components/depurador/PanelMemoria.vue';
import { ejemplosDepuracion, type EjemploDepuracion } from '../lib/ejemplosDepuracion';
import { useDepurador } from '../composables/useDepurador';
import { convertirTraza, fuenteDesdeJSON, normalizarFuente } from '../lib/trazaSemantica';

interface CodigoCargado {
    id_codigo: number;
    id_actividad: number | null;
    id_usuario: number;
    nombre_codigo: string;
    nombre_archivo: string;
    formato: string;
    contenido_codigo: string;
    fecha_creacion_codigo: string;
    huella: string;
    traza_json: Record<string, unknown> | null;
}

const props = defineProps<{
    codigo: CodigoCargado | null;
    autorCodigo: { nombre: string; email: string | null } | null;
    actividadCodigo: string | null;
    permisosCodigo: { editar: boolean };
}>();

const rutas = { inicio: '/dashboard', secciones: '/secciones', codigos: '/codigos', logout: '/logout' };
const page = usePage<{
    actor?: { id: number; nombre: string; rol: string };
    auth?: { user?: { nombre_usuario?: string; name?: string; rol?: string } };
    aulaFlash?: string | null;
}>();
const nombre = computed(() => page.props.actor?.nombre || page.props.auth?.user?.nombre_usuario || page.props.auth?.user?.name || 'Usuario');
const inicial = computed(() => nombre.value.trim().charAt(0).toUpperCase() || 'E');
const rol = computed(() => page.props.actor?.rol || page.props.auth?.user?.rol || 'estudiante');
const cerrandoSesion = ref(false);

const ejemploId = ref(ejemplosDepuracion[0].id);
const form = useForm({
    nombre_codigo: props.codigo?.nombre_codigo ?? '',
    nombre_archivo: props.codigo?.nombre_archivo ?? 'programa.py',
    formato: 'py',
    contenido_codigo: props.codigo?.contenido_codigo ?? '',
    id_actividad: props.codigo?.id_actividad ?? null,
    huella: props.codigo?.huella ?? '',
});


// Los códigos guardados cargan su traza desde la base de datos.
// Se conserva la lectura manual de archivos para los ejemplos anteriores.
interface ArchivoJSON { nombre: string; ruta: string; origen: 'general' | 'codigo' }
const archivosJSON = ref<ArchivoJSON[]>([]);
const archivoJSON = ref('');
const trazaJSON = ref<EjemploDepuracion | null>(null);
const jsonActivo = ref(false);
const cargandoJSON = ref(false);
const errorJSON = ref('');
const instruccionesJSON = ref(false);
const fuenteAlCargar = ref('');
const formatoAlCargar = ref('');
let solicitudJSON: AbortController | null = null;
let turnoJSON = 0;
let seleccionDesdeLista = false;
const usaJSON = computed(() => props.codigo ? jsonActivo.value : ejemploId.value === 'json-externo');
const fuenteSinGuardar = computed(() => !!props.codigo && (
    normalizarFuente(form.contenido_codigo) !== normalizarFuente(props.codigo.contenido_codigo)
    || form.formato.trim().toLowerCase() !== props.codigo.formato.trim().toLowerCase()
));
const jsonCompatible = computed(() => !!trazaJSON.value && (!props.codigo || (
    !fuenteSinGuardar.value && normalizarFuente(form.contenido_codigo) === fuenteAlCargar.value
    && form.formato.trim().toLowerCase() === formatoAlCargar.value
)));

function normalizarCodigo(codigo: string) {
    return codigo.replace(/\s+/g, '');
}

const ejemploCompatible = computed(() => {
    if (!props.codigo || form.formato.toLowerCase() !== 'java') return null;
    const contenido = normalizarCodigo(form.contenido_codigo);
    return ejemplosDepuracion.find(item => normalizarCodigo(item.codigo) === contenido) ?? null;
});

const codigoGuardado = computed<EjemploDepuracion>(() => ({
    id: `codigo-${props.codigo?.id_codigo ?? 'nuevo'}`,
    titulo: form.nombre_codigo || 'Código guardado',
    descripcion: usaJSON.value
        ? 'Recorre la traza generada por el Parser-AST y guardada junto a este código.'
        : ejemploCompatible.value
        ? 'Este programa coincide con una traza local disponible. Puedes recorrerla paso a paso.'
        : 'Puedes editar y guardar el programa. Para generar sus pasos automáticamente falta conectar el motor de ejecución.',
    archivo: form.nombre_archivo || 'Código fuente',
    codigo: form.contenido_codigo,
    pasos: usaJSON.value ? (jsonCompatible.value ? trazaJSON.value!.pasos : []) : ejemploCompatible.value?.pasos ?? [],
}));

const ejemplo = computed<EjemploDepuracion>(() => props.codigo
    ? codigoGuardado.value
    : ejemploId.value === 'json-externo'
        ? trazaJSON.value ?? { id: 'json-externo', titulo: 'JSON del motor', descripcion: 'Selecciona un archivo JSON externo para recorrer sus instrucciones.', archivo: archivoJSON.value || 'JSON del motor', codigo: '', pasos: [] }
        : (ejemplosDepuracion.find(item => item.id === ejemploId.value) ?? ejemplosDepuracion[0]));
const {
    indice, confirmado, reproduciendo, modo, intervalo, respuesta, feedback,
    paso, anterior, memoria, salida, esperando, terminado, estado,
    puedeIniciar, puedeAvanzar, iniciar, pausar, avanzar, retroceder, reiniciar, confirmar,
} = useDepurador(ejemplo);
const ejecutados = computed(() => Math.max(0, indice.value + (confirmado.value ? 1 : 0)));
const estadoVisible = computed(() => form.processing ? 'Guardando código y generando traza…' : !usaJSON.value ? estado.value : cargandoJSON.value ? 'Cargando JSON…' : fuenteSinGuardar.value ? 'Guarda o inicia para generar la nueva traza' : jsonCompatible.value ? estado.value : props.codigo ? 'Guarda o inicia para generar la traza' : 'Selecciona y carga un JSON');

function guardarCodigo(iniciarDespues = false) {
    if (!props.codigo || !props.permisosCodigo.editar || form.processing) return;
    pausar();
    form.put(`/codigos/${props.codigo.id_codigo}`, {
        preserveScroll: true,
        onSuccess: (pagina: { props: Record<string, unknown> }) => {
            // La redirección devuelve la nueva huella para la siguiente edición.
            const guardado = pagina.props.codigo as CodigoCargado | null | undefined;
            if (!guardado) return;
            form.nombre_codigo = guardado.nombre_codigo;
            form.nombre_archivo = guardado.nombre_archivo;
            form.formato = guardado.formato;
            form.contenido_codigo = guardado.contenido_codigo;
            form.id_actividad = guardado.id_actividad;
            form.huella = guardado.huella;
            form.defaults();
            cargarTrazaGuardada(guardado);
            if (iniciarDespues) void nextTick(() => iniciar());
        },
    });
}

function cargarTrazaGuardada(codigo: CodigoCargado) {
    ++turnoJSON;
    solicitudJSON?.abort();
    cargandoJSON.value = false;
    trazaJSON.value = null;
    errorJSON.value = '';
    jsonActivo.value = true;
    instruccionesJSON.value = false;
    if (!codigo.traza_json) return;
    try {
        trazaJSON.value = convertirTraza(codigo.traza_json, codigo.contenido_codigo, codigo.nombre_archivo, codigo.nombre_codigo);
        fuenteAlCargar.value = normalizarFuente(codigo.contenido_codigo);
        formatoAlCargar.value = codigo.formato;
    } catch (error) {
        errorJSON.value = error instanceof Error ? error.message : 'No se pudo leer la traza guardada.';
    }
}

const necesitaGenerarTraza = computed(() => !!props.codigo && props.permisosCodigo.editar
    && !!form.contenido_codigo.trim() && (form.isDirty || !props.codigo.traza_json));
function iniciarCodigo() {
    if (form.processing) return;
    if (necesitaGenerarTraza.value) guardarCodigo(true);
    else iniciar();
}


function parametrosJSON(): URLSearchParams {
    const parametros = new URLSearchParams();
    if (props.codigo) parametros.set('codigo', String(props.codigo.id_codigo));
    return parametros;
}
async function obtenerJSON(url: string, control: AbortController): Promise<Record<string, unknown>> {
    const respuesta = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store', signal: control.signal });
    const datos = await respuesta.json().catch(() => { throw new Error('No se recibió JSON. Revisa la ruta y tu sesión.'); });
    if (!respuesta.ok) {
        const errorArchivo = datos.errors?.archivo?.[0];
        throw new Error(errorArchivo || datos.message || 'No se pudo leer el archivo JSON.');
    }
    return datos;
}
async function actualizarArchivos(automatico = false) {
    const turno = ++turnoJSON;
    solicitudJSON?.abort();
    const control = new AbortController();
    solicitudJSON = control;
    cargandoJSON.value = true;
    errorJSON.value = '';
    let cargarAutomaticamente = false;
    try {
        const datos = await obtenerJSON(`/depurador/trazas/archivos?${parametrosJSON()}`, control);
        if (turno !== turnoJSON) return;
        const lista = datos.archivos;
        if (!Array.isArray(lista)) throw new Error('La lista de archivos no tiene el formato esperado.');
        archivosJSON.value = lista as ArchivoJSON[];
        if (!archivosJSON.value.some(item => item.ruta === archivoJSON.value)) {
            const nombreFuente = props.codigo?.nombre_archivo.replace(/\.[^.]+$/, '');
            const privado = archivosJSON.value.find(item => item.origen === 'codigo');
            const porNombre = nombreFuente ? archivosJSON.value.find(item => item.nombre === `traza_${nombreFuente}.json`) : undefined;
            const inicial = archivosJSON.value.find(item => item.nombre === datos.archivo_inicial);
            seleccionDesdeLista = true;
            archivoJSON.value = (privado ?? porNombre ?? inicial ?? archivosJSON.value[0])?.ruta ?? '';
            seleccionDesdeLista = false;
            cargarAutomaticamente = automatico && !!archivoJSON.value && (!props.codigo || !!privado || !!porNombre);
        }
    } catch (error) {
        if (!control.signal.aborted && turno === turnoJSON) errorJSON.value = error instanceof Error ? error.message : 'No se pudo listar la carpeta de JSON.';
    } finally {
        if (turno === turnoJSON) cargandoJSON.value = false;
    }
    if (cargarAutomaticamente && turno === turnoJSON) await cargarJSON();
}
async function cargarJSON() {
    if (!archivoJSON.value || fuenteSinGuardar.value) return;
    const turno = ++turnoJSON;
    solicitudJSON?.abort();
    const control = new AbortController();
    solicitudJSON = control;
    pausar();
    jsonActivo.value = true;
    if (!props.codigo) ejemploId.value = 'json-externo';
    trazaJSON.value = null;
    errorJSON.value = '';
    cargandoJSON.value = true;
    const fuente = props.codigo ? normalizarFuente(form.contenido_codigo) : '';
    const formato = form.formato.trim().toLowerCase();
    const id = props.codigo?.id_codigo;
    const archivo = archivoJSON.value;
    try {
        const parametros = parametrosJSON();
        parametros.set('archivo', archivo);
        const datos = await obtenerJSON(`/depurador/trazas/archivo?${parametros}`, control);
        if (turno !== turnoJSON || archivo !== archivoJSON.value || id !== props.codigo?.id_codigo) return;
        if (props.codigo && (fuente !== normalizarFuente(form.contenido_codigo) || formato !== form.formato.trim().toLowerCase())) return;
        const vista = props.codigo ? { codigo: fuente, esEjemplo: true } : fuenteDesdeJSON(datos.traza);
        const convertido = convertirTraza(datos.traza, vista.codigo, props.codigo ? form.nombre_archivo : archivo.split('/').pop()!, props.codigo ? form.nombre_codigo : archivo.split('/').pop()!);
        fuenteAlCargar.value = fuente;
        formatoAlCargar.value = formato;
        instruccionesJSON.value = !props.codigo && !vista.esEjemplo;
        trazaJSON.value = convertido;
    } catch (error) {
        if (!control.signal.aborted && turno === turnoJSON) errorJSON.value = error instanceof Error ? error.message : 'No se pudo cargar la traza JSON.';
    } finally {
        if (turno === turnoJSON) cargandoJSON.value = false;
    }
}
watch(archivoJSON, () => {
    if (!seleccionDesdeLista) {
        ++turnoJSON;
        solicitudJSON?.abort();
        cargandoJSON.value = false;
    }
    trazaJSON.value = null;
    errorJSON.value = '';
    if (usaJSON.value) reiniciar();
}, { flush: 'sync' });
watch(() => JSON.stringify([props.codigo?.id_codigo, props.codigo?.huella, props.codigo?.traza_json]), () => {
    trazaJSON.value = null;
    jsonActivo.value = false;
    archivoJSON.value = '';
    if (props.codigo) {
        form.nombre_codigo = props.codigo.nombre_codigo;
        form.nombre_archivo = props.codigo.nombre_archivo;
        form.formato = 'py';
        form.contenido_codigo = props.codigo.contenido_codigo;
        form.id_actividad = props.codigo.id_actividad;
        form.huella = props.codigo.huella;
        form.defaults();
    }
    if (props.codigo) cargarTrazaGuardada(props.codigo);
    else void actualizarArchivos(true);
}, { immediate: true });
onScopeDispose(() => { ++turnoJSON; solicitudJSON?.abort(); });

function cerrarSesion() {
    if (cerrandoSesion.value) return;
    pausar();
    cerrandoSesion.value = true;
    router.post(rutas.logout, {}, { onFinish: () => { cerrandoSesion.value = false; } });
}
</script>

<template>
    <Head title="Depuración visual - Depurador Visual" />

    <div class="depurador-app">
        <BarraLateral pestana-activa="depurador" />

        <main class="dv-contenido">
            <header class="dv-encabezado">
                <div>
                    <Link v-if="codigo" :href="rutas.codigos" class="dv-volver">← Volver a códigos</Link>
                    <h1>{{ codigo ? codigo.nombre_codigo : 'Depuración visual' }}</h1>
                    <p>{{ codigo ? 'Edita tu programa Python y recorre su traza de ejecución.' : 'Sigue el código y descubre cómo cambia la memoria.' }}</p>
                    <Link v-if="!codigo" href="/codigos/nuevo" class="dv-volver">Escribir código Python</Link>
                </div>
                <div class="dv-acciones-cuenta">
                    <BotonModoOscuro />
                    <span class="dv-avatar" aria-hidden="true">{{ inicial }}</span>
                    <button type="button" class="dv-salir" :disabled="cerrandoSesion" @click="cerrarSesion">{{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}</button>
                </div>
            </header>

            <p v-if="page.props.aulaFlash" class="dv-aviso correcto" role="status">{{ page.props.aulaFlash }}</p>

            <div class="dv-seleccion">
                <div class="dv-ejercicio">
                    <label :for="codigo ? undefined : 'ejemplo-depuracion'">
                        {{ codigo ? 'Código cargado' : 'Ejercicio de ejemplo' }}
                        <span class="dv-etiqueta">{{ codigo ? form.formato : usaJSON ? 'JSON del motor' : 'Java' }}</span>
                    </label>
                    <strong v-if="codigo" class="dv-nombre-codigo">{{ form.nombre_archivo }}</strong>
                    <select v-else id="ejemplo-depuracion" v-model="ejemploId">
                        <option value="json-externo">{{ archivoJSON ? `JSON · ${archivoJSON.split('/').pop()}` : 'JSON del motor' }}</option>
                        <option v-for="item in ejemplosDepuracion" :key="item.id" :value="item.id">{{ item.titulo }}</option>
                    </select>
                    <p>{{ ejemplo.descripcion }}</p>
                    <p v-if="codigo && autorCodigo">Autor: {{ autorCodigo.nombre }}<span v-if="autorCodigo.email"> · {{ autorCodigo.email }}</span></p>
                    <p v-if="codigo && actividadCodigo">Actividad: {{ actividadCodigo }}</p>
                </div>
                <fieldset class="dv-modos">
                    <legend>Modo de recorrido</legend>
                    <div>
                        <label :class="{ activo: modo === 'predecir' }"><input v-model="modo" type="radio" value="predecir" name="modo-depuracion" />Predecir</label>
                        <label :class="{ activo: modo === 'observar' }"><input v-model="modo" type="radio" value="observar" name="modo-depuracion" />Observar</label>
                    </div>
                    <p>{{ modo === 'predecir' ? 'Completa los valores para continuar.' : 'Sigue la ejecución automática o paso a paso.' }}</p>
                </fieldset>
            </div>


            <section v-if="!codigo" class="dv-seleccion" aria-label="JSON del motor">
                <div class="dv-ejercicio">
                    <label for="archivo-traza-json">JSON del motor</label>
                    <select id="archivo-traza-json" v-model="archivoJSON" :disabled="cargandoJSON || !archivosJSON.length">
                        <option value="" disabled>Selecciona un archivo JSON</option>
                        <option v-for="archivo in archivosJSON" :key="archivo.ruta" :value="archivo.ruta">{{ archivo.nombre }}{{ archivo.origen === 'codigo' ? ' · Este código' : '' }}</option>
                    </select>
                    <p v-if="cargandoJSON" role="status">Leyendo la carpeta externa…</p>
                    <p v-else-if="usaJSON && jsonCompatible" role="status">{{ ejemplo.pasos.length }} pasos cargados de {{ archivoJSON.split('/').pop() }}.</p>
                    <p v-else-if="usaJSON && fuenteSinGuardar" role="status">Guarda los cambios y carga el JSON que el motor genere para el código actualizado.</p>
                    <p v-else>{{ codigo ? 'Elige el JSON que el motor generó para este código.' : 'Selecciona un JSON y carga las instrucciones que entregó el motor.' }}</p>
                    <p v-if="errorJSON" class="dv-aviso error" role="alert">{{ errorJSON }}</p>
                    <p v-if="usaJSON && instruccionesJSON">Esta vista muestra las instrucciones del JSON por línea.</p>
                    <div class="dv-editor-pie">
                        <button type="button" class="dv-boton dv-primario" :disabled="cargandoJSON || !archivoJSON || fuenteSinGuardar" @click="cargarJSON">Cargar JSON</button>
                        <button type="button" class="dv-boton" :disabled="cargandoJSON" @click="actualizarArchivos()">Actualizar lista</button>
                        <button v-if="codigo && ejemploCompatible && jsonActivo" type="button" class="dv-boton" @click="jsonActivo = false; reiniciar()">Usar ejemplo local</button>
                    </div>
                </div>
            </section>

            <form v-if="codigo && permisosCodigo.editar" class="dv-panel dv-editor" @submit.prevent="guardarCodigo()">
                <header class="dv-panel-cabecera">
                    <h2>Editor del código</h2>
                    <span class="dv-etiqueta">{{ form.isDirty ? 'Cambios sin guardar' : 'Guardado' }}</span>
                </header>
                <div class="dv-editor-cuerpo">
                    <div v-if="Object.keys(form.errors).length" class="dv-aviso error" role="alert">
                        <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                    </div>
                    <div class="dv-campos-editor">
                        <label>Nombre
                            <input :disabled="form.processing" v-model="form.nombre_codigo" required maxlength="150" />
                        </label>
                        <label>Archivo
                            <input :disabled="form.processing" v-model="form.nombre_archivo" required maxlength="200" />
                        </label>
                        <label>Formato
                            <input :disabled="form.processing" v-model="form.formato" readonly aria-label="Formato Python" />
                        </label>
                    </div>
                    <label class="dv-codigo-label">Código fuente
                        <textarea :disabled="form.processing" v-model="form.contenido_codigo" @keydown="insertarIndentacion" required maxlength="5000" rows="16" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off" />
                    </label>
                    <div class="dv-editor-pie">
                        <button type="submit" class="dv-boton dv-primario" :disabled="form.processing || !form.isDirty">
                            {{ form.processing ? 'Generando traza…' : 'Guardar cambios' }}
                        </button>
                        <span>{{ form.contenido_codigo.split('\n').length }} líneas · {{ form.contenido_codigo.length }} caracteres</span>
                    </div>
                </div>
            </form>

            <div v-else-if="codigo" class="dv-aviso lectura" role="note">
                Estás viendo el código de {{ autorCodigo?.nombre || 'un estudiante' }} en modo de solo lectura. Solo su autor puede modificarlo o eliminarlo.
            </div>

            <div v-if="codigo && !ejemploCompatible && !usaJSON" class="dv-aviso informacion" role="status">
                Guarda o inicia este programa Python para generar su traza de ejecución.
            </div>

            <p v-if="codigo && errorJSON" class="dv-aviso error" role="alert">{{ errorJSON }}</p>

            <ControlesEjecucion
                v-model:intervalo="intervalo"
                :estado="estadoVisible" :indice="indice" :total="ejemplo.pasos.length" :ejecutados="ejecutados"
                :reproduciendo="reproduciendo" :esperando="esperando"
                :puede-iniciar="!form.processing && (puedeIniciar || necesitaGenerarTraza)" :puede-avanzar="!form.processing && puedeAvanzar"
                @iniciar="iniciarCodigo" @pausar="pausar" @retroceder="retroceder" @avanzar="avanzar" @reiniciar="reiniciar"
            />

            <div class="dv-columnas">
                <div class="dv-columna-codigo">
                    <VisorCodigo :titulo="usaJSON && instruccionesJSON ? 'Instrucciones del JSON' : 'Código fuente'" :codigo="ejemplo.codigo" :archivo="ejemplo.archivo" :linea="paso?.linea ?? null" :esperando="esperando" />
                    <section class="dv-panel dv-consola" aria-labelledby="titulo-consola">
                        <header class="dv-panel-cabecera"><h2 id="titulo-consola">Consola de salida</h2><span v-if="terminado" class="dv-completado">Finalizado</span></header>
                        <pre v-if="salida.length" aria-live="polite">{{ salida.join('\n') }}</pre>
                        <p v-else>{{ terminado ? 'El programa finalizó sin instrucciones de salida.' : 'Aquí aparecerán las salidas incluidas en la traza.' }}</p>
                    </section>
                </div>
                <PanelMemoria
                    v-model:respuesta="respuesta"
                    :paso="paso" :indice="indice" :memoria="memoria" :anterior="anterior"
                    :esperando="esperando" :confirmado="confirmado" :feedback="feedback"
                    @confirmar="confirmar"
                />
            </div>

            <p v-if="codigo" class="dv-nota">El código Python y su traza se guardan juntos en tu cuenta. Al modificar la fuente, guarda o inicia para regenerar los pasos.</p>
            <p v-else class="dv-nota">{{ usaJSON ? 'El recorrido sigue las instrucciones del JSON seleccionado. El archivo se lee desde la carpeta externa y tus predicciones no se guardan.' : codigo ? 'El código se guarda en la base de datos. Puedes cargar su JSON desde el selector o recorrer un ejemplo local compatible.' : 'Ejemplos de práctica con ejecución local. Puedes reiniciarlos las veces que necesites; tus respuestas no se guardan.' }}</p>
        </main>
    </div>
</template>

<style>
/* Todas las reglas están limitadas a esta página; no cambian las otras vistas. */
.depurador-app { --dv-violeta: #7455ef; display: grid; grid-template-columns: 240px minmax(0, 1fr); min-height: 100vh; background: #faf7f3; color: #414454; font: 14px/1.55 'Segoe UI', system-ui, sans-serif; }
.depurador-app *, .depurador-app *::before, .depurador-app *::after { box-sizing: border-box; }
.depurador-app h1, .depurador-app h2, .depurador-app h3, .depurador-app p { margin: 0; }
.depurador-app button, .depurador-app input, .depurador-app select, .depurador-app textarea { font: inherit; }
.depurador-app button { cursor: pointer; }
.depurador-app button:disabled { opacity: .45; cursor: not-allowed; }
.depurador-app a { text-decoration: none; }
.depurador-app :focus-visible { outline: 2px solid #9070dd; outline-offset: 3px; }
.depurador-app .dv-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; color: #0095e7; background: #dff2ff; font-size: 14px; font-weight: 700; }
.depurador-app .dv-avatar-pequeno { width: 34px; height: 34px; font-size: 12px; }
.depurador-app .dv-contenido { width: 100%; max-width: 1480px; min-width: 0; margin: 0 auto; padding: 28px 36px 40px 48px; }
.depurador-app .dv-encabezado { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 30px; }
.depurador-app .dv-encabezado h1 { color: #414454; font-size: 30px; font-weight: 700; line-height: 1.3; letter-spacing: -.6px; }
.depurador-app .dv-encabezado p { margin-top: 7px; color: #9390a2; }
.depurador-app .dv-volver { display: inline-block; margin-bottom: 7px; color: #7954b8; font-size: 12px; }
.depurador-app .dv-acciones-cuenta { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
.depurador-app .dv-salir { min-height: 38px; padding: 8px 17px; border: 1px solid #8bbafa; border-radius: 12px; background: transparent; color: #373cd3; font-size: 12px; }
.depurador-app .dv-salir:hover { background: #f0f5ff; }
.depurador-app .dv-seleccion { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 24px; margin-bottom: 22px; padding: 22px 24px; border: 1px solid #dfd2f8; border-radius: 20px; background: #ebe4fa; }
.depurador-app .dv-ejercicio { flex: 1; min-width: 240px; }
.depurador-app .dv-ejercicio > label { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #715691; font-size: 12px; }
.depurador-app .dv-ejercicio select { max-width: 100%; width: 390px; padding: 9px 32px 9px 11px; border: 1px solid #d6c6ed; border-radius: 10px; background: #f9f6ff; color: #6846aa; font-size: 14px; font-weight: 600; }
.depurador-app .dv-nombre-codigo { display: block; color: #6846aa; font-size: 16px; }
.depurador-app .dv-ejercicio p, .depurador-app .dv-modos p { margin-top: 10px; color: #8b779f; font-size: 12px; }
.depurador-app .dv-modos { min-width: 0; margin: 0; padding: 0; border: 0; }
.depurador-app .dv-modos legend { margin-bottom: 10px; color: #715691; font-size: 12px; }
.depurador-app .dv-modos > div { display: flex; gap: 5px; padding: 4px; border: 1px solid #d9ccef; border-radius: 12px; background: #e5dbf6; }
.depurador-app .dv-modos label { display: flex; align-items: center; justify-content: center; gap: 7px; flex: 1; padding: 6px 12px; border-radius: 8px; color: #937bac; font-size: 12px; cursor: pointer; }
.depurador-app .dv-modos label.activo { background: #fff; color: #7652b7; box-shadow: 0 2px 6px #8663b514; }
.depurador-app .dv-modos input { margin: 0; width: 12px; height: 12px; accent-color: #8f68ca; }
.depurador-app .dv-panel { min-width: 0; border: 1px solid #eee6f4; border-radius: 20px; background: #fff; box-shadow: 0 10px 28px #4e357005; }
.depurador-app .dv-panel-cabecera { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 17px 21px; border-bottom: 1px solid #efe8f6; }
.depurador-app .dv-panel-cabecera h2 { color: #765592; font-size: 14px; font-weight: 600; }
.depurador-app .dv-aviso { margin-bottom: 20px; padding: 13px 16px; border: 1px solid #d9ccef; border-radius: 12px; background: #f6f1fd; color: #765592; font-size: 12px; }
.depurador-app .dv-aviso.correcto { border-color: #d0e5d8; background: #edf7f0; color: #477d5d; }
.depurador-app .dv-aviso.error { border-color: #f0d1d8; background: #fff0f1; color: #a44d5f; }
.depurador-app .dv-aviso.lectura { border-color: #d7dce9; background: #f4f6fa; color: #657087; }
.depurador-app .dv-aviso.informacion a { color: #6542d6; font-weight: 600; }
.depurador-app .dv-editor { overflow: hidden; margin-bottom: 22px; }
.depurador-app .dv-editor-cuerpo { padding: 22px; }
.depurador-app .dv-campos-editor { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 150px; gap: 13px; margin-bottom: 16px; }
.depurador-app .dv-campos-editor label, .depurador-app .dv-codigo-label { display: grid; gap: 6px; color: #766184; font-size: 12px; }
.depurador-app .dv-campos-editor input { min-width: 0; width: 100%; padding: 9px 11px; border: 1px solid #e0d5ed; border-radius: 9px; background: #fdfbff; color: #5f4d6c; }
.depurador-app .dv-codigo-label textarea { width: 100%; min-height: 290px; padding: 15px; overflow: auto; resize: vertical; border: 1px solid #e0d5ed; border-radius: 10px; background: #fcfaff; color: #554960; font: 13px/1.75 ui-monospace, SFMono-Regular, Consolas, monospace; tab-size: 4; white-space: pre; }
.depurador-app .dv-editor-pie { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 14px; color: #94869f; font-size: 11px; }
.depurador-app .dv-etiqueta { display: inline-block; padding: 3px 8px; border-radius: 7px; background: #f4eefb; color: #9b85ae; font-size: 10px; font-weight: 400; }
.depurador-app .dv-boton { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 37px; padding: 8px 13px; border: 1px solid #e4d9f1; border-radius: 10px; background: #faf7fe; color: #8d75a3; font-size: 12px; }
.depurador-app .dv-boton:not(:disabled):hover { border-color: #c8b2e7; background: #f1e9fc; }
.depurador-app .dv-primario { border-color: var(--dv-violeta); background: var(--dv-violeta); color: #fff; }
.depurador-app .dv-primario:not(:disabled):hover { border-color: #6345d1; background: #6345d1; }
.depurador-app .dv-columnas { display: grid; grid-template-columns: minmax(0, 1.18fr) minmax(310px, .9fr); align-items: start; gap: 22px; }
.depurador-app .dv-columna-codigo { display: grid; gap: 22px; min-width: 0; }
.depurador-app .dv-consola { overflow: hidden; }
.depurador-app .dv-consola pre { min-height: 83px; max-height: 180px; margin: 0; padding: 20px; overflow: auto; background: #fcfaff; color: #7b5ba3; font: 14px/1.65 ui-monospace, Consolas, monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
.depurador-app .dv-consola > p { min-height: 83px; padding: 20px; color: #aa98b5; font-size: 12px; }
.depurador-app .dv-completado { color: #649279; font-size: 11px; }
.depurador-app .dv-nota { margin-top: 20px; color: #a495af; font-size: 11px; }
@media (max-width: 1200px) {
    .depurador-app .dv-contenido { padding: 28px; }
    .depurador-app .dv-columnas { gap: 18px; grid-template-columns: minmax(0, 1fr) minmax(290px, .9fr); }
}
@media (max-width: 1000px) {
    .depurador-app .dv-columnas { grid-template-columns: minmax(0, 1fr); }
    .depurador-app .dv-acciones-cuenta .dv-avatar { display: none; }
    .depurador-app .dv-encabezado h1 { font-size: 26px; }
    .depurador-app .dv-contenido { padding: 26px 24px; }
    .depurador-app .dv-campos-editor { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 700px) {
    .depurador-app { grid-template-columns: minmax(0, 1fr); }
    .depurador-app .dv-contenido { padding: 25px 20px 32px; }
    .depurador-app .dv-encabezado { align-items: flex-start; gap: 12px; }
    .depurador-app .dv-encabezado p { font-size: 13px; }
    .depurador-app .dv-salir { padding: 8px 10px; font-size: 11px; }
    .depurador-app .dv-seleccion { padding: 20px; }
    .depurador-app .dv-ejercicio { min-width: 0; flex-basis: 100%; }
    .depurador-app .dv-campos-editor { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 480px) {
    .depurador-app .dv-encabezado { flex-direction: column; }
    .depurador-app .dv-encabezado h1 { font-size: 25px; }
    .depurador-app .dv-contenido { padding-right: 16px; padding-left: 16px; }
}
</style>