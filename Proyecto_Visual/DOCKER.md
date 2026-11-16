# Docker para el Depurador Visual

El **Dockerfile** construye la imagen: instala Composer y las dependencias PHP,
compila Vue con Vite y Wayfinder y deja Laravel con FrankenPHP como servidor.
Node y Composer se usan durante la construcción. El **docker-compose.yaml**
conecta la aplicación con PostgreSQL y conserva los datos en volúmenes.
El parser AST y el motor semántico siguen siendo servicios externos.

Copia los archivos en `Proyecto_Visual/`, donde están `artisan`, `composer.json` y
`package.json`. El SQL nuevo va en `database/sql/`, al lado de
`Esquema_Depurador_Visual.sql`. Añade `/.env.docker` al `.gitignore` de la app
si no aparece. Si usas el instalador de texto, este añade esa línea. Si extraes
el ZIP manualmente, desde `Proyecto_Visual/` puedes hacerlo así:

```bash
grep -qxF '/.env.docker' .gitignore || printf '\n/.env.docker\n' >> .gitignore
```

## 1. Preparar Ubuntu

En un servidor que administres, instala Docker Engine con el complemento Compose
siguiendo [la guía oficial de Ubuntu](https://docs.docker.com/engine/install/ubuntu/).
Si el servidor es compartido y no tienes permisos para usar Docker, pide acceso al
administrador o prueba primero en otra máquina.

```bash
docker --version
docker compose version
docker info >/dev/null
```

Si tu instalación usa `sudo`, puedes usar `sudo docker` y, para los comandos
siguientes, definir `dc() { sudo docker compose --env-file .env.docker "$@"; }`.

## 2. Crear la configuración local

Desde `Proyecto_Visual/`:

```bash
if [ ! -e .env.docker ]; then
  (
    set -eu
    umask 077
    clave_app=$(openssl rand -base64 32)
    clave_bd=$(openssl rand -hex 24)
    sed -e "s|^APP_KEY=$|APP_KEY=base64:$clave_app|" \
        -e "s|^DB_PASSWORD=$|DB_PASSWORD=$clave_bd|" \
        .env.docker.example > .env.docker
  )
fi
chmod 600 .env.docker
dc() { docker compose --env-file .env.docker "$@"; }
dc config --quiet
```

Guarda `APP_KEY` y `DB_PASSWORD` como secretos. Conserva la misma `APP_KEY`
en actualizaciones. PostgreSQL toma su contraseña inicial al crear el volumen;
editar el archivo después no modifica la contraseña de una base ya creada.
Esta configuración usa una base nueva dentro de Docker. No apunta a la base que
usa tu despliegue actual.

## 3. Construir y arrancar por primera vez

Ejecuta en orden. Si falla un paso, corrígelo antes de seguir.

```bash
dc build app
dc up -d --wait db
dc run --rm --no-deps app php artisan migrate --force
dc up -d app
dc ps
curl -fsS --retry 10 --retry-connrefused --retry-delay 1 http://127.0.0.1:8080/up
```

Al crear el volumen por primera vez, PostgreSQL carga el SQL del proyecto
y después `Docker_Base_Nueva.sql`. Así Laravel conserva la clave primaria
`id_usuario` y ejecuta las migraciones pendientes sin volver a crear `usuario`.
No uses ese SQL de arranque sobre una base existente.

Abre [http://127.0.0.1:8080/login](http://127.0.0.1:8080/login) si trabajas
en el mismo equipo. Si Docker corre en otro Ubuntu, abre el túnel desde
**tu computador** en una terminal y déjalo abierto:

```bash
ssh -N -L 8080:127.0.0.1:8080 usuario@IP_DEL_UBUNTU
```

Para probar en tu instancia privada, puedes cargar los usuarios de demostración
del proyecto y entrar con `profesor@depurador.test` / `profesor123`:

```bash
dc exec app php artisan db:seed --class=UsuarioPruebaSeeder --force
```

El seeder carga contraseñas conocidas y puede restablecerlas si esas cuentas
ya existen. Úsalo solamente para pruebas privadas.

## 4. Publicar en un servidor propio

La configuración inicial solo publica los puertos en `127.0.0.1`.
Puedes mantenerla así para probar en paralelo con PM2 y Nginx. Si el
servidor es tuyo, quieres publicar directamente con FrankenPHP/Caddy y los
puertos 80 y 443 están libres, apunta el DNS de tu dominio al servidor,
habilita esos puertos y cambia estos valores en `.env.docker`:

```dotenv
APP_URL=https://depurador.tudominio.cl
APP_BIND=0.0.0.0
APP_HTTP_PORT=80
APP_HTTPS_PORT=443
SERVER_NAME=depurador.tudominio.cl
SESSION_SECURE_COOKIE=true
```

Luego ejecuta `dc up -d app` y verifica `https://depurador.tudominio.cl/up`.
Caddy gestionará HTTPS si el dominio y los puertos son accesibles. Si Nginx
ya ocupa esos puertos, no los vuelvas a publicar con Docker. Mantén el servicio
privado mientras preparas el cambio de proxy con quien administre el servidor.
No se cambia tu workflow actual de GitHub Actions ni el servicio PM2.

Antes de abrir un entorno nuevo al público, crea un profesor real en la
base nueva. Desde Bash, el siguiente comando pide los datos y guarda la
contraseña con el hash que genera el modelo; si el correo ya existe, falla
en lugar de cambiar su contraseña:

```bash
(
  set -e
  read -r -p 'Nombre: ' DOCKER_PROFESOR_NOMBRE
  read -r -p 'Correo: ' DOCKER_PROFESOR_EMAIL
  read -r -s -p 'Contraseña (mínimo 12 caracteres): ' DOCKER_PROFESOR_PASSWORD
  printf '\n'
  test -n "$DOCKER_PROFESOR_NOMBRE"
  test -n "$DOCKER_PROFESOR_EMAIL"
  test "${#DOCKER_PROFESOR_PASSWORD}" -ge 12
  export DOCKER_PROFESOR_NOMBRE DOCKER_PROFESOR_EMAIL DOCKER_PROFESOR_PASSWORD
  dc run --rm --no-deps \
    -e DOCKER_PROFESOR_NOMBRE -e DOCKER_PROFESOR_EMAIL -e DOCKER_PROFESOR_PASSWORD \
    app php artisan tinker --execute='App\Models\Usuario::create([
        "nombre_usuario" => getenv("DOCKER_PROFESOR_NOMBRE"),
        "email" => getenv("DOCKER_PROFESOR_EMAIL"),
        "rol" => "profesor",
        "contrasena" => getenv("DOCKER_PROFESOR_PASSWORD"),
    ]);'
)
```

Para publicar la imagen en un servicio que admite Dockerfile y PostgreSQL
gestionado, selecciona `Proyecto_Visual` como contexto de construcción y
configura las variables de `.env.docker.example` en la plataforma.
Cambia `DB_HOST=db` por el host real, usa las credenciales de esa base, mantén
`SERVER_NAME=:80` si la plataforma termina HTTPS y ajusta el proxy de confianza
de Laravel a la plataforma si esta reenvía el esquema HTTPS. La versión actual
del proyecto solo confía en proxies de loopback. Para una base gestionada
vacía, aplica los dos SQL en el mismo orden y luego `php artisan migrate --force`
como tarea de despliegue. Si ya tienes datos, restaura un respaldo en una base
de prueba y revisa `php artisan migrate:status` antes de ejecutar migraciones:
los scripts de arranque son solo para bases nuevas. Si trasladas una instalación,
conserva su `APP_KEY` y las trazas guardadas en `storage/app/trazas`.

## 5. Mantener el despliegue

```bash
# Actualizar después de traer cambios al código
dc build app
dc run --rm --no-deps app php artisan migrate --force
dc up -d app

# Revisar logs y estado de las migraciones
dc logs --tail 100 app db
dc exec app php artisan migrate:status

# Copia de seguridad de PostgreSQL; guarda también los archivos de storage/app
umask 077
dc exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > depurador.dump

# Detener sin borrar la base
dc down
```

`dc down -v` elimina los volúmenes de este proyecto, incluidos los datos de
PostgreSQL y las trazas. Para mover trazas adicionales al contenedor, puedes
usar `dc cp archivo.json app:/app/storage/app/trazas/archivo.json`.

Referencias: [Docker — Taller de Desarrollo](https://ingsoftware.notion.site/Docker-Taller-de-Desarrollo-40d46e87ee3b801b9a12ee711e6f9d0a),
[construcción por etapas](https://docs.docker.com/build/building/multi-stage/)
y [FrankenPHP en producción](https://frankenphp.dev/docs/production/).
