export interface Paginacion {
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

export interface Pagina<T> extends Paginacion { data: T[] }

export interface ActividadOpcion {
    id_actividad: number;
    nombre_actividad: string | null;
}

export interface Actividad extends ActividadOpcion {
    descripcion_actividad: string | null;
    instrucciones: string | null;
    fecha_creacion_actividad: string | null;
    secciones_count: number;
    codigos_count: number;
    permisos: { editar: boolean; desvincular: boolean };
}

export interface Seccion {
    id_seccion: number;
    id_usuario: number | null;
    actividades_count: number;
    estudiantes_count: number;
    nombre_seccion: string | null;
    descripcion_seccion: string | null;
}

export interface Estudiante {
    id_usuario: number;
    nombre_usuario: string;
    email: string;
    fecha_inscripcion: string | null;
}

export interface CodigoResumen {
    id_codigo: number;
    id_actividad: number | null;
    nombre_codigo: string | null;
    nombre_archivo: string | null;
    formato: string | null;
    fecha_creacion_codigo: string | null;
}

export interface Codigo extends CodigoResumen {
    id_usuario: number | null;
    contenido_codigo: string | null;
    huella: string; // Calculada por el servidor; no existe como columna.
}
