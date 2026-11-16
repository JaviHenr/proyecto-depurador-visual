SET lock_timeout = '5s';
SET statement_timeout = '15s';

BEGIN;

DO $verificar$
BEGIN
    IF current_database() <> 'depurador_local'
       OR to_regclass('public.seccion') IS NULL
       OR to_regclass('public.seccion_actividad') IS NULL
       OR to_regclass('public.migrations') IS NULL
       OR EXISTS (
           SELECT 1
           FROM information_schema.columns
           WHERE table_schema = 'public'
             AND table_name = 'seccion'
             AND column_name = 'id_actividad'
       )
       OR NOT EXISTS (
           SELECT 1
           FROM pg_constraint
           WHERE conrelid = to_regclass('public.seccion_actividad')
             AND contype = 'p'
             AND pg_get_constraintdef(oid) IN (
                 'PRIMARY KEY (id_seccion, id_actividad)',
                 'PRIMARY KEY (id_actividad, id_seccion)'
             )
       )
    THEN
        RAISE EXCEPTION 'El esquema no coincide. No se registro la migracion.';
    END IF;
END;
$verificar$;

LOCK TABLE public.migrations IN SHARE ROW EXCLUSIVE MODE;

INSERT INTO public.migrations (migration, batch)
SELECT '2026_09_21_000001_permitir_varias_actividades_por_seccion', 0
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations
    WHERE migration = '2026_09_21_000001_permitir_varias_actividades_por_seccion'
);

COMMIT;