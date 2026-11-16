-- Solo para un PostgreSQL nuevo, inmediatamente después del esquema del proyecto.
-- El esquema inicial ya creó usuario con id_usuario, la clave que usa la aplicación.
-- Se completa la parte de autenticación de la migración original y se registra
-- como aplicada con batch 0; el resto se ejecuta con php artisan migrate.
BEGIN;

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0)
);

CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX sessions_user_id_index ON sessions (user_id);
CREATE INDEX sessions_last_activity_index ON sessions (last_activity);

CREATE TABLE migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

INSERT INTO migrations (migration, batch)
VALUES ('0001_01_01_000000_create_users_table', 0);

COMMIT;
