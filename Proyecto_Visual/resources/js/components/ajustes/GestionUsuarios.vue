<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Paginador from '../aula/Paginador.vue';
import { ajustes } from '../../routes';
import { create, edit } from '../../routes/ajustes/usuarios';
import type { Pagina } from '../../types/aula';
import type { UsuarioResumen } from '../../types/usuarios';

const props = defineProps<{ usuarios: Pagina<UsuarioResumen>; filtros: { q: string } }>();
const page = usePage<{ aulaFlash?: string | null }>();
const busqueda = useForm({ q: props.filtros.q });

function buscar() {
    busqueda.get(ajustes.url(), { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <section class="gestion-usuarios" aria-labelledby="titulo-usuarios">
        <div class="gestion-cabecera">
            <div>
                <h2 id="titulo-usuarios">Gestión de usuarios</h2>
                <p>Crea profesores y estudiantes. Puedes editar tu perfil y los datos de los estudiantes.</p>
            </div>
            <Link :href="create.url()" class="gestion-boton principal">Crear usuario</Link>
        </div>

        <p v-if="page.props.aulaFlash" class="gestion-aviso" role="status">{{ page.props.aulaFlash }}</p>

        <form class="gestion-busqueda" @submit.prevent="buscar">
            <label class="gestion-campo" for="buscar-usuario">
                Buscar por nombre o correo
                <input id="buscar-usuario" v-model="busqueda.q" type="search" maxlength="100" placeholder="Nombre o correo electrónico" />
            </label>
            <button type="submit" class="gestion-boton" :disabled="busqueda.processing">Buscar</button>
        </form>
        <p v-if="busqueda.errors.q" class="gestion-error" role="alert">{{ busqueda.errors.q }}</p>

        <p class="gestion-total">{{ usuarios.total }} usuarios encontrados</p>
        <div v-if="usuarios.data.length" class="gestion-lista">
            <article v-for="usuario in usuarios.data" :key="usuario.id_usuario" class="gestion-fila">
                <div class="gestion-datos">
                    <h3>{{ usuario.nombre_usuario }} <span v-if="usuario.es_actual" class="gestion-propio">Tu cuenta</span></h3>
                    <p>{{ usuario.email }}</p>
                    <span class="gestion-rol">{{ usuario.rol === 'profesor' ? 'Profesor' : 'Estudiante' }}</span>
                </div>
                <Link v-if="usuario.puede_editar" :href="edit.url(usuario.id_usuario)" class="gestion-boton" :aria-label="`Editar a ${usuario.nombre_usuario}`">Editar</Link>
                <span v-else class="gestion-sin-edicion">Otro profesor · solo lectura</span>
            </article>
        </div>
        <p v-else class="gestion-vacio">No hay usuarios que coincidan con la búsqueda.</p>
        <div class="gestion-paginacion"><Paginador :pagina="usuarios" /></div>
    </section>
</template>

<style scoped>
.gestion-usuarios { margin-bottom: 24px; padding: 26px; border: 1px solid #e8e0ef; border-radius: 18px; background: #fff; }
.gestion-cabecera, .gestion-fila, .gestion-busqueda { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.gestion-cabecera h2 { font-size: 19px; color: #6646bd; }
.gestion-cabecera p, .gestion-total, .gestion-datos p, .gestion-sin-edicion { margin-top: 5px; color: #85768f; font-size: 13px; }
.gestion-boton { display: inline-flex; justify-content: center; align-items: center; padding: 10px 16px; border: 1px solid #ded3ed; border-radius: 10px; color: #714acb; background: #faf7ff; text-decoration: none; font-size: 13px; cursor: pointer; }
.gestion-boton.principal { color: #fff; background: #7455ef; border-color: #7455ef; }
.gestion-boton:disabled { opacity: .5; cursor: wait; }
.gestion-busqueda { margin: 22px 0 16px; align-items: end; justify-content: flex-start; }
.gestion-campo { display: grid; gap: 7px; flex: 1; min-width: 180px; color: #766184; font-size: 13px; }
.gestion-campo input { padding: 10px 12px; border: 1px solid #ded3ed; border-radius: 10px; background: #fcfaff; color: #414454; width: 100%; }
.gestion-total { margin-bottom: 12px; }
.gestion-lista { display: grid; gap: 10px; }
.gestion-fila { padding: 16px; border: 1px solid #eee5f4; border-radius: 12px; }
.gestion-datos { min-width: 0; overflow-wrap: anywhere; }
.gestion-datos h3 { color: #554565; font-size: 14px; }
.gestion-propio, .gestion-rol { display: inline-block; border-radius: 7px; padding: 3px 7px; font-size: 11px; background: #f0e8fb; color: #715093; }
.gestion-propio { margin-left: 5px; }
.gestion-rol { margin-top: 7px; }
.gestion-aviso { margin-top: 18px; padding: 12px; color: #477d5d; background: #edf7f0; border-radius: 10px; }
.gestion-error { color: #a44d5f; }
.gestion-vacio { padding: 24px 0; color: #85768f; }
.gestion-paginacion :deep(.paginacion) { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 18px; font-size: 13px; }
.gestion-paginacion :deep(a) { color: #714acb; }
.gestion-usuarios :focus-visible { outline: 2px solid #9b79e5; outline-offset: 3px; }
html.dark .gestion-usuarios { background: #1e1933; border-color: #39304d; }
html.dark .gestion-cabecera h2, html.dark .gestion-datos h3 { color: #d5c6f4; }
html.dark .gestion-cabecera p, html.dark .gestion-total, html.dark .gestion-datos p, html.dark .gestion-sin-edicion, html.dark .gestion-campo, html.dark .gestion-vacio { color: #aaa1be; }
html.dark .gestion-fila { border-color: #39304d; }
html.dark .gestion-boton, html.dark .gestion-campo input { color: #d5c6f4; background: #241e3c; border-color: #493d60; }
html.dark .gestion-boton.principal { background: #7455ef; color: #fff; }
html.dark .gestion-propio, html.dark .gestion-rol { color: #cbb6ee; background: #36294e; }
html.dark .gestion-aviso { color: #b1dec0; background: #213d30; }
html.dark .gestion-paginacion :deep(a) { color: #cbb6ee; }
html.dark .gestion-campo input { color-scheme: dark; }
html.dark .gestion-campo input::placeholder { color: #aaa1be; opacity: 1; }
html.dark .gestion-error { color: #f4adb8; }
@media(max-width:600px) { .gestion-usuarios { padding: 18px; } .gestion-cabecera { align-items: flex-start; } }
</style>
