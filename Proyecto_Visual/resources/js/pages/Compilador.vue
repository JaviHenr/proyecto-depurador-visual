<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';



type Valor = string | number | boolean | null;

interface Variable {
    nombre: string;
    tipo: string;
    valor: Valor;
}

interface Arreglo {
    nombre: string;
    valores: Valor[];
    indiceActual: number | null;
}

// Contenido inicial del editor. Puedes sustituirlo por el código que quieras mostrar.
const codigo = ref(`public class Main {
    public static void main(String[] args) {
        int[] numeros = {2, 4, 6};
        int suma = 0;

        for (int i = 0; i < numeros.length; i++) {
            suma += numeros[i];
        }

        System.out.println(suma);
    }
}`);

const lineas = computed(() => codigo.value.split('\n'));
const numerosEditor = ref<HTMLElement | null>(null);
const desplazamiento = ref(0);

// Estos estados se actualizarán cuando implementes la ejecución.
const estado = ref('Sin ejecución');
const lineaActual = ref<number | null>(null); // Numeración desde 1.
const pasoActual = ref<number | null>(null);
const variables = ref<Variable[]>([]);
const arreglos = ref<Arreglo[]>([]);
const ciclo = ref<{ tipo: string; iteracion: number; condicion: string } | null>(null);
const salida = ref('');

function sincronizarEditor(event: Event) {
    const editor = event.target as HTMLTextAreaElement;
    desplazamiento.value = editor.scrollTop;
    if (numerosEditor.value) numerosEditor.value.scrollTop = editor.scrollTop;
}

function iniciar() {
    // TODO: enviar codigo.value al motor y recibir los estados de ejecución.
}

function pausar() {
    // TODO: detener el avance automático de los estados.
}

function retroceder() {
    // TODO: recuperar el estado anterior y actualizar los paneles.
}

function avanzar() {
    // TODO: mostrar el siguiente estado y actualizar lineaActual, variables y arreglos.
}

function reiniciar() {
    // TODO: volver al primer estado de la ejecución.
}
</script>

<template>
    <Head title="Depurador Visual" />

    <div class="flex min-h-screen bg-[#FAF7F2] text-[#1C1917]">
        <BarraLateral pestana-activa="depurador" />

        <div class="flex-1 min-w-0 flex flex-col">
            <!-- Encabezado -->
            <header class="border-b border-[#E7E5E4] bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight">Depurador Visual</h1>
                        <p class="mt-1 text-xs text-[#78716C]">Comprensión de estructuras iterativas</p>
                    </div>
                    <span class="rounded-sm border border-[#E7E5E4] bg-[#FAFAF9] px-3 py-1 text-xs text-[#57534E]">Java</span>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl space-y-4 px-4 py-6 sm:px-6">
                <!-- Controles: funciones pendientes de implementar -->
                <section aria-label="Controles de ejecución" class="flex flex-wrap items-center justify-between gap-3 rounded-sm border border-[#E7E5E4] bg-white p-3">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="control control-principal" @click="iniciar">
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path d="M6 3.5v13l10-6.5z" /></svg>
                            Iniciar
                        </button>
                        <button type="button" class="control" @click="pausar">
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path d="M5 4h3v12H5zm7 0h3v12h-3z" /></svg>
                            Pausar
                        </button>
                        <button type="button" class="control" @click="retroceder">
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 10H4m5-5-5 5 5 5" /></svg>
                            Retroceder
                        </button>
                        <button type="button" class="control" @click="avanzar">
                            Avanzar
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10h12m-5-5 5 5-5 5" /></svg>
                        </button>
                        <button type="button" class="control" @click="reiniciar">Reiniciar</button>
                    </div>
                    <span class="flex items-center gap-2 px-1 text-xs text-[#78716C]" role="status">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#A8A29E]" aria-hidden="true" />
                        {{ estado }}
                    </span>
                </section>

                <!-- Dos columnas en escritorio; una columna en pantallas pequeñas -->
                <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
                    <!-- Izquierda: código y consola -->
                    <div class="min-w-0 space-y-4">
                        <section class="overflow-hidden rounded-sm border border-[#E7E5E4] bg-white">
                            <div class="flex items-center justify-between border-b border-[#E7E5E4] px-4 py-3">
                                <h2 class="text-xs font-semibold">Código fuente</h2>
                                <span class="text-xs text-[#78716C]">Main.java</span>
                            </div>

                            <div class="editor-contenedor">
                                <div ref="numerosEditor" class="editor-numeros" aria-hidden="true">
                                    <div v-for="(_, indice) in lineas" :key="indice" :class="{ 'numero-activo': lineaActual === indice + 1 }">
                                        {{ indice + 1 }}
                                    </div>
                                </div>
                                <div class="editor-texto">
                                    <!-- El resaltado se activa al asignar una línea a lineaActual. -->
                                    <div v-if="lineaActual !== null" class="linea-resaltada" aria-hidden="true"
                                        :style="{ top: `${12 + (lineaActual - 1) * 24 - desplazamiento}px` }" />
                                    <textarea v-model="codigo" aria-label="Código fuente Java" spellcheck="false" autocapitalize="off"
                                        autocomplete="off" wrap="off" @scroll="sincronizarEditor" />
                                </div>
                            </div>

                            <div class="flex justify-between gap-3 border-t border-[#E7E5E4] px-4 py-2 text-[11px] text-[#78716C]">
                                <span>{{ lineas.length }} líneas</span>
                                <span>Línea actual: {{ lineaActual ?? '—' }}</span>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-sm border border-[#E7E5E4] bg-white">
                            <h2 class="border-b border-[#E7E5E4] px-4 py-3 text-xs font-semibold">Consola de salida</h2>
                            <pre class="min-h-24 max-h-48 overflow-auto whitespace-pre-wrap break-words bg-[#FAFAF9] p-4 font-mono text-xs leading-6 text-[#57534E]">{{ salida || 'La salida del programa aparecerá aquí.' }}</pre>
                        </section>
                    </div>

                    <!-- Derecha: espacio preparado para la depuración visual -->
                    <section class="min-w-0 overflow-hidden rounded-sm border border-[#E7E5E4] bg-white">
                        <div class="flex items-center justify-between border-b border-[#E7E5E4] px-4 py-3">
                            <h2 class="text-xs font-semibold">Depuración visual</h2>
                            <span class="text-[11px] text-[#78716C]">Paso: {{ pasoActual ?? '—' }}</span>
                        </div>

                        
                    </section>
                </div>
            </main>
        </div>
    </div>
</template>