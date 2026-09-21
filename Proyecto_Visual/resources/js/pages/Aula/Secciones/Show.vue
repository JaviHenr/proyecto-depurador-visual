<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AulaLayout from '../../../layouts/AulaLayout.vue';
import FormularioSeccion from '../../../components/aula/FormularioSeccion.vue';
import FormularioActividad from '../../../components/aula/FormularioActividad.vue';
import FormularioEstudiante from '../../../components/aula/FormularioEstudiante.vue';
import Paginador from '../../../components/aula/Paginador.vue';
import type { Actividad, Estudiante, Pagina, Seccion } from '../../../types/aula';

const props = defineProps<{
    seccion: Seccion;
    profesor: string;
    actividades: Pagina<Actividad>;
    estudiantes: Pagina<Estudiante> | null;
    filtros: { q: string };
    permisos: { gestionar: boolean };
}>();
const editandoSeccion = ref(false);
const creandoActividad = ref(false);
const editandoId = ref<number | null>(null);
const accion = useForm({});
const accionEstudiante = useForm({});
const buscar = useForm({ q: props.filtros.q });

function eliminarSeccion() {
    if (!window.confirm('¿Eliminar esta sección? Esta acción no se puede deshacer.')) return;
    accion.clearErrors();
    accion.delete(`/secciones/${props.seccion.id_seccion}`);
}

function quitarActividad(actividad: Actividad, desvincular = false) {
    const nombre = actividad.nombre_actividad || `Actividad #${actividad.id_actividad}`;
    const mensaje = desvincular
        ? `¿Retirar «${nombre}» de esta sección? Se conservará en sus otras secciones y sus códigos seguirán guardados.`
        : `¿Eliminar «${nombre}»? Las demás actividades de la sección se conservarán.`;
    if (!window.confirm(mensaje)) return;
    accion.clearErrors();
    const url = `/secciones/${props.seccion.id_seccion}/actividades/${actividad.id_actividad}`;
    accion.delete(desvincular ? `${url}/vinculo` : url, {
        preserveScroll: true,
        onSuccess: () => { editandoId.value = null; },
    });
}

function retirarEstudiante(estudiante: Estudiante) {
    if (!window.confirm(`¿Retirar a ${estudiante.nombre_usuario} de esta sección? Sus códigos no se eliminarán.`)) return;
    accionEstudiante.clearErrors();
    accionEstudiante.delete(`/secciones/${props.seccion.id_seccion}/estudiantes/${estudiante.id_usuario}`, {
        preserveScroll: true,
    });
}

