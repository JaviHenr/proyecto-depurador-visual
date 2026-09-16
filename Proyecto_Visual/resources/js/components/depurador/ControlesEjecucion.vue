<script setup lang="ts">
defineProps<{
    estado: string;
    indice: number;
    total: number;
    ejecutados: number;
    reproduciendo: boolean;
    esperando: boolean;
    puedeIniciar: boolean;
    puedeAvanzar: boolean;
    intervalo: number;
}>();

defineEmits<{
    iniciar: [];
    pausar: [];
    retroceder: [];
    avanzar: [];
    reiniciar: [];
    'update:intervalo': [valor: number];
}>();
</script>

<template>
    <section class="dv-panel controles" aria-label="Controles de ejecución">
        <div class="fila-controles">
            <div class="botones">
                <button type="button" class="dv-boton dv-primario" :disabled="!puedeIniciar" @click="$emit('iniciar')">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6 3.5v13l10-6.5z" /></svg>
                    {{ indice < 0 ? 'Iniciar' : 'Reanudar' }}
                </button>
                <button type="button" class="dv-boton" :disabled="!reproduciendo" @click="$emit('pausar')">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5 4h3v12H5zm7 0h3v12h-3z" /></svg>
                    Pausar
                </button>
                <button type="button" class="dv-boton" :disabled="indice < 0" @click="$emit('retroceder')">
                    <span aria-hidden="true">←</span> Retroceder
                </button>
                <button type="button" class="dv-boton" :disabled="!puedeAvanzar" @click="$emit('avanzar')">
                    Avanzar <span aria-hidden="true">→</span>
                </button>
                <button type="button" class="dv-boton" :disabled="indice < 0" @click="$emit('reiniciar')">Reiniciar</button>
            </div>
            <label class="velocidad">Ritmo
                <select :value="intervalo" @change="$emit('update:intervalo', Number(($event.target as HTMLSelectElement).value))">
                    <option :value="2200">Lento</option>
                    <option :value="1400">Normal</option>
                    <option :value="700">Rápido</option>
                </select>
            </label>
        </div>
        <div class="estado-ejecucion">
            <span class="estado" :class="{ esperando, reproduciendo }" role="status"><span class="punto" aria-hidden="true" />{{ estado }}</span>
            <span>Paso {{ Math.max(0, indice + 1) }} de {{ total }}</span>
        </div>
        <progress :value="ejecutados" :max="Math.max(1, total)" aria-label="Pasos ejecutados" />
    </section>
</template>

<style scoped>
.controles { padding: 20px 22px; margin-bottom: 22px; }
.fila-controles, .botones, .velocidad, .estado-ejecucion, .estado { display: flex; align-items: center; }
.fila-controles { justify-content: space-between; flex-wrap: wrap; gap: 16px; }
.botones { gap: 8px; flex-wrap: wrap; }
.botones svg { width: 14px; height: 14px; }
.velocidad { gap: 9px; color: #82738f; font-size: 12px; }
.velocidad select { padding: 7px 10px; border: 1px solid #e5ddef; border-radius: 9px; color: #60516e; background: #fcfaff; }
.estado-ejecucion { justify-content: space-between; flex-wrap: wrap; gap: 8px; margin: 18px 0 9px; color: #8a7d96; font-size: 12px; }
.estado { gap: 7px; }
.estado.esperando { color: #91671f; }
.estado.reproduciendo { color: #6841cc; }
.punto { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
progress { display: block; width: 100%; height: 5px; appearance: none; border: 0; border-radius: 8px; overflow: hidden; background: #f0eaf7; color: #9877e7; }
progress::-webkit-progress-bar { background: #f0eaf7; }
progress::-webkit-progress-value { background: #9877e7; border-radius: 8px; }
progress::-moz-progress-bar { background: #9877e7; border-radius: 8px; }
@media (max-width: 480px) { .controles { padding: 18px; } }
</style>
