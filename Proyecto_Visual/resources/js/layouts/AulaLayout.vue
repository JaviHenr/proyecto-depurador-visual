<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
<<<<<<< HEAD
=======
import BarraLateral from '../components/BarraLateral.vue';
>>>>>>> origin/feature/login-page
defineProps<{ titulo: string; descripcion?: string; activo: 'secciones' | 'codigos' }>();
const page = usePage<{ actor: { id: number; nombre: string; rol: string }; aulaFlash?: string | null }>();
const sesion = useForm({});
</script>

<template>
    <Head :title="`${titulo} - Depurador Visual`" />
    <div class="aula-app">
<<<<<<< HEAD
        <aside class="aula-lateral">
            <Link href="/dashboard" class="aula-marca">Depurador Visual</Link>
            <nav aria-label="Menú principal">
                <Link href="/dashboard"><span aria-hidden="true">✨</span> Inicio</Link>
                <Link href="/secciones" :class="{ seleccionado: activo === 'secciones' }" :aria-current="activo === 'secciones' ? 'page' : undefined"><span aria-hidden="true">📖</span> Secciones</Link>
                <Link href="/codigos" :class="{ seleccionado: activo === 'codigos' }" :aria-current="activo === 'codigos' ? 'page' : undefined"><span aria-hidden="true">⌘</span> Códigos</Link>
            </nav>
            <div class="aula-perfil"><span class="avatar">{{ page.props.actor.nombre.charAt(0).toUpperCase() }}</span><div><strong>{{ page.props.actor.nombre }}</strong><small>{{ page.props.actor.rol }}</small></div></div>
        </aside>
=======
        <BarraLateral :pestana-activa="activo" />
>>>>>>> origin/feature/login-page
        <main class="aula-main">
            <header class="aula-encabezado"><div><h1>{{ titulo }}</h1><p v-if="descripcion" class="muted">{{ descripcion }}</p></div><button type="button" class="btn" :disabled="sesion.processing" @click="sesion.post('/logout')">Cerrar sesión</button></header>
            <p v-if="page.props.aulaFlash" class="aviso" role="status">{{ page.props.aulaFlash }}</p>
            <slot />
        </main>
    </div>
</template>