function buscarActividades() {
    editandoId.value = null;
    buscar.get(`/secciones/${props.seccion.id_seccion}`, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <AulaLayout :titulo="seccion.nombre_seccion || 'Sección sin nombre'" :descripcion="`Profesor: ${profesor}`" activo="secciones">
        <div class="barra">
            <Link href="/secciones">← Mis secciones</Link>
            <div v-if="permisos.gestionar" class="acciones">
                <button type="button" class="btn" @click="editandoSeccion = !editandoSeccion">{{ editandoSeccion ? 'Cerrar formulario' : 'Editar sección' }}</button>
                <button type="button" class="btn danger" :disabled="accion.processing" @click="eliminarSeccion">Eliminar sección</button>
            </div>
        </div>
        <div v-if="Object.keys(accion.errors).length" class="errores" role="alert">
            <p v-for="(error, key) in accion.errors" :key="key">{{ error }}</p>
        </div>
        <FormularioSeccion v-if="editandoSeccion && permisos.gestionar" :key="seccion.id_seccion" :seccion="seccion" @cerrar="editandoSeccion = false" />
        <section v-if="seccion.descripcion_seccion" class="panel">
            <h2>Acerca de la sección</h2>
            <p class="texto-largo">{{ seccion.descripcion_seccion }}</p>
        </section>
        <section class="panel">
            <div class="barra barra-interna">
                <div>
                    <h2>Estudiantes</h2>
                    <p class="muted">{{ seccion.estudiantes_count }} {{ seccion.estudiantes_count === 1 ? 'estudiante inscrito' : 'estudiantes inscritos' }}</p>
                </div>
            </div>

            <template v-if="permisos.gestionar && estudiantes">
                <p class="muted">Agrega estudiantes por el correo registrado en su cuenta.</p>
                <FormularioEstudiante :id-seccion="seccion.id_seccion" />
                <div v-if="Object.keys(accionEstudiante.errors).length" class="errores" role="alert">
                    <p v-for="(error, key) in accionEstudiante.errors" :key="key">{{ error }}</p>
                </div>
                <div v-if="estudiantes.data.length" class="lista">
                    <article v-for="estudiante in estudiantes.data" :key="estudiante.id_usuario" class="fila">
                        <div>
                            <h3>{{ estudiante.nombre_usuario }}</h3>
                            <p class="muted">{{ estudiante.email }}</p>
                            <p v-if="estudiante.fecha_inscripcion" class="muted">Inscrito el {{ estudiante.fecha_inscripcion.slice(0, 10) }}</p>
                        </div>
                        <button
                            type="button"
                            class="btn danger"
                            :disabled="accionEstudiante.processing"
                            @click="retirarEstudiante(estudiante)"
                        >Retirar</button>
                    </article>
                </div>
                <div v-else class="vacio vacio-compacto">Aún no hay estudiantes inscritos.</div>
                <Paginador :pagina="estudiantes" />
            </template>
            <p v-else class="muted">Estás inscrito en esta sección y puedes consultar sus actividades.</p>
        </section>
        <div class="barra">
            <div class="acciones"><h2>Actividades</h2><span class="etiqueta">{{ seccion.actividades_count }} en total</span></div>
            <button v-if="permisos.gestionar" type="button" class="btn primary" @click="creandoActividad = !creandoActividad">{{ creandoActividad ? 'Cerrar formulario' : '+ Crear actividad' }}</button>
        </div>
        <FormularioActividad v-if="creandoActividad && permisos.gestionar" :id-seccion="seccion.id_seccion" @cerrar="creandoActividad = false" />
        <form class="busqueda buscador-actividades" @submit.prevent="buscarActividades">
            <input v-model="buscar.q" type="search" maxlength="100" placeholder="Buscar actividad en esta sección" aria-label="Buscar actividad" />
            <button type="submit" class="btn" :disabled="buscar.processing">Buscar</button>
        </form>
        <div v-if="actividades.data.length">
            <article v-for="actividad in actividades.data" :key="actividad.id_actividad" class="panel">
                <div class="barra">
                    <h2>{{ actividad.nombre_actividad || 'Actividad sin nombre' }}</h2>
                    <span class="etiqueta">{{ actividad.fecha_creacion_actividad?.slice(0, 10) || 'Sin fecha registrada' }}</span>
                </div>
                <p v-if="actividad.descripcion_actividad" class="texto-largo">{{ actividad.descripcion_actividad }}</p>
                <div v-if="actividad.instrucciones" class="instrucciones">
                    <h3>Instrucciones</h3><p class="texto-largo">{{ actividad.instrucciones }}</p>
                </div>
                <p v-if="actividad.secciones_count > 1" class="muted nota">Actividad compartida con otras secciones, disponible en modo de lectura. Puedes retirarla de esta sección sin eliminarla de las demás.</p>
                <p class="muted nota">{{ actividad.codigos_count }} {{ actividad.codigos_count === 1 ? 'código asociado' : 'códigos asociados' }}.</p>
                <div class="acciones nota">
                    <Link :href="`/codigos/nuevo?actividad=${actividad.id_actividad}`" class="btn primary">+ Crear código</Link>
                    <Link :href="`/codigos?actividad=${actividad.id_actividad}`" class="btn">Mis códigos de esta actividad</Link>
                    <button v-if="actividad.permisos.editar" type="button" class="btn" @click="editandoId = editandoId === actividad.id_actividad ? null : actividad.id_actividad">{{ editandoId === actividad.id_actividad ? 'Cerrar formulario' : 'Editar' }}</button>
                    <button v-if="actividad.permisos.editar" type="button" class="btn danger" :disabled="accion.processing" @click="quitarActividad(actividad)">Eliminar</button>
                    <button v-if="actividad.permisos.desvincular" type="button" class="btn danger" :disabled="accion.processing" @click="quitarActividad(actividad, true)">Retirar de esta sección</button>
                </div>
                <FormularioActividad
                    v-if="editandoId === actividad.id_actividad && actividad.permisos.editar"
                    class="edicion-actividad"
                    :id-seccion="seccion.id_seccion"
                    :actividad="actividad"
                    @cerrar="editandoId = null"
                />
            </article>
        </div>
        <div v-else class="vacio">{{ filtros.q ? 'No hay actividades que coincidan con la búsqueda.' : 'Esta sección todavía no tiene actividades. Crea la primera para comenzar.' }}</div>
        <Paginador :pagina="actividades" />
    </AulaLayout>
</template>

<style scoped>
.texto-largo { white-space: pre-wrap; overflow-wrap: anywhere; }
.instrucciones { margin-top: 20px; padding: 18px; border-radius: 12px; background: #faf7fd; }
.nota { margin-top: 18px; }
.buscador-actividades { margin-bottom: 22px; }
.edicion-actividad { margin-top: 20px; margin-bottom: 0; }
.barra-interna { margin-bottom: 8px; }
.vacio-compacto { padding: 20px; }
</style>
