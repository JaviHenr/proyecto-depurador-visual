<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import type { User } from '@/types';

const props = defineProps<{
    user: User;
}>();

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <h1>
        Hola, {{ props.user.name }}
    </h1>

    <!-- Botón Cerrar Sesión Simple -->
    <button
        @click="logout"
        type="button"
        class="text-xs text-[#78716C] hover:text-[#991B1B] hover:bg-[#FEF2F2] border border-[#E7E5E4] hover:border-[#FECACA] px-2.5 py-1 rounded-xs transition-colors cursor-pointer"
        >
           Cerrar Sesión
    </button>
</template>
