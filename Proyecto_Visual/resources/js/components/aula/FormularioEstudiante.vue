<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store } from '../../actions/App/Http/Controllers/Aula/EstudianteController';
import { show } from '../../actions/App/Http/Controllers/Aula/SeccionController';
import type { Estudiante, Pagina } from '../../types/aula';

const props = defineProps<{
    idSeccion: number;
    disponibles: Pagina<Pick<Estudiante, 'id_usuario' | 'nombre_usuario' | 'email'>>;
    busqueda: string;
    filtroActividad: string;
}>();
const buscar = useForm({ buscar_estudiante: props.busqueda, q: props.filtroActividad });
const form = useForm({ id_estudiante: 0 });

watch(() => [props.busqueda, props.filtroActividad] as const, ([busqueda, actividad]) => {
    buscar.buscar_estudiante = busqueda;
    buscar.q = actividad;
});

function buscarEstudiantes() {
    form.clearErrors();
    buscar.get(show.url(props.idSeccion), { preserveState: true, preserveScroll: true });
}

function agregar(id: number) {
    if (form.processing) return;
    form.clearErrors();
    form.id_estudiante = id;
    form.post(store.url(props.idSeccion), { preserveScroll: true });
}
</script>

<template>
    <section class="formulario-estudiante" aria-labelledby="titulo-estudiantes-disponibles">
        <h3 id="titulo-estudiantes-disponibles">Agregar estudiantes</h3>
        <form class="busqueda" @submit.prevent="buscarEstudiantes">
            <input
                v-model.trim="buscar.buscar_estudiante"
                type="search"
                maxlength="100"
                aria-label="Buscar estudiantes por nombre o correo"
                placeholder="Buscar por nombre o correo"
            />
            <button type="submit" class="btn" :disabled="buscar.processing || form.processing">Buscar</button>
        </form>
        <p v-if="buscar.errors.buscar_estudiante" class="error-campo" role="alert">{{ buscar.errors.buscar_estudiante }}</p>
        <p v-if="form.errors.id_estudiante" class="error-campo" role="alert">{{ form.errors.id_estudiante }}</p>
        <ul v-if="disponibles.data.length" class="lista">
            <li v-for="estudiante in disponibles.data" :key="estudiante.id_usuario" class="fila">
                <div>
                    <strong>{{ estudiante.nombre_usuario }}</strong>
                    <p class="muted">{{ estudiante.email }}</p>
                </div>
                <button
                    type="button"
                    class="btn primary"
                    :aria-label="`Agregar a ${estudiante.nombre_usuario} (${estudiante.email})`"
                    :disabled="form.processing || buscar.processing"
                    @click="agregar(estudiante.id_usuario)"
                >{{ form.processing && form.id_estudiante === estudiante.id_usuario ? 'Agregando…' : 'Agregar' }}</button>
            </li>
        </ul>
        <p v-else class="muted">{{ busqueda ? 'No hay estudiantes disponibles que coincidan con la búsqueda.' : 'No hay estudiantes disponibles para inscribir.' }}</p>
        <nav v-if="disponibles.last_page > 1" class="paginacion" aria-label="Paginación de estudiantes disponibles">
            <Link v-if="disponibles.prev_page_url" :href="disponibles.prev_page_url" class="btn" preserve-scroll>Anterior</Link>
            <span>Página {{ disponibles.current_page }} de {{ disponibles.last_page }}</span>
            <Link v-if="disponibles.next_page_url" :href="disponibles.next_page_url" class="btn" preserve-scroll>Siguiente</Link>
        </nav>
    </section>
</template>

<style scoped>
.formulario-estudiante { display: grid; gap: 10px; margin: 16px 0 20px; }
.formulario-estudiante .fila > div { min-width: 0; overflow-wrap: anywhere; }
.error-campo { margin: 0; color: #a44d5f; font-size: 12px; }
</style>
