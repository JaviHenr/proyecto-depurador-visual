<script setup lang="ts">
import { insertarIndentacion } from '../../../lib/indentacionCodigo';
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
    nombre_archivo: props.codigo?.nombre_archivo ?? 'programa.py',
    formato: 'py',
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
        <form class="panel editor-programa" @submit.prevent="guardar">
            <div v-if="Object.keys(form.errors).length" class="errores" role="alert">
                <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
            </div>
            <div class="editor-distribucion">
                <div class="editor-area">
                    <label class="campo editor-fuente">
                        <span class="editor-cabecera"><span>Código fuente</span><span class="etiqueta" aria-hidden="true">Python</span></span>
                        <textarea :disabled="form.processing" v-model="form.contenido_codigo" @keydown="insertarIndentacion" class="editor" required maxlength="5000" rows="18" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off" placeholder="Escribe tu programa Python aquí…" />
                    </label>
                    <div class="editor-pie"><span class="muted">{{ form.contenido_codigo.split('\n').length }} líneas · {{ form.contenido_codigo.length }} caracteres</span></div>
                </div>
                <section class="editor-datos" aria-labelledby="datos-programa">
                    <h2 id="datos-programa">Datos del programa</h2>
                    <label class="campo">Nombre del código
                        <input :disabled="form.processing" v-model="form.nombre_codigo" required maxlength="150" placeholder="Ej.: Suma con un ciclo for" />
                    </label>
                    <label class="campo">Actividad
                        <select :disabled="form.processing" v-model="form.id_actividad">
                            <option :value="null">Código personal, sin actividad</option>
                            <option v-if="fueraDeOpciones" :value="codigo?.id_actividad">Actividad vinculada #{{ codigo?.id_actividad }}</option>
                            <option v-for="actividad in actividades" :key="actividad.id_actividad" :value="actividad.id_actividad">{{ actividad.nombre_actividad || `Actividad #${actividad.id_actividad}` }}</option>
                        </select>
                    </label>
                    <label class="campo">Nombre del archivo
                        <input :disabled="form.processing" v-model="form.nombre_archivo" required maxlength="200" placeholder="programa.py" />
                    </label>
                    <label class="campo">Formato
                        <input :disabled="form.processing" v-model="form.formato" readonly aria-label="Formato Python" />
                    </label>
                    <button type="submit" class="btn primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : 'Guardar y abrir depurador' }}</button>
                    <p v-if="codigo?.fecha_creacion_codigo" class="muted">Creado el {{ codigo.fecha_creacion_codigo }}.</p>
                </section>
            </div>
        </form>
    </AulaLayout>
</template>

<style scoped>
.editor-distribucion {
    display: grid;
    grid-template-columns: minmax(0, 1.9fr) minmax(240px, 1fr);
    align-items: start;
    gap: clamp(16px, 2.5vw, 30px);
}
.editor-area { min-width: 0; }
.editor-programa .editor-fuente { gap: 0; }
.editor-cabecera, .editor-pie {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 14px;
    border: 1px solid var(--editor-borde);
    background: var(--editor-superficie);
}
.editor-programa {
    --editor-borde: #E1D8F1;
    --editor-superficie: #F1EDFB;
    --editor-texto: #1E1B38;
}
:global(html.dark) .editor-programa {
    --editor-borde: #3B315B;
    --editor-superficie: #2C2053;
    --editor-texto: #FFFFFF;
}
.editor-cabecera { border-radius: 12px 12px 0 0; color: var(--editor-texto); font-weight: 700; }
.editor-programa .editor {
    display: block;
    border: 1px solid var(--editor-borde);
    border-block-width: 0;
    border-radius: 0;
    padding: 16px;
    min-height: 280px;
}
.editor-pie { border-radius: 0 0 12px 12px; font-size: 12px; }
.editor-datos {
    display: grid;
    gap: 14px;
    min-width: 0;
    padding: 0 0 0 20px;
    border-inline-start: 1px solid var(--editor-borde);
}
.editor-datos .campo { color: var(--editor-texto); }
.editor-programa .editor-datos h2 { margin: 0; font-size: 16px; font-weight: 700; }
.editor-programa .editor-datos .primary { min-height: 42px; margin-top: 4px; background: #4F26C6; border-color: #4F26C6; }
@media (max-width: 970px) {
    .editor-distribucion { grid-template-columns: minmax(0, 1fr); }
    .editor-datos { border-inline-start: 0; border-block-start: 1px solid var(--editor-borde); padding: 16px 0 0; }
}
</style>
