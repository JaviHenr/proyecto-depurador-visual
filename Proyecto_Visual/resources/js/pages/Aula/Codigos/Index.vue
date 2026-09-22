<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AulaLayout from '../../../layouts/AulaLayout.vue';
import Paginador from '../../../components/aula/Paginador.vue';
import type { CodigoResumen, Pagina } from '../../../types/aula';

const props = defineProps<{
    codigos: Pagina<CodigoResumen>;
    filtros: { q: string; actividad: number | null; vista: 'mios' | 'estudiantes' };
    vista: 'mios' | 'estudiantes';
    puedeVerEstudiantes: boolean;
}>();
const buscar = useForm({ q: props.filtros.q, actividad: props.filtros.actividad, vista: props.vista });
const borrado = useForm({});
function eliminar(codigo: CodigoResumen) {
    if (!window.confirm(`¿Eliminar «${codigo.nombre_codigo || 'Código sin nombre'}»? Esta acción no se puede deshacer.`)) return;
    borrado.clearErrors();
    borrado.delete(`/codigos/${codigo.id_codigo}`, { preserveScroll: true });
}
</script>

<template>
    <AulaLayout
        :titulo="vista === 'estudiantes' ? 'Códigos de estudiantes' : 'Mis códigos'"
        :descripcion="vista === 'estudiantes' ? 'Consulta las entregas asociadas a las actividades de tus secciones.' : 'Guarda tus programas y ábrelos en el depurador visual.'"
        activo="codigos"
    >
        <nav v-if="puedeVerEstudiantes" class="pestanas" aria-label="Tipos de códigos">
            <Link href="/codigos?vista=mios" :class="{ activa: vista === 'mios' }">Mis códigos</Link>
            <Link href="/codigos?vista=estudiantes" :class="{ activa: vista === 'estudiantes' }">Códigos de estudiantes</Link>
        </nav>
        <div class="barra">
            <form class="busqueda" @submit.prevent="buscar.get('/codigos', { preserveState: true })">
                <input v-model="buscar.q" type="search" maxlength="100" placeholder="Buscar por nombre o archivo" aria-label="Buscar código" />
                <button type="submit" class="btn" :disabled="buscar.processing">Buscar</button>
            </form>
            <Link href="/codigos/nuevo" class="btn primary">+ Crear código</Link>
        </div>
        <div v-if="filtros.actividad" class="barra">
            <p class="muted">Mostrando tus códigos de la actividad #{{ filtros.actividad }}.</p>
            <Link href="/codigos" class="btn">Ver todos mis códigos</Link>
        </div>
        <div v-if="Object.keys(borrado.errors).length" class="errores" role="alert">
            <p v-for="(error, key) in borrado.errors" :key="key">{{ error }}</p>
        </div>
        <div v-if="codigos.data.length" class="lista">
            <article v-for="codigo in codigos.data" :key="codigo.id_codigo" class="fila">
                <div>
                    <h3>{{ codigo.nombre_codigo || 'Código sin nombre' }}</h3>
                    <p class="muted">{{ codigo.nombre_archivo || 'Sin nombre de archivo' }} · {{ codigo.formato || 'Sin formato' }}</p>
                    <p v-if="vista === 'estudiantes'" class="muted">Autor: {{ codigo.autor_nombre }} · {{ codigo.autor_email }}</p>
                    <p class="muted">
                        {{ codigo.id_actividad ? (codigo.actividad_nombre || `Actividad #${codigo.id_actividad}`) : 'Código personal' }}
                        · {{ codigo.fecha_creacion_codigo || 'Sin fecha registrada' }}
                    </p>
                </div>
                <div class="acciones">
                    <Link :href="`/depurador/${codigo.id_codigo}`" class="btn primary">
                        {{ codigo.permisos.editar ? 'Abrir en depurador' : 'Ver en depurador' }}
                    </Link>
                    <button v-if="codigo.permisos.editar" type="button" class="btn danger" :disabled="borrado.processing" @click="eliminar(codigo)">Eliminar</button>
                </div>
            </article>
        </div>
        <div v-else class="vacio">
            {{ filtros.q || filtros.actividad
                ? 'No hay códigos que coincidan con la búsqueda.'
                : (vista === 'estudiantes' ? 'Todavía no hay códigos de estudiantes disponibles en tus secciones.' : 'Todavía no has guardado códigos. Crea el primero para comenzar.') }}
        </div>
        <Paginador :pagina="codigos" />
    </AulaLayout>
</template>

<style scoped>
.pestanas a { padding: 10px 16px; border: 1px solid #e2d7ef; border-radius: 10px; background: #f6f1fd; color: #8b729f; }
.pestanas a.activa { border-color: #bba0e7; background: #e9ddfb; color: #6c46b2; font-weight: 600; }
</style>
