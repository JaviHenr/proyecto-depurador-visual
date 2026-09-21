<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import type { Seccion } from '../../types/aula';

const props = defineProps<{ seccion?: Seccion }>();
const emit = defineEmits<{ cerrar: [] }>();
const form = useForm({
    nombre_seccion: props.seccion?.nombre_seccion ?? '',
    descripcion_seccion: props.seccion?.descripcion_seccion ?? '',
});

function guardar() {
    const opciones = { preserveScroll: true, onSuccess: () => emit('cerrar') };
    if (props.seccion) form.put(`/secciones/${props.seccion.id_seccion}`, opciones);
    else form.post('/secciones', opciones);
}
</script>

<template>
    <form class="panel" @submit.prevent="guardar">
        <h2>{{ seccion ? 'Editar sección' : 'Crear sección' }}</h2>
        <div v-if="Object.keys(form.errors).length" class="errores" role="alert">
            <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
        </div>
        <div class="campos">
            <label class="campo completo">Nombre de la sección
                <input v-model="form.nombre_seccion" required maxlength="200" placeholder="Ej.: Programación I · Sección A" />
            </label>
            <label class="campo completo">Descripción (opcional)
                <textarea v-model="form.descripcion_seccion" maxlength="300" rows="3" placeholder="Describe el propósito de esta sección." />
            </label>
        </div>
        <div class="acciones">
            <button type="submit" class="btn primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : 'Guardar sección' }}</button>
            <button type="button" class="btn" :disabled="form.processing" @click="emit('cerrar')">Cancelar</button>
        </div>
    </form>
</template>
