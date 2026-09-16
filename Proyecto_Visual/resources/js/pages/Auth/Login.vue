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

<style scoped>
.layout-login {
    --bg-app: #FAF7F2;          /* Fondo crema cálido igual al Dashboard */
    --bg-card: #FFFFFF;
    --text-dark: #3D3D4E;
    --text-muted: #8E8E9F;

    --pastel-lila: #EBE4FB;
    --primary: #6C5CE7;
    --primary-hover: #5846E0;

    --radius-lg: 20px;
    --radius-md: 12px;
    --shadow-soft: 0 8px 24px rgba(138, 149, 158, 0.08);

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: var(--bg-app);
    color: var(--text-dark);
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 32px 16px;
    box-sizing: border-box;
}

/* Encabezado */
.brand-header {
    text-align: center;
    margin-bottom: 28px;
}

.brand-title {
    font-size: 1.8rem;
    font-weight: 800;
    letter-spacing: -0.025em;
    color: #2D2B52;
    line-height: 1.2;
    margin: 0;
}

.brand-accent {
    background: linear-gradient(135deg, #6C5CE7 0%, #8E7CF7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-left: 5px;
}

.brand-subtitle {
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-top: 6px;
}

/* Tarjeta */
.login-card {
    width: 100%;
    max-width: 390px;
    background-color: var(--bg-card);
    padding: 36px 32px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    border: 1px solid rgba(0, 0, 0, 0.03);
    box-sizing: border-box;
}

.card-header {
    text-align: center;
    margin-bottom: 24px;
}

.card-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 6px 0;
}

.card-header p {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin: 0;
}

/* Alerta */
.error-alert {
    padding: 10px 14px;
    background-color: #FEF2F2;
    border: 1px solid #FECACA;
    border-radius: var(--radius-md);
    color: #991B1B;
    font-size: 0.82rem;
    margin-bottom: 18px;
}

/* Formulario */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #4B4B5C;
}

.form-input {
    width: 100%;
    padding: 11px 14px;
    background-color: #FAF9F7;
    border: 1px solid #E5E3DF;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    color: var(--text-dark);
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-input:focus {
    background-color: #FFFFFF;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.12);
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-wrapper .form-input {
    padding-right: 64px;
}

.btn-toggle-pwd {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    font-size: 0.8rem;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    transition: color 0.2s;
}

.btn-toggle-pwd:hover {
    color: var(--text-dark);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: var(--text-muted);
    user-select: none;
}

.checkbox {
    accent-color: var(--primary);
    width: 15px;
    height: 15px;
    cursor: pointer;
}

.btn-submit {
    width: 100%;
    padding: 12px;
    background-color: var(--primary);
    color: #FFFFFF;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s, box-shadow 0.2s;
    box-shadow: 0 4px 14px rgba(108, 92, 231, 0.22);
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn-submit:hover:not(:disabled) {
    background-color: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(108, 92, 231, 0.3);
}

.btn-submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