<style>
<<<<<<< HEAD
.aula-app { display: grid; grid-template-columns: 232px minmax(0, 1fr); min-height: 100vh; background: #faf7f3; color: #414454; font: 14px/1.6 'Segoe UI', system-ui, sans-serif; }
=======
.aula-app { display: grid; grid-template-columns: 240px minmax(0, 1fr); min-height: 100vh; background: #faf7f3; color: #414454; font: 14px/1.6 'Segoe UI', system-ui, sans-serif; }
>>>>>>> origin/feature/login-page
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
<<<<<<< HEAD
.aula-app .aula-lateral { position: sticky; top: 0; display: flex; flex-direction: column; height: 100vh; padding: 22px 12px; background: #fff; }
.aula-app .aula-marca { padding: 0 8px; font-size: 20px; font-weight: 700; color: #7455ff; white-space: nowrap; }
.aula-app .aula-lateral nav { display: grid; gap: 8px; margin-top: 32px; }
.aula-app .aula-lateral nav a { display: flex; align-items: center; gap: 13px; padding: 12px 16px; border-radius: 12px; color: #454457; }
.aula-app .aula-lateral nav a:hover { background: #f5f0fc; }
.aula-app .aula-lateral nav a.seleccionado { background: #ece4fd; color: #6542d6; font-weight: 600; }
.aula-app .aula-perfil { display: flex; gap: 10px; align-items: center; margin-top: auto; padding: 22px 8px 0; border-top: 1px solid #efedf1; }
.aula-app .aula-perfil > div { min-width: 0; }
.aula-app .aula-perfil strong { display: block; max-width: 142px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; font-weight: 500; }
.aula-app .aula-perfil small { display: block; color: #9284a0; text-transform: capitalize; }
=======
>>>>>>> origin/feature/login-page
.aula-app .avatar { display: grid; place-items: center; flex-shrink: 0; width: 35px; height: 35px; border-radius: 50%; background: #dff2ff; color: #0095e7; font-weight: 700; }
.aula-app .aula-main { width: 100%; max-width: 1400px; min-width: 0; margin: 0 auto; padding: 30px 40px 45px; }
.aula-app .aula-encabezado { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 28px; }
.aula-app .aula-encabezado p { margin-top: 6px; }
.aula-app .muted { color: #85768f; font-size: 13px; }
.aula-app .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 37px; padding: 8px 13px; border: 1px solid #dcd0ed; border-radius: 10px; background: #f7f2fe; color: #7954b8; font-size: 12px; }
.aula-app .btn.primary { background: #7455ef; border-color: #7455ef; color: white; }
.aula-app .btn.danger { color: #a44e60; background: #fff4f5; border-color: #f0d1d8; }
.aula-app .btn:hover:not(:disabled) { filter: brightness(.97); }
.aula-app .acciones { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; }
.aula-app .entre { justify-content: space-between; }
.aula-app .barra { display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between; gap: 14px; margin-bottom: 20px; }
.aula-app .panel { padding: 24px; margin-bottom: 22px; border: 1px solid #eee5f5; border-radius: 20px; background: #fff; box-shadow: 0 10px 28px #4e357005; }
.aula-app .panel h2, .aula-app .panel h3 { margin-bottom: 10px; }
.aula-app .tarjetas { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 18px; }
.aula-app .tarjeta { display: flex; flex-direction: column; gap: 12px; min-width: 0; padding: 23px; border: 1px solid #ddcfff; border-radius: 20px; background: #ebe4fa; }
.aula-app .tarjeta p { color: #80708e; font-size: 13px; }
.aula-app .tarjeta .btn { align-self: flex-start; margin-top: auto; }
.aula-app .etiqueta { display: inline-block; padding: 3px 8px; border-radius: 8px; color: #806690; background: #f2ebf9; font-size: 11px; }
.aula-app .campos { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; margin: 18px 0; }
.aula-app .campo { display: flex; flex-direction: column; gap: 6px; min-width: 0; color: #766184; font-size: 12px; }
.aula-app .completo { grid-column: 1 / -1; }
.aula-app input:not([type=radio]), .aula-app select, .aula-app textarea { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid #e0d5ed; border-radius: 10px; background: #fdfbff; color: #5f4d6c; font-size: 13px; }
.aula-app textarea { resize: vertical; }
.aula-app .busqueda { display: flex; gap: 8px; max-width: 480px; flex: 1; }
.aula-app .errores { margin: 14px 0; padding: 12px 16px; border-radius: 10px; background: #fff0f1; color: #a44d5f; font-size: 12px; }
.aula-app .aviso { margin-bottom: 20px; padding: 13px 16px; border: 1px solid #d0e5d8; border-radius: 12px; background: #edf7f0; color: #477d5d; font-size: 13px; }
.aula-app .pestanas { display: flex; flex-wrap: wrap; gap: 8px; margin: 22px 0; }
.aula-app .pestanas button { padding: 10px 16px; border: 1px solid #e2d7ef; border-radius: 10px; background: #f6f1fd; color: #8b729f; }
.aula-app .pestanas button.activa { border-color: #bba0e7; background: #e9ddfb; color: #6c46b2; }
.aula-app .lista { display: grid; gap: 12px; }
.aula-app .fila { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding: 15px 18px; border: 1px solid #eee5f4; border-radius: 12px; background: #fff; }
.aula-app .pre { overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; padding: 16px; border-radius: 10px; background: #faf7fd; color: #715881; font: 12px/1.7 ui-monospace, Consolas, monospace; }
.aula-app .editor { min-height: 340px; tab-size: 4; white-space: pre; font: 13px/1.8 ui-monospace, Consolas, monospace; }
.aula-app details { margin: 14px 0; }
.aula-app summary { cursor: pointer; color: #7e6392; font-size: 13px; }
.aula-app .vacio { padding: 32px; border: 1px dashed #ddcfea; border-radius: 18px; text-align: center; color: #8a7896; background: #fff; }
.aula-app .paginacion { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 13px; margin-top: 22px; color: #92809f; font-size: 12px; }
@media(max-width:1100px) { .aula-app .tarjetas { grid-template-columns: repeat(2,minmax(0,1fr)); } .aula-app .aula-main { padding: 26px; } }
<<<<<<< HEAD
@media(max-width:700px) { .aula-app { grid-template-columns: minmax(0,1fr); } .aula-app .aula-lateral { position: static; height: auto; padding: 18px 16px 10px; } .aula-app .aula-lateral nav { display: flex; gap: 5px; overflow-x: auto; margin-top: 18px; } .aula-app .aula-lateral nav a { white-space: nowrap; padding: 8px 11px; } .aula-app .aula-perfil { display: none; } .aula-app .aula-main { padding: 24px 18px; } .aula-app .campos { grid-template-columns: minmax(0,1fr); } }
=======
@media(max-width:700px) { .aula-app { grid-template-columns: minmax(0,1fr); } .aula-app .aula-main { padding: 24px 18px; } .aula-app .campos { grid-template-columns: minmax(0,1fr); } }
>>>>>>> origin/feature/login-page
@media(max-width:480px) { .aula-app .tarjetas { grid-template-columns: minmax(0,1fr); } .aula-app .aula-encabezado { align-items: flex-start; flex-direction: column; } .aula-app .panel { padding: 20px; } }
</style>
