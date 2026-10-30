<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BarraLateral from '../components/BarraLateral.vue';
import BotonModoOscuro from '../components/BotonModoOscuro.vue';
import { useTheme } from '../lib/useTheme';
import GestionUsuarios from '../components/ajustes/GestionUsuarios.vue';
import { edit } from '../routes/ajustes/usuarios';
import type { Pagina } from '../types/aula';
import type { UsuarioResumen } from '../types/usuarios';

defineProps<{ usuarios: Pagina<UsuarioResumen>; filtros: { q: string }; perfilId: number }>();

const page = usePage<{
    auth?: { user?: { nombre_usuario?: string; name?: string; email?: string; rol?: string } };
    usuario?: { nombre_usuario?: string; name?: string; email?: string; rol?: string };
    actor?: { id?: number; nombre?: string; rol?: string };
}>();

const { isDark, toggleTheme } = useTheme();

const currentUser = computed(() => page.props.usuario ?? page.props.auth?.user ?? null);
const userName = computed(() => currentUser.value?.nombre_usuario || currentUser.value?.name || page.props.actor?.nombre || 'Profesor de prueba');
const userEmail = computed(() => currentUser.value?.email || 'usuario@depurador.test');
const userRole = computed(() => currentUser.value?.rol || page.props.actor?.rol || 'Profesor');
const userInitial = computed(() => userName.value.trim().charAt(0).toUpperCase() || 'P');

// Preferencias configurables locales
const tamanoFuente = ref('14px');
const velocidadPaso = ref('1s');
const mostrarNumeros = ref('si');

const cerrandoSesion = ref(false);
const cerrarSesion = () => {
    if (cerrandoSesion.value) return;
    cerrandoSesion.value = true;
    router.post('/logout', {}, { onFinish: () => { cerrandoSesion.value = false; } });
};

function seleccionarTema(modo: 'claro' | 'oscuro') {
    if ((modo === 'oscuro' && !isDark.value) || (modo === 'claro' && isDark.value)) {
        toggleTheme();
    }
}
</script>

