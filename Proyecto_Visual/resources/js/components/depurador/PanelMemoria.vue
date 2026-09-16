<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import type { Memoria, PasoTraza } from '../../lib/ejemplosDepuracion';

const props = defineProps<{
    paso: PasoTraza | null;
    indice: number;
    memoria: Memoria;
    anterior: Memoria;
    esperando: boolean;
    confirmado: boolean;
    respuesta: string;
    feedback: { tipo: 'error' | 'correcto'; texto: string } | null;
}>();
defineEmits<{ confirmar: []; 'update:respuesta': [valor: string] }>();
const campo = ref<HTMLInputElement | null>(null);
const guardarCampo = (elemento: unknown) => { campo.value = elemento as HTMLInputElement | null; };
const escalares = computed(() => {
    const nombres = Object.keys(props.memoria).filter(nombre => !Array.isArray(props.memoria[nombre]));
    const pendiente = props.esperando ? props.paso?.reto?.variable : null;
    if (pendiente && !nombres.includes(pendiente)) nombres.push(pendiente);
    return nombres;
});
const arreglos = computed(() => Object.entries(props.memoria).filter((entrada): entrada is [string, number[]] => Array.isArray(entrada[1])));
const valorAnterior = (nombre: string) => Object.hasOwn(props.anterior, nombre) ? String(props.anterior[nombre]) : 'sin declarar';
const cambio = (nombre: string) => props.confirmado && props.memoria[nombre] !== props.anterior[nombre];

watch(() => [props.indice, props.esperando], async () => {
    if (!props.esperando) return;
    await nextTick();
    campo.value?.focus({ preventScroll: true });
}, { flush: 'post' });
</script>

<template>
    <section class="dv-panel panel-memoria" aria-labelledby="titulo-memoria">
        <header class="dv-panel-cabecera">
            <h2 id="titulo-memoria">Depuración visual</h2>
            <span class="dv-etiqueta">{{ paso ? `Paso ${indice + 1}` : 'Estado de memoria' }}</span>
        </header>

        <div v-if="!paso" class="vacio">
            <span class="simbolo" aria-hidden="true">{ }</span>
            <h3>Ve el código por dentro</h3>
            <p>Inicia o avanza un paso para descubrir cómo cambian las variables.</p>
            <span class="nota-vacio">En modo Predecir, completarás el nuevo valor antes de continuar.</span>
        </div>

        <div v-else class="cuerpo-memoria">
            <div class="paso-cabecera">
                <span class="rotulo">{{ esperando ? 'Antes de ejecutar la línea' : 'Después de ejecutar la línea' }} {{ paso.linea }}</span>
                <h3>{{ paso.titulo }}</h3>
                <p v-if="esperando">¿Qué valor tendrá <code>{{ paso.reto?.variable }}</code> después de esta instrucción?</p>
                <p v-else>{{ paso.explicacion }}</p>
            </div>

            <form @submit.prevent="$emit('confirmar')">
                <div class="variables">
                    <div v-for="nombre in escalares" :key="nombre" class="variable" :class="{ cambio: cambio(nombre), pregunta: esperando && paso.reto?.variable === nombre }">
                        <label v-if="esperando && paso.reto?.variable === nombre" :for="`valor-${nombre}`"><code>{{ nombre }}</code> <span>=</span></label>
                        <div v-else class="nombre-variable"><code>{{ nombre }}</code><span class="tipo">int</span></div>

                        <template v-if="esperando && paso.reto?.variable === nombre">
                            <input :id="`valor-${nombre}`" :ref="guardarCampo" :value="respuesta" type="text" inputmode="numeric" autocomplete="off" spellcheck="false" placeholder="?" :aria-invalid="feedback?.tipo === 'error'" :aria-describedby="feedback?.tipo === 'error' ? 'pista-paso feedback-paso' : 'pista-paso'" @input="$emit('update:respuesta', ($event.target as HTMLInputElement).value)" />
                            <p class="anterior">Paso previo: {{ nombre }} = {{ valorAnterior(nombre) }}</p>
                        </template>
                        <template v-else>
                            <strong class="valor">{{ memoria[nombre] }}</strong>
                            <span v-if="cambio(nombre)" class="anterior">Antes: {{ valorAnterior(nombre) }}</span>
                        </template>
                    </div>
                </div>

                <template v-if="esperando">
                    <p id="pista-paso" class="pista">{{ paso.reto?.pista }}</p>
                    <button type="submit" class="dv-boton dv-primario confirmar">Confirmar paso</button>
                </template>
                <p v-if="feedback" id="feedback-paso" class="feedback" :class="feedback.tipo" :role="feedback.tipo === 'error' ? 'alert' : 'status'">{{ feedback.texto }}</p>
            </form>

            <section v-for="[nombre, valores] in arreglos" :key="nombre" class="arreglo" :aria-label="`Arreglo ${nombre}`">
                <div class="nombre-arreglo"><code>{{ nombre }}</code><span class="tipo">int[{{ valores.length }}]</span></div>
                <div class="celdas">
                    <div v-for="(valor, i) in valores" :key="i" class="celda" :class="{ actual: paso.acceso?.arreglo === nombre && paso.acceso.indice === i }">
                        <strong>{{ valor }}</strong><span>[{{ i }}]</span>
                    </div>
                </div>
                <p v-if="paso.acceso?.arreglo === nombre" class="pista">Elemento consultado: {{ nombre }}[{{ paso.acceso.indice }}]</p>
            </section>

            <div v-if="paso.ciclo" class="ciclo">
                <span>Ciclo <code>{{ paso.ciclo.tipo }}</code> · Iteración {{ paso.ciclo.iteracion }}</span>
                <div><span>Última condición</span><code>{{ paso.ciclo.condicion }}</code><strong :class="{ falsa: !paso.ciclo.resultado }">{{ paso.ciclo.resultado ? 'Verdadera' : 'Falsa' }}</strong></div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.panel-memoria { overflow: hidden; }
