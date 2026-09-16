<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Auth, Usuario } from '@/types';

interface NavItem {
    id: string;
    label: string;
}

interface Tarjeta {
    id: number;
    titulo: string;
    descripcion: string;
}

interface Actividad {
    id: number;
    titulo: string;
    tiempo: string;
}

const props = defineProps<{
    auth?: Auth;
    usuario?: Usuario;
}>();

const currentUser = computed(() => props.usuario ?? props.auth?.user ?? null);
const userName = computed(() => currentUser.value?.nombre_usuario || 'Usuario');
const userRole = computed(() => currentUser.value?.rol || '');
const userInitial = computed(() => {
    return currentUser.value?.nombre_usuario?.trim()?.charAt(0)?.toUpperCase() || 'U';
});

const logout = () => {
    router.post('/logout');
};

// Estado para la navegación lateral
const navItems = ref<NavItem[]>([
    { id: 'inicio', label: 'Inicio'},
    { id: 'secciones', label: 'Secciones'},
    { id: 'actividades', label: 'Actividades'},
    { id: 'ajustes', label: 'Ajustes' }
]);
const activeTab = ref('inicio');

// Tarjetas principales del dashboard
const tarjetas = ref<Tarjeta[]>([
    {
        id: 1,
        titulo: 'Códigos disponibles',
        descripcion: 'Consulta los códigos y programas disponibles para iniciar una sesión de depuración.'
    },
    {
        id: 2,
        titulo: 'Actividades',
        descripcion: 'Actividades a realizar y estado de las mismas.'
    },
    {
        id: 4,
        titulo: 'Favoritos',
        descripcion: 'Accede rápidamente a tus actividades favoritas.'
    }
]);

// Datos de ejemplo para actividad reciente
const actividadReciente = ref<Actividad[]>([
    { id: 1, titulo: 'Documento sin título', tiempo: 'Hace 2 horas'},
    { id: 2, titulo: 'Planilla de actividad', tiempo: 'Ayer'}
]);

