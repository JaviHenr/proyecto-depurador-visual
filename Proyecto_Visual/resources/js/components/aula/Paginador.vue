<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Paginacion } from '../../types/aula';
defineProps<{ pagina: Paginacion }>();
</script>
<template>
    <nav v-if="pagina.last_page > 1" class="paginacion" aria-label="Paginación">
        <Link v-if="pagina.prev_page_url" :href="pagina.prev_page_url" class="btn">Anterior</Link>
        <span>Página {{ pagina.current_page }} de {{ pagina.last_page }}</span>
        <Link v-if="pagina.next_page_url" :href="pagina.next_page_url" class="btn">Siguiente</Link>
    </nav>
</template>