.cuerpo-memoria { padding: 24px; }
.paso-cabecera { margin-bottom: 22px; }
.rotulo { display: block; color: #9b88ae; font-size: 11px; margin-bottom: 7px; }
.paso-cabecera h3 { font-size: 18px; font-weight: 600; color: #6343b4; }
.paso-cabecera p { margin-top: 9px; color: #8a7a99; font-size: 13px; line-height: 1.65; }
.variables { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.variable { min-width: 0; padding: 16px; border: 1px solid #e9e0f2; border-radius: 13px; background: #fdfbff; }
.variable.cambio { border-color: #d7c5f5; background: #f3edfc; }
.variable.pregunta { grid-column: 1 / -1; padding: 18px; border-color: #d9c5f7; background: #f7f2fe; }
.nombre-variable, .nombre-arreglo { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 7px; }
.nombre-variable code, .nombre-arreglo code, .variable label { color: #63546e; font-size: 13px; }
.variable label { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; }
.tipo { color: #a18fab; font-size: 10px; }
.valor { display: block; margin-top: 8px; color: #7050b8; font: 600 24px/1.25 ui-monospace, Consolas, monospace; }
.variable input { width: 100%; min-height: 47px; padding: 10px 14px; border: 1px solid #cbb5ef; border-radius: 10px; background: white; color: #6843b9; text-align: center; font: 18px/1.4 ui-monospace, Consolas, monospace; }
.variable input::placeholder { color: #b4a0c2; }
.anterior { display: block; margin-top: 9px; color: #9885a6; font-size: 11px; font-style: italic; }
.pista { margin-top: 12px; color: #9787a1; font-size: 12px; line-height: 1.6; }
.confirmar { width: 100%; margin-top: 18px; }
.feedback { margin-top: 14px; padding: 12px; border-radius: 10px; font-size: 12px; }
.feedback.error { background: #fff0f1; color: #a54b5c; }
.feedback.correcto { background: #eaf7ef; color: #468263; }
.arreglo { margin-top: 24px; padding-top: 20px; border-top: 1px solid #f0e8f6; }
.celdas { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 13px; }
.celda { min-width: 54px; overflow: hidden; border: 1px solid #e4d9ef; border-radius: 10px; background: #faf7fe; text-align: center; font-family: ui-monospace, Consolas, monospace; }
.celda strong { display: block; padding: 12px 16px; color: #766082; font-size: 16px; font-weight: 500; }
.celda span { display: block; padding: 4px; border-top: 1px solid #e8dff0; color: #a18bad; font-size: 10px; }
.celda.actual { border-color: #9b74df; background: #eae0fb; box-shadow: 0 0 0 1px #cbb5ef; }
.celda.actual strong { color: #7043bf; }
.ciclo { margin-top: 24px; padding: 16px; border-radius: 12px; background: #faf7fc; color: #8c779a; font-size: 11px; }
.ciclo > div { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
.ciclo strong { color: #448264; font-weight: 500; }
.ciclo strong.falsa { color: #b47849; }
.vacio { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 350px; padding: 30px; text-align: center; }
.simbolo { display: grid; place-items: center; width: 62px; height: 62px; margin-bottom: 18px; border-radius: 18px; background: #eee5fb; color: #a085ca; font: 24px ui-monospace, Consolas, monospace; }
.vacio h3 { color: #78579f; font-size: 17px; font-weight: 600; }
.vacio p { max-width: 280px; margin-top: 12px; color: #9685a2; font-size: 13px; line-height: 1.7; }
.nota-vacio { max-width: 265px; margin-top: 18px; color: #aa98b4; font-size: 11px; line-height: 1.7; }
@media (max-width: 480px) { .cuerpo-memoria { padding: 18px; } .variables { grid-template-columns: 1fr; } }
</style>
