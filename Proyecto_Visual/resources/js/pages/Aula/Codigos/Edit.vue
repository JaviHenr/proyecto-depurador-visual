<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AulaLayout from '../../../layouts/AulaLayout.vue';
import type { ActividadOpcion, Codigo } from '../../../types/aula';

const props = defineProps<{
    codigo: Codigo | null;
    actividades: ActividadOpcion[];
    inicial: { id_actividad: number | null };
}>();
const form = useForm({
    nombre_codigo: props.codigo?.nombre_codigo ?? '',
    nombre_archivo: props.codigo?.nombre_archivo ?? 'Main.java',
    formato: props.codigo?.formato ?? 'java',
    contenido_codigo: props.codigo?.contenido_codigo ?? '',
    id_actividad: props.codigo?.id_actividad ?? props.inicial.id_actividad,
    huella: props.codigo?.huella ?? '',
});
const fueraDeOpciones = computed(() => props.codigo?.id_actividad != null && !props.actividades.some(a => a.id_actividad === props.codigo?.id_actividad));
function guardar() {
    const opciones = {
        preserveScroll: true,
        onSuccess: (page: { props: Record<string, unknown> }) => {
            // La respuesta redirige al editor con la huella recién calculada.
            const guardado = page.props.codigo as Codigo | null | undefined;
            if (guardado) {
                form.nombre_codigo = guardado.nombre_codigo ?? '';
                form.nombre_archivo = guardado.nombre_archivo ?? '';
                form.formato = guardado.formato ?? '';
                form.contenido_codigo = guardado.contenido_codigo ?? '';
                form.id_actividad = guardado.id_actividad;
                form.huella = guardado.huella;
                form.defaults();
            }
        },
    };
    if (props.codigo) form.put(`/codigos/${props.codigo.id_codigo}`, opciones);
    else form.post('/codigos', opciones);
}
</script>

<template>
    <AulaLayout :titulo="codigo ? 'Editar código' : 'Nuevo código'" descripcion="El código se guarda en tu cuenta para que puedas retomarlo después." activo="codigos">
        <div class="barra">
            <Link href="/codigos">← Mis códigos</Link>
            <span class="muted">{{ form.isDirty ? 'Cambios sin guardar' : (codigo ? 'Código guardado' : 'Completa los datos') }}</span>
        </div>
        <form class="panel" @submit.prevent="guardar">
            <div v-if="Object.keys(form.errors).length" class="errores" role="alert">
                <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
            </div>
            <div class="campos">
                <label class="campo">Nombre del código
                    <input v-model="form.nombre_codigo" required maxlength="150" placeholder="Ej.: Suma con un ciclo for" />
                </label>
                <label class="campo">Actividad
                    <select v-model="form.id_actividad">
                        <option :value="null">Código personal, sin actividad</option>
                        <option v-if="fueraDeOpciones" :value="codigo?.id_actividad">Actividad vinculada #{{ codigo?.id_actividad }}</option>
                        <option v-for="actividad in actividades" :key="actividad.id_actividad" :value="actividad.id_actividad">{{ actividad.nombre_actividad || `Actividad #${actividad.id_actividad}` }}</option>
                    </select>
                </label>
                <label class="campo">Nombre del archivo
                    <input v-model="form.nombre_archivo" required maxlength="200" placeholder="Main.java" />
                </label>
                <label class="campo">Formato
                    <input v-model="form.formato" required maxlength="20" list="formatos-codigo" placeholder="java" />
                    <datalist id="formatos-codigo"><option value="java" /><option value="py" /><option value="js" /><option value="txt" /></datalist>
                </label>
                <label class="campo completo">Código fuente
                    <textarea v-model="form.contenido_codigo" class="editor" required maxlength="100000" rows="18" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off" placeholder="Escribe tu código aquí…" />
                </label>
            </div>
            <div class="acciones entre">
                <button type="submit" class="btn primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : 'Guardar y abrir depurador' }}</button>
                <span class="muted">{{ form.contenido_codigo.split('\n').length }} líneas · {{ form.contenido_codigo.length }} caracteres</span>
            </div>
            <p v-if="codigo?.fecha_creacion_codigo" class="muted" style="margin-top: 16px">Creado el {{ codigo.fecha_creacion_codigo }}.</p>
        </form>
    </AulaLayout>
</template>
