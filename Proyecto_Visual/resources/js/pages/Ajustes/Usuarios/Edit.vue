<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AulaLayout from '../../../layouts/AulaLayout.vue';
import { ajustes } from '../../../routes';
import { store, update } from '../../../routes/ajustes/usuarios';
import type { DatosUsuario, RolUsuario } from '../../../types/usuarios';

const props = defineProps<{ usuarioEditar: DatosUsuario | null }>();
const form = useForm({
    nombre_usuario: props.usuarioEditar?.nombre_usuario ?? '',
    email: props.usuarioEditar?.email ?? '',
    rol: props.usuarioEditar?.rol ?? 'estudiante' as RolUsuario,
    contrasena: '',
    contrasena_confirmation: '',
});

function guardar() {
    const opciones = {
        preserveScroll: true,
        onFinish: () => form.reset('contrasena', 'contrasena_confirmation'),
    };
    if (props.usuarioEditar) {
        form.put(update.url(props.usuarioEditar.id_usuario), opciones);
    } else {
        form.post(store.url(), opciones);
    }
}
</script>

<template>
    <AulaLayout :titulo="usuarioEditar ? 'Editar usuario' : 'Crear usuario'" descripcion="Administra los datos de acceso al depurador visual." activo="ajustes">
        <div class="barra"><Link :href="ajustes.url()">← Volver a Ajustes</Link></div>
        <form class="panel" @submit.prevent="guardar">
            <div v-if="Object.keys(form.errors).length" class="errores" role="alert">
                <p v-for="(error, campo) in form.errors" :key="campo">{{ error }}</p>
            </div>
            <div class="campos">
                <label class="campo" for="nombre-usuario">Nombre
                    <input id="nombre-usuario" v-model="form.nombre_usuario" :disabled="form.processing" required maxlength="100" autocomplete="name" />
                </label>
                <label class="campo" for="correo-usuario">Correo electrónico
                    <input id="correo-usuario" v-model="form.email" :disabled="form.processing" type="email" required maxlength="200" autocomplete="off" />
                </label>
                <label class="campo" for="rol-usuario">Rol
                    <select id="rol-usuario" v-model="form.rol" :disabled="!!usuarioEditar || form.processing" required>
                        <option value="estudiante">Estudiante</option>
                        <option value="profesor">Profesor</option>
                    </select>
                    <span v-if="usuarioEditar" class="muted">El rol se conserva al editar.</span>
                </label>
                <div class="campo" />
                <label class="campo" for="contrasena-usuario">{{ usuarioEditar ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
                    <input id="contrasena-usuario" v-model="form.contrasena" :disabled="form.processing" type="password" :required="!usuarioEditar" minlength="8" maxlength="72" autocomplete="new-password" />
                    <span class="muted">{{ usuarioEditar ? 'Déjala vacía para conservar la contraseña actual.' : 'Debe tener al menos 8 caracteres.' }}</span>
                </label>
                <label class="campo" for="confirmacion-usuario">Confirmar contraseña
                    <input id="confirmacion-usuario" v-model="form.contrasena_confirmation" :disabled="form.processing" type="password" :required="!usuarioEditar || !!form.contrasena" minlength="8" maxlength="72" autocomplete="new-password" />
                </label>
            </div>
            <div class="acciones" style="margin-top: 20px">
                <button type="submit" class="btn primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : (usuarioEditar ? 'Guardar cambios' : 'Crear usuario') }}</button>
                <Link :href="ajustes.url()" class="btn">Cancelar</Link>
            </div>
        </form>
    </AulaLayout>
</template>
