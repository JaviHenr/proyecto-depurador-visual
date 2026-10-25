<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';
import BotonModoOscuro from '../components/BotonModoOscuro.vue';

defineProps<{ titulo: string; descripcion?: string; activo: 'secciones' | 'codigos' }>();
const page = usePage<{
    actor?: { id: number; nombre: string; rol: string };
    auth?: { user?: { nombre_usuario?: string; name?: string; rol?: string } };
    usuario?: { nombre_usuario?: string; name?: string; rol?: string };
    aulaFlash?: string | null;
}>();

const currentUser = computed(() => (page.props as any).usuario ?? (page.props as any).auth?.user ?? null);
const userName = computed(() => currentUser.value?.nombre_usuario || currentUser.value?.name || page.props.actor?.nombre || 'Profesor de prueba');
const inicial = computed(() => userName.value.trim().charAt(0).toUpperCase() || 'P');

const sesion = useForm({});
</script>

<template>
    <Head :title="`${titulo} - Depurador Visual`" />
    <div class="aula-app">
        <BarraLateral :pestana-activa="activo" />
        <main class="aula-main">
            <header class="aula-encabezado">
                <div>
                    <h1>{{ titulo }}</h1>
                    <p v-if="descripcion" class="muted">{{ descripcion }}</p>
                </div>
                <div class="aula-acciones-encabezado">
                    <BotonModoOscuro />
                    <span class="avatar" aria-hidden="true">{{ inicial }}</span>
                    <button type="button" class="boton-salir" :disabled="sesion.processing" @click="sesion.post('/logout')">Cerrar Sesión</button>
                </div>
            </header>
            <p v-if="page.props.aulaFlash" class="aviso" role="status">{{ page.props.aulaFlash }}</p>
            <slot />
        </main>
    </div>
</template>

