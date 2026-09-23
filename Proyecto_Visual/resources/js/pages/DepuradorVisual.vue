<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';
import ControlesEjecucion from '../components/depurador/ControlesEjecucion.vue';
import VisorCodigo from '../components/depurador/VisorCodigo.vue';
import PanelMemoria from '../components/depurador/PanelMemoria.vue';
import { ejemplosDepuracion } from '../lib/ejemplosDepuracion';
import { useDepurador } from '../composables/useDepurador';

// Conserva estas rutas o ajústalas a los nombres que ya utilices en Laravel.
const rutas = { inicio: '/dashboard', secciones: '/secciones', logout: '/logout' };
const page = usePage<{
    auth?: { user?: { nombre_usuario?: string; name?: string; rol?: string } };
}>();
const nombre = computed(() => page.props.auth?.user?.nombre_usuario || page.props.auth?.user?.name || 'Estudiante');
const inicial = computed(() => nombre.value.trim().charAt(0).toUpperCase() || 'E');
const rol = computed(() => page.props.auth?.user?.rol || 'estudiante');
const cerrandoSesion = ref(false);

const ejemploId = ref(ejemplosDepuracion[0].id);
const ejemplo = computed(() => ejemplosDepuracion.find(item => item.id === ejemploId.value) ?? ejemplosDepuracion[0]);
const {
    indice, confirmado, reproduciendo, modo, intervalo, respuesta, feedback,
    paso, anterior, memoria, salida, esperando, terminado, estado,
    puedeIniciar, puedeAvanzar, iniciar, pausar, avanzar, retroceder, reiniciar, confirmar,
} = useDepurador(ejemplo);
const ejecutados = computed(() => Math.max(0, indice.value + (confirmado.value ? 1 : 0)));

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
                <div><h1>Depuración visual</h1><p>Sigue el código y descubre cómo cambia la memoria.</p></div>
                <div class="dv-acciones-cuenta"><span class="dv-avatar" aria-hidden="true">{{ inicial }}</span><button type="button" class="dv-salir" :disabled="cerrandoSesion" @click="cerrarSesion">{{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}</button></div>
            </header>

            <div class="dv-seleccion">
                <div class="dv-ejercicio">
                    <label for="ejemplo-depuracion">Ejercicio de ejemplo <span class="dv-etiqueta">Java</span></label>
                    <select id="ejemplo-depuracion" v-model="ejemploId">
                        <option v-for="item in ejemplosDepuracion" :key="item.id" :value="item.id">{{ item.titulo }}</option>
                    </select>
                    <p>{{ ejemplo.descripcion }}</p>
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

            <ControlesEjecucion
                v-model:intervalo="intervalo"
                :estado="estado" :indice="indice" :total="ejemplo.pasos.length" :ejecutados="ejecutados"
                :reproduciendo="reproduciendo" :esperando="esperando"
                :puede-iniciar="puedeIniciar" :puede-avanzar="puedeAvanzar"
                @iniciar="iniciar" @pausar="pausar" @retroceder="retroceder" @avanzar="avanzar" @reiniciar="reiniciar"
            />

            <div class="dv-columnas">
                <div class="dv-columna-codigo">
                    <VisorCodigo :codigo="ejemplo.codigo" :archivo="ejemplo.archivo" :linea="paso?.linea ?? null" :esperando="esperando" />
                    <section class="dv-panel dv-consola" aria-labelledby="titulo-consola">
                        <header class="dv-panel-cabecera"><h2 id="titulo-consola">Consola de salida</h2><span v-if="terminado" class="dv-completado">Finalizado</span></header>
                        <pre v-if="salida.length" aria-live="polite">{{ salida.join('\n') }}</pre>
                        <p v-else>El resultado aparecerá al ejecutar <code>System.out.println</code>.</p>
                    </section>
                </div>
                <PanelMemoria
                    v-model:respuesta="respuesta"
                    :paso="paso" :indice="indice" :memoria="memoria" :anterior="anterior"
                    :esperando="esperando" :confirmado="confirmado" :feedback="feedback"
                    @confirmar="confirmar"
                />
            </div>

            <p class="dv-nota">Ejemplos de práctica con ejecución local. Puedes reiniciarlos las veces que necesites; tus respuestas no se guardan.</p>
        </main>
    </div>
</template>
<<<<<<< Updated upstream
=======

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
    .depurador-app { grid-template-columns: 240px minmax(0, 1fr); }
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
>>>>>>> Stashed changes
