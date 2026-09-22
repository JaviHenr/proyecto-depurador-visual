<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ idSeccion: number }>();
const form = useForm({ email_estudiante: '' });

function agregar() {
    form.post(`/secciones/${props.idSeccion}/estudiantes`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form class="formulario-estudiante" @submit.prevent="agregar">
        <label class="campo">Correo del estudiante
            <input
                v-model.trim="form.email_estudiante"
                type="email"
                required
                maxlength="200"
                autocomplete="off"
                placeholder="estudiante@ejemplo.com"
            />
        </label>
        <button type="submit" class="btn primary" :disabled="form.processing">
            {{ form.processing ? 'Agregando…' : '+ Agregar estudiante' }}
        </button>
        <p v-if="form.errors.email_estudiante" class="error-campo" role="alert">
            {{ form.errors.email_estudiante }}
        </p>
    </form>
</template>

<style scoped>
.formulario-estudiante { display: grid; grid-template-columns: minmax(220px, 1fr) auto; align-items: end; gap: 10px; margin: 16px 0 20px; }
.error-campo { grid-column: 1 / -1; margin: 0; color: #a44d5f; font-size: 12px; }
@media(max-width:600px) { .formulario-estudiante { grid-template-columns: minmax(0, 1fr); } }
</style>
