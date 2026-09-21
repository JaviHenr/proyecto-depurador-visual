<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = defineProps<{ codigo: string; archivo: string; linea: number | null; esperando: boolean }>();
const contenedor = ref<HTMLElement | null>(null);

// Resaltado visual sencillo; no es un parser ni modifica el programa.
function tokens(texto: string) {
    const patron = /("(?:\\.|[^"\\])*"|\/\/.*$|\b(?:public|class|static|void|int|for|while|boolean|String)\b|\b\d+\b)/g;
    const partes: { texto: string; clase: string }[] = [];
    let anterior = 0;
    for (const coincidencia of texto.matchAll(patron)) {
        const posicion = coincidencia.index ?? 0;
        if (posicion > anterior) partes.push({ texto: texto.slice(anterior, posicion), clase: '' });
        const valor = coincidencia[0];
        partes.push({ texto: valor, clase: valor.startsWith('//') ? 'comentario' : valor.startsWith('"') ? 'cadena' : /^\d/.test(valor) ? 'numero' : 'palabra' });
        anterior = posicion + valor.length;
    }
    partes.push({ texto: texto.slice(anterior) || (texto.length ? '' : ' '), clase: '' });
    return partes;
}

const lineas = computed(() => props.codigo.split('\n').map(tokens));

watch(() => [props.linea, props.codigo], () => {
    const visor = contenedor.value;
    const actual = visor?.querySelector<HTMLElement>(`[data-linea="${props.linea}"]`);
    if (!visor || !actual) return;
    const rect = actual.getBoundingClientRect();
    const borde = visor.getBoundingClientRect();
    if (rect.top < borde.top) visor.scrollTop -= borde.top - rect.top;
    else if (rect.bottom > borde.bottom) visor.scrollTop += rect.bottom - borde.bottom;
}, { flush: 'post' });
</script>

<template>
    <section class="dv-panel visor" aria-labelledby="titulo-codigo">
        <header class="dv-panel-cabecera">
            <h2 id="titulo-codigo">Código fuente</h2>
            <span class="dv-etiqueta">{{ archivo }}</span>
        </header>
        <div ref="contenedor" class="codigo" role="region" aria-label="Código Java del ejemplo, solo lectura" tabindex="0">
            <div v-for="(partes, i) in lineas" :key="i" class="linea" :class="{ activa: linea === i + 1, pendiente: esperando && linea === i + 1 }" :data-linea="i + 1" :aria-current="linea === i + 1 ? 'step' : undefined">
                <span class="numero-linea" aria-hidden="true">{{ i + 1 }}</span>
                <code><span v-for="(parte, j) in partes" :key="j" :class="parte.clase">{{ parte.texto }}</span></code>
            </div>
        </div>
        <footer>
            <span>{{ linea ? `Línea ${linea} resaltada` : 'Inicia para seguir la ejecución' }}</span>
            <span>{{ lineas.length }} líneas · Solo lectura</span>
        </footer>
    </section>
</template>

<style scoped>
.visor { overflow: hidden; }
.codigo { padding: 18px 0; max-height: 470px; min-height: 310px; overflow: auto; background: #fcfaff; }
.linea { display: flex; width: max-content; min-width: 100%; border-left: 3px solid transparent; font: 12px/29px ui-monospace, SFMono-Regular, Consolas, monospace; }
.numero-linea { flex-shrink: 0; width: 43px; padding-right: 13px; color: #aba0b5; text-align: right; user-select: none; }
.linea code { display: block; padding: 0 20px 0 8px; white-space: pre; color: #554960; font: inherit; tab-size: 4; }
.activa { background: #ede4fc; border-left-color: #9370e6; }
.activa .numero-linea { color: #7851c9; font-weight: 700; }
.pendiente { background: #f5eefe; }
.palabra { color: #8854ae; }
.cadena { color: #497d69; }
.numero { color: #b47b3d; }
.comentario { color: #94859e; font-style: italic; }
footer { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 13px 20px; border-top: 1px solid #eee6f5; color: #94869f; font-size: 11px; }
</style>
