<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);

const form = useForm({
    email: '',
    contrasena: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.contrasena = '';
        },
    });
};
</script>

<template>
    <Head title="Iniciar Sesión - Depurador Visual" />

    <div class="layout-login">
        <!-- Encabezado de Marca (igual a Dashboard) -->
        <div class="brand-header">
            <h1 class="brand-title">Depurador<span class="brand-accent">Visual</span></h1>
            <p class="brand-subtitle">Comprensión de estructuras iterativas</p>
        </div>

        <!-- Tarjeta de Login Simple -->
        <div class="login-card">
            <div class="card-header">
                <h2>Iniciar Sesión</h2>
                <p>Ingresa tus credenciales para continuar</p>
            </div>

            <!-- Alerta de Error en Tono Pastel -->
            <div v-if="form.errors.email" class="error-alert">
                {{ form.errors.email }}
            </div>

            <form @submit.prevent="submit" class="login-form">
                <!-- Campo Correo -->
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="nombre@ejemplo.com"
                        class="form-input"
                    />
                </div>

                <!-- Campo Contraseña -->
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-wrapper">
                        <input
                            id="password"
                            v-model="form.contrasena"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="form-input"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="btn-toggle-pwd"
                        >
                            {{ showPassword ? 'Ocultar' : 'Ver' }}
                        </button>
                    </div>
                </div>

                <!-- Recordar sesión -->
                <div class="form-options">
                    <label class="checkbox-label">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="checkbox"
                        />
                        <span>Recordar sesión</span>
                    </label>
                </div>

                <!-- Botón Ingresar -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-submit"
                >
                    <span>{{ form.processing ? 'Ingresando...' : 'Iniciar Sesión' }}</span>
                </button>
            </form>
        </div>
    </div>
</template>