<template>
    <Head title="Ajustes - Depurador Visual" />

    <div class="ajustes-app">
        <BarraLateral pestana-activa="ajustes" />

        <main class="ajustes-principal">
            <header class="ajustes-encabezado">
                <div>
                    <h1>Ajustes</h1>
                    <p>Configuración general y personalización del entorno</p>
                </div>
                <div class="ajustes-acciones-encabezado">
                    <BotonModoOscuro />
                    <span class="avatar-encabezado" aria-hidden="true">{{ userInitial }}</span>
                    <button type="button" class="boton-salir" :disabled="cerrandoSesion" @click="cerrarSesion">
                        {{ cerrandoSesion ? 'Cerrando sesión…' : 'Cerrar Sesión' }}
                    </button>
                </div>
            </header>

            <GestionUsuarios :usuarios="usuarios" :filtros="filtros" />

            <!-- Sección: Tema y Apariencia -->
            <section class="ajustes-seccion">
                <div class="ajustes-seccion-header">
                    <div class="ajustes-icono-seccion">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </div>
                    <div>
                        <h2>Tema y Apariencia</h2>
                        <p>Selecciona cómo deseas visualizar el sistema en tu dispositivo</p>
                    </div>
                </div>

                <div class="temas-grid">
                    <!-- Tarjeta Modo Claro -->
                    <button
                        type="button"
                        class="tema-tarjeta"
                        :class="{ activo: !isDark }"
                        @click="seleccionarTema('claro')"
                    >
                        <div class="tema-preview preview-claro">
                            <div class="preview-sidebar">
                                <div class="preview-line" style="width: 70%"></div>
                                <div class="preview-line" style="width: 85%"></div>
                                <div class="preview-line" style="width: 60%"></div>
                            </div>
                            <div class="preview-content">
                                <div class="preview-box"></div>
                                <div class="preview-box" style="height: 40px"></div>
                            </div>
                        </div>
                        <div class="tema-info">
                            <span class="tema-titulo">Modo Claro</span>
                            <span v-if="!isDark" class="tema-badge">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                Activo
                            </span>
                        </div>
                        <p class="tema-desc">Tonos claros cálidos con acentos lavanda y violeta para el día a día.</p>
                    </button>

                    <!-- Tarjeta Modo Oscuro -->
                    <button
                        type="button"
                        class="tema-tarjeta"
                        :class="{ activo: isDark }"
                        @click="seleccionarTema('oscuro')"
                    >
                        <div class="tema-preview preview-oscuro">
                            <div class="preview-sidebar">
                                <div class="preview-line" style="width: 70%"></div>
                                <div class="preview-line" style="width: 85%"></div>
                                <div class="preview-line" style="width: 60%"></div>
                            </div>
                            <div class="preview-content">
                                <div class="preview-box"></div>
                                <div class="preview-box" style="height: 40px"></div>
                            </div>
                        </div>
                        <div class="tema-info">
                            <span class="tema-titulo">Modo Oscuro</span>
                            <span v-if="isDark" class="tema-badge">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                Activo
                            </span>
                        </div>
                        <p class="tema-desc">Fondo azul noche profundo (#12101F) diseñado para reducir la fatiga visual.</p>
                    </button>
                </div>
            </section>

            <!-- Sección: Perfil de Usuario -->
            <section class="ajustes-seccion">
                <div class="ajustes-seccion-header">
                    <div class="ajustes-icono-seccion">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2>Perfil y Cuenta</h2>
                        <p>Información del usuario que ha iniciado sesión actualmente</p>
                    </div>
                </div>

                <div class="perfil-card">
                    <div class="perfil-avatar">
                        {{ userInitial }}
                    </div>
                    <div class="perfil-detalles">
                        <h3 class="perfil-nombre">{{ userName }}</h3>
                        <p class="text-xs text-[#85768F] dark:text-[#8E89A8] mt-0.5">{{ userEmail }}</p>
                        <span class="perfil-rol">{{ userRole }}</span>
                        <p class="mt-3"><Link :href="edit.url(perfilId)" class="text-sm font-semibold text-[#7455ef] dark:text-[#baabff]">Editar mis datos</Link></p>
                    </div>
                </div>
            </section>

            <!-- Sección: Preferencias del Depurador -->
            <section class="ajustes-seccion">
                <div class="ajustes-seccion-header">
                    <div class="ajustes-icono-seccion">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h2>Preferencias de Depuración</h2>
                        <p>Ajustes de visualización para el editor de código y las simulaciones</p>
                    </div>
                </div>

                <div class="opciones-grid">
                    <div class="opcion-item">
                        <label class="opcion-label" for="fuente-codigo">Tamaño de fuente del editor</label>
                        <p class="opcion-desc">Define el tamaño del texto en el código Java.</p>
                        <div class="opcion-control">
                            <select id="fuente-codigo" v-model="tamanoFuente" class="opcion-select">
                                <option value="12px">Pequeño (12px)</option>
                                <option value="14px">Estándar (14px)</option>
                                <option value="16px">Grande (16px)</option>
                            </select>
                        </div>
                    </div>

                    <div class="opcion-item">
                        <label class="opcion-label" for="velocidad-animacion">Velocidad de paso a paso</label>
                        <p class="opcion-desc">Tiempo de espera automático entre cada iteración.</p>
                        <div class="opcion-control">
                            <select id="velocidad-animacion" v-model="velocidadPaso" class="opcion-select">
                                <option value="1.5s">Pausado (1.5s)</option>
                                <option value="1s">Normal (1.0s)</option>
                                <option value="0.5s">Rápido (0.5s)</option>
                            </select>
                        </div>
                    </div>

                    <div class="opcion-item">
                        <label class="opcion-label" for="numeros-linea">Números de línea</label>
                        <p class="opcion-desc">Mostrar indicador numérico al costado del código.</p>
                        <div class="opcion-control">
                            <select id="numeros-linea" v-model="mostrarNumeros" class="opcion-select">
                                <option value="si">Activado</option>
                                <option value="no">Desactivado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección: Acerca del Sistema -->
            <section class="ajustes-seccion">
                <div class="ajustes-seccion-header">
                    <div class="ajustes-icono-seccion">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2>Acerca del Proyecto</h2>
                        <p>Depurador Visual de Algoritmos e Iteraciones</p>
                    </div>
                </div>

                <div class="text-xs text-[#85768F] dark:text-[#8E89A8] leading-relaxed space-y-2">
                    <p><strong class="text-[#2D264B] dark:text-[#FFFFFF]">Versión:</strong> 1.0.0 (Actualización Modo Oscuro)</p>
                    <p><strong class="text-[#2D264B] dark:text-[#FFFFFF]">Propósito:</strong> Herramienta pedagógica orientada a facilitar la comprensión de estructuras iterativas y seguimiento visual de memoria en Java.</p>
                </div>
            </section>
        </main>
    </div>
</template>
