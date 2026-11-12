import { computed, onScopeDispose, ref, watch, type Ref } from 'vue';
import type { EjemploDepuracion, Memoria } from '../lib/ejemplosDepuracion';
import type { ValidarPaso } from '../lib/motorSemantico';

export interface FeedbackPaso {
    tipo: 'error' | 'correcto';
    texto: string;
    valorEsperado?: number | boolean;
}

export function useDepurador(ejemplo: Ref<EjemploDepuracion>, validar: ValidarPaso) {
    const indice = ref(-1);
    const confirmado = ref(false);
    const reproduciendo = ref(false);
    const preferencia = ref<'predecir' | 'observar'>('predecir');
    const puedePredecir = computed(() => !!ejemplo.value.referenciaTraza && ejemplo.value.pasos.some(paso => !!paso.reto));
    const modo = computed({
        get: () => puedePredecir.value ? preferencia.value : 'observar' as const,
        set: (valor: 'predecir' | 'observar') => { preferencia.value = valor; },
    });
    const intervalo = ref(1400);
    const respuesta = ref('');
    const feedback = ref<FeedbackPaso | null>(null);
    const validando = ref(false);
    let temporizador: ReturnType<typeof setTimeout> | undefined;
    let solicitud: AbortController | null = null;

    const paso = computed(() => ejemplo.value.pasos[indice.value] ?? null);
    const anterior = computed<Memoria>(() => ejemplo.value.pasos[indice.value - 1]?.memoria ?? {});
    const memoria = computed<Memoria>(() => confirmado.value && paso.value ? paso.value.memoria : anterior.value);
    const salida = computed(() => (confirmado.value ? paso.value?.salida : ejemplo.value.pasos[indice.value - 1]?.salida) ?? []);
    const esperando = computed(() => !!paso.value?.reto && modo.value === 'predecir' && !confirmado.value);
    const terminado = computed(() => ejemplo.value.pasos.length > 0 && indice.value === ejemplo.value.pasos.length - 1 && confirmado.value);
    const estado = computed(() => validando.value ? 'Validando respuesta…' : terminado.value ? 'Finalizado' : esperando.value ? 'Esperando tu respuesta' : reproduciendo.value ? 'En ejecución' : indice.value < 0 ? 'Listo para iniciar' : 'En pausa');
    const puedeIniciar = computed(() => ejemplo.value.pasos.length > 0 && !reproduciendo.value && !esperando.value && !terminado.value);
    const puedeAvanzar = computed(() => ejemplo.value.pasos.length > 0 && !esperando.value && !terminado.value);

    function cancelarTemporizador() {
        if (temporizador !== undefined) clearTimeout(temporizador);
        temporizador = undefined;
    }

    function pausar() {
        cancelarTemporizador();
        reproduciendo.value = false;
    }

    function cancelarValidacion() {
        solicitud?.abort();
        solicitud = null;
        validando.value = false;
    }

    function limpiarRespuesta() {
        cancelarValidacion();
        respuesta.value = '';
        feedback.value = null;
    }

    function entrarAlPaso(nuevoIndice: number, recordar = false) {
        indice.value = nuevoIndice;
        limpiarRespuesta();
        confirmado.value = recordar || modo.value === 'observar' || !paso.value?.reto;
        if (esperando.value || terminado.value) pausar();
    }

    function programar() {
        cancelarTemporizador();
        if (!reproduciendo.value || esperando.value || terminado.value) return;
        temporizador = setTimeout(() => {
            temporizador = undefined;
            if (!reproduciendo.value) return;
            entrarAlPaso(indice.value + 1);
            programar();
        }, intervalo.value);
    }

    function iniciar() {
        if (!puedeIniciar.value) return;
        reproduciendo.value = true;
        if (indice.value === -1) entrarAlPaso(0);
        programar();
    }

    function avanzar() {
        if (!puedeAvanzar.value) return;
        pausar();
        entrarAlPaso(indice.value + 1);
    }

    function retroceder() {
        if (indice.value < 0) return;
        pausar();
        if (indice.value === 0) reiniciar();
        else entrarAlPaso(indice.value - 1, true);
    }

    function reiniciar() {
        pausar();
        indice.value = -1;
        confirmado.value = false;
        limpiarRespuesta();
    }

    async function confirmar() {
        const actual = paso.value;
        const traza = ejemplo.value.referenciaTraza;
        if (!esperando.value || validando.value || !traza || !actual?.reto || actual.numeroPaso === undefined) return;

        const texto = respuesta.value.trim();
        let valor: number | boolean;
        if (actual.reto.tipo === 'condicion') {
            if (texto !== 'true' && texto !== 'false') {
                feedback.value = { tipo: 'error', texto: 'Selecciona verdadero o falso.' };
                return;
            }
            valor = texto === 'true';
        } else {
            valor = Number(texto);
            if (!/^[+-]?\d+$/.test(texto) || !Number.isSafeInteger(valor)) {
                feedback.value = { tipo: 'error', texto: 'Escribe un número entero válido.' };
                return;
            }
        }

        const control = new AbortController();
        solicitud = control;
        validando.value = true;
        feedback.value = null;
        try {
            const resultado = await validar(traza, actual.numeroPaso, valor, control.signal);
            if (solicitud !== control) return;
            confirmado.value = resultado.correcto;
            feedback.value = {
                tipo: resultado.correcto ? 'correcto' : 'error',
                texto: resultado.mensaje,
                valorEsperado: resultado.valorEsperado,
            };
        } catch (error) {
            if (solicitud !== control) return;
            feedback.value = {
                tipo: 'error',
                texto: error instanceof Error && !(error instanceof TypeError)
                    ? error.message : 'No se pudo validar la respuesta. Vuelve a confirmar el paso.',
            };
        } finally {
            if (solicitud === control) {
                solicitud = null;
                validando.value = false;
            }
        }
    }

    watch(ejemplo, reiniciar, { flush: 'sync' });
    watch(modo, valor => {
        if (valor === 'predecir') {
            reiniciar();
        } else {
            pausar();
            limpiarRespuesta();
            if (paso.value) confirmado.value = true;
        }
    }, { flush: 'sync' });
    watch(intervalo, () => { if (reproduciendo.value) programar(); }, { flush: 'sync' });
    onScopeDispose(() => { pausar(); cancelarValidacion(); });

    return {
        indice, confirmado, reproduciendo, modo, puedePredecir, intervalo, respuesta, feedback, validando,
        paso, anterior, memoria, salida, esperando, terminado, estado,
        puedeIniciar, puedeAvanzar, iniciar, pausar, avanzar, retroceder, reiniciar, confirmar,
    };
}