const seleccionarTarjeta = (tarjeta: Tarjeta) => {
    console.log('Tarjeta seleccionada:', tarjeta.titulo);
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="layout-app">
        <!-- Barra lateral de navegación -->
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-text">
                    <span class="brand-title">Depurador Visual</span>
                </div>
            </div>

            <nav class="nav-menu">
                <button
                    v-for="item in navItems"
                    :key="item.id"
                    class="nav-btn"
                    :class="{ active: activeTab === item.id }"
                    @click="activeTab = item.id"
                >
                    <span>{{ item.label }}</span>
                </button>
            </nav>

            <!-- Footer con usuario y cerrar sesión -->
            <div class="sidebar-footer">
                <div v-if="currentUser" class="user-chip">
                    <div class="user-avatar-sm">{{ userInitial }}</div>
                    <div class="user-info">
                        <span class="user-name">{{ userName }}</span>
                        <span class="user-role">{{ userRole }}</span>
                    </div>
                </div>
                <button
                    @click="logout"
                    type="button"
                    class="nav-btn logout-nav-btn"
                    title="Cerrar Sesión"
                >
                    <span>Cerrar Sesión</span>
                </button>
            </div>
        </aside>

        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Encabezado superior -->
            <header class="top-header">
                <div class="saludo">
                    <h1>Hola, <span v-if="currentUser"> {{ userName }}</span></h1>
                    <p>Aquí puedes realizar actividades de depuración.</p>
                </div>
                <div class="header-actions">
                    <div
                        class="avatar-user"
                        :title="currentUser ? `${userName} (${userRole})` : 'Perfil'"
                    >
                        {{ userInitial }}
                    </div>
                    <button
                        @click="logout"
                        type="button"
                        class="btn-logout"
                        title="Cerrar Sesión"
                    >
                        Cerrar Sesión
                    </button>
                </div>
            </header>

            <!-- Cuadrícula de módulos/tarjetas -->
            <section class="cards-grid">
                <article
                    v-for="card in tarjetas"
                    :key="card.id"
                    class="card"
                    @click="seleccionarTarjeta(card)"
                >
                    <h3>{{ card.titulo }}</h3>
                    <p>{{ card.descripcion }}</p>
                </article>
            </section>

            <!-- Bloque inferior de actividad -->
            <section class="recent-section">
                <h2>Actividad reciente</h2>
                <div class="recent-list">
                    <div
                        v-for="item in actividadReciente"
                        :key="item.id"
                        class="recent-item"
                    >
                        <div class="recent-item-info">
                            <span>{{ item.titulo }}</span>
                        </div>
                        <span class="recent-item-time">{{ item.tiempo }}</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* Variables locales en tonos pastel */
.layout-app {
    --bg-app: #FAF7F2;          /* Fondo crema cálido y sutil */
    --bg-card: #FFFFFF;
    --text-dark: #3D3D4E;       /* Texto suave, no negro puro */
    --text-muted: #8E8E9F;

    --pastel-menta: #E2F5EA;
    --pastel-lila: #EBE4FB;
    --pastel-durazno: #FFE8E0;
    --pastel-amarillo: #FFF3D6;
    --pastel-celeste: #E0F2FE;

    --radius-lg: 20px;
    --radius-md: 12px;
    --shadow-soft: 0 8px 24px rgba(138, 149, 158, 0.08);

    display: flex;
    min-height: 100vh;
    background-color: var(--bg-app);
    color: var(--text-dark);
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Sidebar */
.sidebar {
    width: 240px;
    background-color: var(--bg-card);
    padding: 32px 20px;
    display: flex;
    flex-direction: column;
    gap: 32px;
    box-shadow: var(--shadow-soft);
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.25rem;
    font-weight: 700;
    color: #6C5CE7;
    padding-left: 8px;
}

.nav-menu {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.nav-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: var(--radius-md);
    border: none;
    background: transparent;
    color: var(--text-dark);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s;
    text-align: left;
    width: 100%;
}

.nav-btn:hover {
    background-color: var(--pastel-lila);
    color: #5544BE;
}

.nav-btn.active {
    background-color: var(--pastel-lila);
    color: #5544BE;
}

.sidebar-footer {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
}

.user-avatar-sm {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background-color: var(--pastel-celeste);
    color: #0284C7;
    font-size: 0.85rem;
    font-weight: 700;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.user-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.user-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: capitalize;
}

.logout-nav-btn {
    color: #991B1B;
}

.logout-nav-btn:hover {
    background-color: #FEF2F2;
    color: #7F1D1D;
}

/* Área de contenido */
.main-content {
    flex: 1;
    padding: 40px 48px;
    overflow-y: auto;
}

.top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 36px;
}

.saludo h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.saludo p {
    color: var(--text-muted);
    font-size: 0.95rem;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.avatar-user {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: var(--pastel-celeste);
    color: #0284C7;
    font-weight: 700;
    display: grid;
    place-items: center;
    box-shadow: var(--shadow-soft);
    cursor: pointer;
}

.btn-logout {
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #554e9b;
    background-color: #FEF2F2;
    border: 1px solid #8cb8d1;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: background-color 0.2s, border-color 0.2s, color 0.2s;
}

.btn-logout:hover {
    background-color: #FEE2E2;
    border-color: #FCA5A5;
    color: #7F1D1D;
}

/* Grilla de tarjetas */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 36px;
}

.card {
    background-color: var(--pastel-lila);
    padding: 26px 24px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    border: 1px solid rgba(108, 92, 231, 0.12);
}

.card:hover {
    transform: translateY(-4px);
    background-color: #E2D7F8;
    border-color: rgba(108, 92, 231, 0.25);
    box-shadow: 0 14px 28px rgba(108, 92, 231, 0.12);
}

.card h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #4834D4;
    margin-bottom: 8px;
}

.card p {
    font-size: 0.9rem;
    color: #5D5D72;
    line-height: 1.5;
}

/* Lista reciente */
.recent-section {
    background-color: var(--bg-card);
    padding: 28px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
}

.recent-section h2 {
    font-size: 1.15rem;
    margin-bottom: 18px;
}

.recent-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.recent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--bg-app);
    padding: 14px 18px;
    border-radius: var(--radius-md);
    font-size: 0.95rem;
}

.recent-item-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.recent-item-time {
    font-size: 0.85rem;
    color: var(--text-muted);
}
</style>