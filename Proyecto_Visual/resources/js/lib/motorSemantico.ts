import { store } from '../actions/App/Http/Controllers/Aula/ValidacionSemanticaController';

export type ReferenciaTraza = ({ codigo: number } | { archivo: string }) & { huella_traza: string };

export interface ResultadoValidacion {
    numeroPaso: number;
    correcto: boolean;
    valorEsperado: number | boolean;
    tipoError: string | null;
    mensaje: string;
}

export type ValidarPaso = (traza: ReferenciaTraza, numeroPaso: number, respuesta: number | boolean, signal: AbortSignal) => Promise<ResultadoValidacion>;

export const validarPaso: ValidarPaso = async (traza, numeroPaso, respuesta, signal) => {
    const cookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)?.[1];
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (cookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(cookie);

    const peticion = await fetch(store.url(), {
        method: 'POST', headers, credentials: 'same-origin', signal,
        body: JSON.stringify({ ...traza, numeroPaso, respuesta }),
    });
    if (peticion.status === 401 || peticion.status === 419) {
        throw new Error('Tu sesión caducó. Vuelve a iniciar sesión para continuar.');
    }
    if (peticion.status === 429) {
        throw new Error('Hay demasiados intentos seguidos. Espera un momento y vuelve a confirmar.');
    }
    const datos = await peticion.json().catch(() => null);
    if (!peticion.ok || !datos) {
        const detalle = datos?.errors ? Object.values(datos.errors).flat()[0] : datos?.message;
        throw new Error(typeof detalle === 'string' ? detalle : 'No se pudo confirmar el paso. Vuelve a intentarlo.');
    }
    return datos;
};
