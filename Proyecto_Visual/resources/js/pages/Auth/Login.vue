<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BotonModoOscuro from '../../components/BotonModoOscuro.vue';
import { useTheme } from '../../lib/useTheme';

const showPassword = ref(false);
const { isDark } = useTheme();

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

    <div class="layout-login-split">
        <!-- Panel Izquierdo: Presentación y Marca con Traza Visual -->
        <aside class="panel-banner-login">
            <div class="banner-top">
                <div class="banner-logo-marca">
                    <div class="brand-logo-contenedor">
                        <img
                            :src="isDark ? '/traza-2-oscuro.svg' : '/traza-2-claro.svg'"
                            alt="Depurador Visual Logo"
                            class="brand-logo-img"
                        />
                    </div>
                    <span class="banner-titulo-marca">Depurador Visual</span>
                </div>
            </div>

            <div class="banner-content">
                <h1 class="banner-slogan">
                    Plataforma de<br />
                    apoyo a la<br />
                    comprensión de<br />
                    estructuras<br />
                    iterativas
                </h1>
            </div>

            <!-- Gráfico decorativo de la traza de ejecución -->
            <div class="banner-watermark-wrapper" aria-hidden="true">
                <svg class="banner-watermark" viewBox="0 0 340 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M20 250 H100 V160 H200 V70 H320"
                        stroke="rgba(255, 255, 255, 0.22)"
                        stroke-width="22"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <circle cx="200" cy="160" r="48" fill="rgba(215, 196, 255, 0.22)" />
                    <circle cx="200" cy="160" r="26" fill="#D7C4FF" />
                </svg>
            </div>
        </aside>

        <!-- Panel Derecho: Formulario de inicio de sesión -->
        <main class="panel-formulario-login">
            <!-- Botón de cambio de modo oscuro en la esquina superior derecha (Luna / Sol) -->
            <div class="login-theme-toggle-wrapper">
                <BotonModoOscuro />
            </div>

            <div class="contenedor-formulario-login">
                <div class="login-header">
                    <h2>Iniciar sesión</h2>
                    <p>Ingresa tus credenciales para continuar</p>
                </div>

                <!-- Alertas de error si las credenciales fallan -->
                <div v-if="form.errors.email" class="error-alert" role="alert">
                    {{ form.errors.email }}
                </div>
                <div v-if="form.errors.contrasena" class="error-alert" role="alert">
                    {{ form.errors.contrasena }}
                </div>

                <form @submit.prevent="submit" class="login-form">
                    <!-- Campo Correo Electrónico -->
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
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

                    <!-- Campo Contraseña con Toggle Ver -->
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="password-wrapper">
                            <input
                                id="password"
                                v-model="form.contrasena"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••••"
                                class="form-input form-input-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="btn-toggle-pwd"
                                :aria-label="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
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

                    <!-- Botón Iniciar sesión -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn-submit"
                    >
                        <span>{{ form.processing ? 'Ingresando...' : 'Iniciar sesión' }}</span>
                    </button>
                </form>
            </div>
        </main>
    </div>
</template>
