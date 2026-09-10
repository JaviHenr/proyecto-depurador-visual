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

const quickLogin = (rol: 'profesor' | 'estudiante') => {
    if (rol === 'profesor') {
        form.email = 'profesor@depurador.test';
        form.contrasena = 'profesor123';
    } else {
        form.email = 'estudiante@depurador.test';
        form.contrasena = 'estudiante123';
    }
    submit();
};
</script>

<template>
    <Head title="Iniciar Sesión - Depurador Visual" />

    <div class="min-h-screen bg-[#FBFBFA] text-[#1C1917] flex flex-col justify-center items-center px-4 py-12">
        <div class="w-full max-w-sm">
            <!-- Encabezado Minimalista -->
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-[#1C1917]">
                    Depurador Visual
                </h1>
                <p class="text-xs text-[#78716C] mt-1">
                    Comprensión de estructuras iterativas
                </p>
            </div>

            <!-- Caja de Login Rectangular -->
            <div class="bg-white border border-[#E7E5E4] rounded-sm p-6 sm:p-7 shadow-xs">
                <div class="mb-5 pb-3 text-center">
                    <h2 class="text-sm font-semibold text-[#1C1917]">Iniciar Sesión</h2>
                </div>

                <!-- Alerta de Error en Tono Pastel -->
                <div v-if="form.errors.email" class="mb-4 p-3 bg-[#FEF2F2] border border-[#FECACA] rounded-xs text-[#991B1B] text-xs">
                    {{ form.errors.email }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Campo Correo -->
                    <div>
                        <label for="email" class="block text-xs font-medium text-[#44403C] mb-1">
                            Correo Electrónico
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="nombre@ejemplo.com"
                            class="w-full bg-[#FAFAF9] border border-[#D6D3D1] rounded-xs px-3 py-2 text-xs text-[#1C1917] placeholder-[#A8A29E] focus:bg-white focus:outline-none focus:border-[#44403C] transition-colors"
                        />
                    </div>

                    <!-- Campo Contraseña -->
                    <div>
                        <label for="password" class="block text-xs font-medium text-[#44403C] mb-1">
                            Contraseña
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.contrasena"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full bg-[#FAFAF9] border border-[#D6D3D1] rounded-xs px-3 pr-9 py-2 text-xs text-[#1C1917] placeholder-[#A8A29E] focus:bg-white focus:outline-none focus:border-[#44403C] transition-colors"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 px-2.5 flex items-center text-xs text-[#78716C] hover:text-[#1C1917] cursor-pointer select-none"
                            >
                                {{ showPassword ? 'Ocultar' : 'Ver' }}
                            </button>
                        </div>
                    </div>

                    <!-- Recordar sesión -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-3.5 h-3.5 rounded-xs border-[#D6D3D1] text-[#44403C] focus:ring-0 focus:ring-offset-0"
                            />
                            <span class="text-xs text-[#78716C]">Recordar sesión</span>
                        </label>
                    </div>

                    <!-- Botón Ingresar -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#00AAFF] hover:bg-[#00AAFF] text-white font-medium py-2 px-4 rounded-xs text-xs transition duration-100 flex items-center justify-center gap-2"
                    >
                        <span>{{ form.processing ? 'Ingresando...' : 'Iniciar Sesión' }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
