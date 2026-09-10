<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import type { Auth } from '@/types';

const props = defineProps<{
    auth: Auth;
}>();

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Dashboard" />

    <template v-if="props.auth.user">
        <h1>
            Hola, {{ props.auth.user.nombre_usuario }}
        </h1>

        <button
            @click="logout"
            type="button"
            class="text-xs text-[#78716C] hover:text-[#991B1B] hover:bg-[#FEF2F2] border border-[#E7E5E4] hover:border-[#FECACA] px-2.5 py-1 rounded-xs transition-colors cursor-pointer"
        >
            Cerrar Sesión
        </button>
    </template>
</template>