<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BarraLateral from '../components/BarraLateral.vue';
</script>

<template>
    <Head title="Blanco" />
    <div class="dashboard-app">
        <BarraLateral />
        <main class="contenido-principal">
            <header class="encabezado-superior">
                <div class="saludo">
                    <h1>Blanco</h1>
                    <p>Esta es la página de blanco.</p>
                </div>
            </header>
        </main>
    </div>
</template>
