export type Usuario = {
    id_usuario: number;
    nombre_usuario: string;
    email: string;
    rol: 'profesor' | 'estudiante';
};

export type Auth = {
    user: Usuario | null;
};