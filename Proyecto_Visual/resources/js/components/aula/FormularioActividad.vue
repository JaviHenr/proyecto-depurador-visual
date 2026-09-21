<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import type { Actividad } from '../../types/aula';

const props = defineProps<{ idSeccion: number; actividad?: Actividad }>();
const emit = defineEmits<{ cerrar: [] }>();
const form = useForm({
    nombre_actividad: props.actividad?.nombre_actividad ?? '',
    descripcion_actividad: props.actividad?.descripcion_actividad ?? '',
    instrucciones: props.actividad?.instrucciones ?? '',
});

function guardar() {
    const url = `/secciones/${props.idSeccion}/actividades`;
    const opciones = { preserveScroll: true, onSuccess: () => emit('cerrar') };
    if (props.actividad) form.put(`${url}/${props.actividad.id_actividad}`, opciones);
    else form.post(url, opciones);
}
</script>

<template>
    <form class="panel" @submit.prevent="guardar">
        <h2>{{ actividad ? 'Editar actividad' : 'Crear actividad' }}</h2>
        <div v-if="Object.keys(form.errors).length" class="errores" role="alert">
            <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
        </div>
        <div class="campos">
            <label class="campo completo">Nombre de la actividad
                <input v-model="form.nombre_actividad" required maxlength="150" placeholder="Ej.: Suma de un arreglo con for" />
            </label>
            <label class="campo completo">Descripción (opcional)
                <textarea v-model="form.descripcion_actividad" maxlength="200" rows="3" />
                <span class="muted">{{ form.descripcion_actividad.length }}/200 caracteres</span>
            </label>
            <label class="campo completo">Instrucciones (opcional)
                <textarea v-model="form.instrucciones" maxlength="200" rows="4" placeholder="Indica qué debe observar el estudiante durante la ejecución." />
                <span class="muted">{{ form.instrucciones.length }}/200 caracteres</span>
            </label>
        </div>
        <div class="acciones">
            <button type="submit" class="btn primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : 'Guardar actividad' }}</button>
            <button type="button" class="btn" :disabled="form.processing" @click="emit('cerrar')">Cancelar</button>
        </div>
    </form>
</template>