<style>
.aula-app { display: grid; grid-template-columns: 250px minmax(0, 1fr); min-height: 100vh; background: #faf7f3; color: #414454; font: 14px/1.6 'Segoe UI', system-ui, sans-serif; transition: background-color 0.25s ease, color 0.25s ease; }
.aula-app * { box-sizing: border-box; }
.aula-app h1, .aula-app h2, .aula-app h3, .aula-app p { margin: 0; overflow-wrap: anywhere; }
.aula-app h1 { font-size: 29px; line-height: 1.3; letter-spacing: -.4px; }
.aula-app h2 { font-size: 19px; color: #6646bd; }
.aula-app h3 { font-size: 16px; color: #694ca5; }
.aula-app a { color: #714acb; text-decoration: none; }
.aula-app button, .aula-app input, .aula-app select, .aula-app textarea { font: inherit; }
.aula-app :focus-visible { outline: 2px solid #9b79e5; outline-offset: 3px; }
.aula-app button { cursor: pointer; }
.aula-app button:disabled { opacity: .5; cursor: not-allowed; }
.aula-app .aula-main { width: 100%; max-width: 1400px; min-width: 0; margin: 0 auto; padding: 30px 40px 45px; }
.aula-app .aula-encabezado { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 28px; }
.aula-app .aula-acciones-encabezado { display: flex; align-items: center; gap: 16px; }
.aula-app .avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; color: #0284c7; background: #e0f2fe; font-size: 14px; font-weight: 700; }
.aula-app .boton-salir { min-height: 38px; padding: 8px 17px; border: 1px solid #8bbafa; border-radius: 12px; background: transparent; color: #373cd3; font-size: 12px; cursor: pointer; transition: all 0.2s ease; }
.aula-app .boton-salir:hover { background: #f0f5ff; }
.aula-app .aula-encabezado p { margin-top: 6px; }
.aula-app .muted { color: #85768f; font-size: 13px; }
.aula-app .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 37px; padding: 8px 13px; border: 1px solid #dcd0ed; border-radius: 10px; background: #f7f2fe; color: #7954b8; font-size: 12px; transition: all 0.2s ease; }
.aula-app .btn.primary { background: #7455ef; border-color: #7455ef; color: white; }
.aula-app .btn.danger { color: #a44e60; background: #fff4f5; border-color: #f0d1d8; }
.aula-app .btn:hover:not(:disabled) { filter: brightness(.97); }
.aula-app .acciones { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; }
.aula-app .entre { justify-content: space-between; }
.aula-app .barra { display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between; gap: 14px; margin-bottom: 20px; }
.aula-app .panel { padding: 24px; margin-bottom: 22px; border: 1px solid #eee5f5; border-radius: 20px; background: #fff; box-shadow: 0 10px 28px #4e357005; transition: all 0.25s ease; }
.aula-app .panel h2, .aula-app .panel h3 { margin-bottom: 10px; }
.aula-app .tarjetas { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 18px; }
.aula-app .tarjeta { display: flex; flex-direction: column; gap: 12px; min-width: 0; padding: 23px; border: 1px solid #ddcfff; border-radius: 20px; background: #ebe4fa; transition: all 0.2s ease; }
.aula-app .tarjeta p { color: #80708e; font-size: 13px; }
.aula-app .tarjeta .btn { align-self: flex-start; margin-top: auto; }
.aula-app .etiqueta { display: inline-block; padding: 3px 8px; border-radius: 8px; color: #806690; background: #f2ebf9; font-size: 11px; }
.aula-app .campos { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; margin: 18px 0; }
.aula-app .campo { display: flex; flex-direction: column; gap: 6px; min-width: 0; color: #766184; font-size: 12px; }
.aula-app .completo { grid-column: 1 / -1; }
.aula-app input:not([type=radio]), .aula-app select, .aula-app textarea { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid #e0d5ed; border-radius: 10px; background: #fdfbff; color: #5f4d6c; font-size: 13px; transition: all 0.2s ease; }
.aula-app textarea { resize: vertical; }
.aula-app .busqueda { display: flex; gap: 8px; max-width: 480px; flex: 1; }
.aula-app .errores { margin: 14px 0; padding: 12px 16px; border-radius: 10px; background: #fff0f1; color: #a44d5f; font-size: 12px; }
.aula-app .aviso { margin-bottom: 20px; padding: 13px 16px; border: 1px solid #d0e5d8; border-radius: 12px; background: #edf7f0; color: #477d5d; font-size: 13px; }
.aula-app .pestanas { display: flex; flex-wrap: wrap; gap: 8px; margin: 22px 0; }
.aula-app .pestanas button, .aula-app .pestanas a { padding: 10px 16px; border: 1px solid #e2d7ef; border-radius: 10px; background: #f6f1fd; color: #8b729f; transition: all 0.2s ease; text-decoration: none; display: inline-flex; align-items: center; }
.aula-app .pestanas button.activa, .aula-app .pestanas a.activa { border-color: #bba0e7; background: #e9ddfb; color: #6c46b2; font-weight: 600; }
.aula-app .lista { display: grid; gap: 12px; }
.aula-app .fila { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding: 15px 18px; border: 1px solid #eee5f4; border-radius: 12px; background: #fff; transition: all 0.2s ease; }
.aula-app .pre { overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; padding: 16px; border-radius: 10px; background: #faf7fd; color: #715881; font: 12px/1.7 ui-monospace, Consolas, monospace; }
.aula-app .editor { min-height: 340px; tab-size: 4; white-space: pre; font: 13px/1.8 ui-monospace, Consolas, monospace; }
.aula-app details { margin: 14px 0; }
.aula-app summary { cursor: pointer; color: #7e6392; font-size: 13px; }
.aula-app .vacio { padding: 32px; border: 1px dashed #ddcfea; border-radius: 18px; text-align: center; color: #8a7896; background: #fff; }
.aula-app .paginacion { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 13px; margin-top: 22px; color: #92809f; font-size: 12px; }

/* Modo Oscuro para AulaLayout */
html.dark .aula-app { background: #12101F; color: #E2DFEC; }
html.dark .aula-app h1 { color: #FFFFFF; }
html.dark .aula-app h2 { color: #C5B6F5; }
html.dark .aula-app h3 { color: #A78BFA; }
html.dark .aula-app a { color: #A78BFA; }
html.dark .aula-app .muted { color: #8E89A8; }
html.dark .aula-app .panel { background: #1C1833; border-color: rgba(255, 255, 255, 0.06); box-shadow: 0 10px 28px rgba(0, 0, 0, 0.3); }
html.dark .aula-app .tarjeta { background: #221B3B; border-color: #382C60; color: #FFFFFF; }
html.dark .aula-app .tarjeta p { color: #8E89A8; }
html.dark .aula-app .fila { background: #1C1833; border-color: rgba(255, 255, 255, 0.05); color: #FFFFFF; }
html.dark .aula-app input:not([type=radio]),
html.dark .aula-app select,
html.dark .aula-app textarea { background: #201A3B; border-color: #362D5E; color: #FFFFFF; }
html.dark .aula-app .btn { background: #201A3B; border-color: #362D5E; color: #C5B6F5; }
html.dark .aula-app .boton-salir { border-color: #362D5E; color: #C5B6F5; }
html.dark .aula-app .boton-salir:hover { background: #201A3B; color: #FFFFFF; }
html.dark .aula-app .btn.primary { background: #6D3DE8; border-color: #6D3DE8; color: #FFFFFF; }
html.dark .aula-app .btn.danger { background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #FCA5A5; }
html.dark .aula-app .etiqueta { background: #2A214D; color: #C5B6F5; }
html.dark .aula-app .pre { background: #161226; border: 1px solid #2F2652; color: #C5B6F5; }
html.dark .aula-app .pestanas button,
html.dark .aula-app .pestanas a { background: #201A3B; border-color: #362D5E; color: #9D96B8; }
html.dark .aula-app .pestanas button.activa,
html.dark .aula-app .pestanas a.activa { background: #3B2874; border-color: #5F42B5; color: #FFFFFF; }
html.dark .aula-app .instrucciones { background: #161226; border: 1px solid #2F2652; color: #E2DFEC; }
html.dark .aula-app .vacio { background: #1C1833; border-color: #382E60; color: #8E89A8; }
html.dark .aula-app .errores { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; }
html.dark .aula-app .aviso { background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #6EE7B7; }

@media(max-width:1100px) { .aula-app .tarjetas { grid-template-columns: repeat(2,minmax(0,1fr)); } .aula-app .aula-main { padding: 26px; } }
@media(max-width:700px) { .aula-app { grid-template-columns: minmax(0,1fr); } .aula-app .aula-main { padding: 24px 18px; } .aula-app .campos { grid-template-columns: minmax(0,1fr); } }
@media(max-width:480px) { .aula-app .tarjetas { grid-template-columns: minmax(0,1fr); } .aula-app .aula-encabezado { align-items: flex-start; flex-direction: column; } .aula-app .panel { padding: 20px; } }
</style>
