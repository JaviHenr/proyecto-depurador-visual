import { computed, onScopeDispose, ref, watch, type Ref } from 'vue';
import type { EjemploDepuracion, Memoria } from '../lib/ejemplosDepuracion';
import { valoresIguales } from '../lib/trazaSemantica';

export function useDepurador(ejemplo: Ref<EjemploDepuracion>) {
    const indice = ref(-1);
    const confirmado = ref(false);
    const reproduciendo = ref(false);
    const modo = ref<'predecir' | 'observar'>('predecir');
    const intervalo = ref(1400);
    const respuesta = ref('');
    const feedback = ref<{ tipo: 'error' | 'correcto'; texto: string } | null>(null);
    let temporizador: ReturnType<typeof setTimeout> | undefined;

    const paso = computed(() => ejemplo.value.pasos[indice.value] ?? null);
    const anterior = computed<Memoria>(() => ejemplo.value.pasos[indice.value - 1]?.memoria ?? {});
    const memoria = computed<Memoria>(() => confirmado.value && paso.value ? paso.value.memoria : anterior.value);
    const salida = computed(() => (confirmado.value ? paso.value?.salida : ejemplo.value.pasos[indice.value - 1]?.salida) ?? []);
    const esperando = computed(() => !!paso.value?.reto && modo.value === 'predecir' && !confirmado.value);
    const terminado = computed(() => ejemplo.value.pasos.length > 0 && indice.value === ejemplo.value.pasos.length - 1 && confirmado.value);
    const estado = computed(() => terminado.value ? 'Finalizado' : esperando.value ? 'Esperando tu respuesta' : reproduciendo.value ? 'En ejecución' : indice.value < 0 ? 'Listo para iniciar' : 'En pausa');
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

    function limpiarRespuesta() {
        respuesta.value = '';
        feedback.value = null;
    }

    function entrarAlPaso(nuevoIndice: number, recordar = false) {
        indice.value = nuevoIndice;
        limpiarRespuesta();
        // Al retroceder se recupera el estado que ya se había alcanzado.
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

    function confirmar() {
        if (!esperando.value || !paso.value?.reto) return;
        const texto = respuesta.value.trim();
        const esperado = paso.value.memoria[paso.value.reto.variable];
        let valor: unknown;
        try {
            if (typeof esperado === 'number') {
                if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/.test(texto)) throw new Error();
                valor = Number(texto);
                if (!Number.isFinite(valor) || (Number.isInteger(esperado) && !Number.isSafeInteger(valor))) throw new Error();
            } else if (typeof esperado === 'boolean') {
                const booleanos: Record<string, boolean> = { true: true, false: false, verdadero: true, falso: false };
                if (!Object.hasOwn(booleanos, texto.toLowerCase())) throw new Error();
                valor = booleanos[texto.toLowerCase()];
            } else if (esperado === null) {
                if (!['null', 'none'].includes(texto.toLowerCase())) throw new Error();
                valor = null;
            } else {
                if (!texto) throw new Error();
                valor = texto.startsWith('"') ? JSON.parse(texto) : texto;
            }
        } catch {
            feedback.value = { tipo: 'error', texto: typeof esperado === 'number' ? 'Escribe un número válido para confirmar el paso.' : 'Escribe un valor válido: texto, true/false o null, según la variable.' };
            return;
        }
        if (!valoresIguales(valor, esperado)) {
            feedback.value = { tipo: 'error', texto: 'Todavía no coincide. Revisa el valor anterior y la operación resaltada.' };
            return;
        }
        confirmado.value = true;
        feedback.value = { tipo: 'correcto', texto: '¡Correcto! La memoria se ha actualizado. Puedes avanzar o reanudar.' };
        // La confirmación conserva el resultado visible; nunca avanza por sorpresa.
        pausar();
    }

    watch(ejemplo, reiniciar, { flush: 'sync' });
    watch(modo, () => {
        pausar();
        limpiarRespuesta();
        if (modo.value === 'observar' && paso.value) confirmado.value = true;
    }, { flush: 'sync' });
    watch(intervalo, () => { if (reproduciendo.value) programar(); }, { flush: 'sync' });
    onScopeDispose(pausar);

    return {
        indice, confirmado, reproduciendo, modo, intervalo, respuesta, feedback,
        paso, anterior, memoria, salida, esperando, terminado, estado,
        puedeIniciar, puedeAvanzar, iniciar, pausar, avanzar, retroceder, reiniciar, confirmar,
    };
}
