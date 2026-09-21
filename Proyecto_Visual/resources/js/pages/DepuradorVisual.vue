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
