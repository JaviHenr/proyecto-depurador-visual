<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AulaLayout from '../../../layouts/AulaLayout.vue';
import FormularioSeccion from '../../../components/aula/FormularioSeccion.vue';
import Paginador from '../../../components/aula/Paginador.vue';
import type { Pagina, Seccion } from '../../../types/aula';

const props = defineProps<{ secciones: Pagina<Seccion>; filtros: { q: string }; puedeCrear: boolean }>();
const creando = ref(false);
const buscar = useForm({ q: props.filtros.q });
</script>

<template>
    <AulaLayout titulo="Mis secciones" descripcion="Organiza tus secciones y consulta las actividades de cada una." activo="secciones">
        <div class="barra">
            <form class="busqueda" @submit.prevent="buscar.get('/secciones', { preserveState: true })">
                <input v-model="buscar.q" type="search" maxlength="100" aria-label="Buscar sección" placeholder="Buscar por nombre" />
                <button type="submit" class="btn" :disabled="buscar.processing">Buscar</button>
            </form>
            <button v-if="puedeCrear" type="button" class="btn primary" @click="creando = !creando">{{ creando ? 'Cerrar formulario' : '+ Crear sección' }}</button>
        </div>
        <FormularioSeccion v-if="creando && puedeCrear" @cerrar="creando = false" />
        <div v-if="secciones.data.length" class="tarjetas">
            <article v-for="seccion in secciones.data" :key="seccion.id_seccion" class="tarjeta">
                <span class="muted">Sección #{{ seccion.id_seccion }}</span>
                <h2>{{ seccion.nombre_seccion || 'Sección sin nombre' }}</h2>
                <p>{{ seccion.descripcion_seccion || 'Consulta las actividades y sus instrucciones.' }}</p>
                <p>
                    {{ seccion.actividades_count }} {{ seccion.actividades_count === 1 ? 'actividad' : 'actividades' }}
                    · {{ seccion.estudiantes_count }} {{ seccion.estudiantes_count === 1 ? 'estudiante' : 'estudiantes' }}
                </p>
                <Link :href="`/secciones/${seccion.id_seccion}`" class="btn">Abrir sección →</Link>
            </article>
        </div>
        <div v-else class="vacio">{{ filtros.q ? 'No hay secciones que coincidan con la búsqueda.' : (puedeCrear ? 'Crea tu primera sección para comenzar.' : 'No tienes secciones disponibles.') }}</div>
        <Paginador :pagina="secciones" />
    </AulaLayout>
</template>
