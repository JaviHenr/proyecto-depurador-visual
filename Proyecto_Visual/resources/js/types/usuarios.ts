export type RolUsuario = 'profesor' | 'estudiante';

export interface DatosUsuario {
    id_usuario: number;
    nombre_usuario: string;
    email: string;
    rol: RolUsuario;
}

export interface UsuarioResumen extends DatosUsuario {
    puede_editar: boolean;
    es_actual: boolean;
}
