# VoluntaRed

Plataforma digital para la vinculación entre voluntarios y fundaciones.
Proyecto de la Facultad de Ingeniería, Universidad de Córdoba (Montería, 2026).

Desarrollada en **PHP 8 + MySQL/MariaDB** con arquitectura MVC propia, API REST en JSON,
autenticación con JWT y una interfaz web responsiva con Bootstrap 5.

## Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql`, `mbstring`, `fileinfo`, `zip` y `openssl` (XAMPP 8.2 las trae).
- MySQL 8 o MariaDB 10.4+ (XAMPP).
- Composer solo si quieres reinstalar dependencias (la carpeta `vendor/` ya está incluida).

## Instalación con Docker (un solo comando)

Requisitos: Docker Desktop. En la carpeta `voluntared` ejecuta:

```
docker compose up -d --build
```

La primera vez construye la imagen, crea la base de datos y carga los datos de demostración
(tarda uno o dos minutos). Después abre:

| Servicio | Dirección | Acceso |
|----------|-----------|--------|
| VoluntaRed | <http://localhost:8080> | cuentas de demostración (abajo) |
| Correos de desarrollo | <http://localhost:8080/dev/correos> | — |
| phpMyAdmin | <http://localhost:8081> | usuario `voluntared`, contraseña `voluntared2026` |
| MariaDB (desde tu equipo) | `localhost:3307` | mismo usuario y contraseña |

Comandos útiles:

```
docker compose logs -f app                            # ver el registro de la aplicación
docker compose exec app vendor/bin/phpunit --testdox  # ejecutar las pruebas unitarias
docker compose exec app php database/instalar.php     # reinstalar los datos de demostración
docker compose exec app php database/respaldo.php     # respaldo en storage/respaldos
docker compose down                                   # detener (los datos se conservan)
docker compose down -v                                # detener y borrar la base de datos y los archivos
```

Los puertos, contraseñas y el correo SMTP se pueden cambiar en un archivo `.env` junto a
`docker-compose.yml`, por ejemplo:

```
VR_PUERTO_WEB=9090
VR_DB_CLAVE=otraClaveSegura
VR_CLAVE=una-clave-larga-y-secreta-para-firmar-los-tokens-jwt
VR_CORREO_DRIVER=smtp
VR_SMTP_USUARIO=tucorreo@gmail.com
VR_SMTP_CLAVE=contraseña-de-aplicación
```

Las contraseñas de la base de datos solo se aplican cuando se crea por primera vez; si ya la
levantaste, usa `docker compose down -v` antes de cambiarlas.

## Inicio de sesión con Google (Firebase)

Además del correo y la contraseña, las pantallas de inicio de sesión y de registro de voluntario
tienen el botón **Continuar con Google**. Firebase Authentication solo se usa para abrir la ventana
de Google: el navegador envía el token de Firebase a `POST /auth/google`, PHP valida su firma con
las llaves públicas de Google (`app/Services/FirebaseAuthService.php`) y a partir de ahí la sesión
es la misma de siempre (JWT propio, roles y suspensión en MySQL).

- Si el correo de Google ya tiene cuenta (voluntario, fundación o administrador), entra a esa cuenta.
- Si no existe, se crea una cuenta de **voluntario** con el correo ya verificado. Las fundaciones se
  registran con el formulario porque deben adjuntar su documento soporte.

El proyecto de Firebase es `voluntared-unicordoba` (sus datos públicos están en `app/config.php`).
Para que el botón funcione, el proveedor **Google** debe estar habilitado en
Firebase › Authentication › Método de inicio de sesión. Para desactivar el botón, deja vacío
`VR_FIREBASE_PROJECT_ID`.

## Instalación con XAMPP

1. Copia la carpeta `voluntared` dentro de `C:\xampp\htdocs\`.
2. Inicia **Apache** y **MySQL** desde el panel de control de XAMPP.
3. Abre una terminal en `C:\xampp\htdocs\voluntared` y crea la base de datos con los datos de demostración:

   ```
   php database/instalar.php
   ```

   (Usa `--sin-demo` para crear solo los catálogos y la cuenta del administrador.)
   También puedes importar `database/voluntared.sql` desde phpMyAdmin.
4. Abre <http://localhost/voluntared/>

### Alternativa: servidor embebido de PHP

```
php database/instalar.php
php -S localhost:8000 -t public public/index.php
```

y abre <http://localhost:8000>.

## Cuentas de demostración

| Rol           | Correo                       | Contraseña      |
|---------------|------------------------------|-----------------|
| Administrador | admin@voluntared.co          | Admin2026       |
| Fundación     | laura@sembrandofuturo.org    | Fundacion2026   |
| Fundación     | carlos@riosinuverde.org      | Fundacion2026   |
| Fundación (pendiente) | jorge@manosqueayudan.org | Fundacion2026 |
| Voluntario    | ana.gomez@correo.com         | Voluntario2026  |
| Voluntario    | juanp.torres@correo.com      | Voluntario2026  |
| Voluntario sin perfil | luis.fernandez@correo.com | Voluntario2026 |

## Correo electrónico

Por defecto (`correo.driver = 'log'` en `app/config.php`) los correos **no se envían**: se guardan en
`storage/correos/` y se pueden leer en <http://localhost:8000/dev/correos> (enlaces de verificación,
códigos de restablecimiento y notificaciones). Para enviarlos de verdad configura el bloque `correo`
con `driver = 'smtp'` y los datos de tu servidor SMTP (por ejemplo, Gmail con contraseña de aplicación).

## Pruebas unitarias

```
vendor\bin\phpunit --testdox
```

Las pruebas usan una base de datos aparte (`voluntared_test`) que se crea automáticamente.

## Estructura

```
app/
  Core/         Router, base de datos (PDO), JWT, CSRF, validación, correo, subidas
  Models/       Acceso a datos por tabla
  Services/     Reglas de negocio (postulaciones, cupos, participación, reportes...)
  Controllers/  Controladores web y API REST (Api/)
  Views/        Plantillas PHP (Bootstrap 5)
  config.php    Configuración
  routes.php    Rutas
database/       schema.sql, instalador y datos de demostración
public/         Front controller, CSS, JS, librerías y archivos subidos
storage/        Documentos soporte (privados) y correos de desarrollo
tests/Unit/     Pruebas PHPUnit
```

## API REST

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | /api/auth/login | Devuelve un JWT (`{"email","contrasena"}`) |
| POST | /api/auth/logout | Revoca el token |
| GET  | /api/oportunidades | Búsqueda paginada (`q, categoria, ciudad, modalidad, desde, habilidad, pagina`) |
| GET  | /api/oportunidades/{id} | Detalle |
| POST | /api/oportunidades/{id}/postulaciones | Postularse (voluntario) |
| GET  | /api/notificaciones | Notificaciones del usuario |
| POST | /api/notificaciones/{id}/leer | Marcar como leída |
| GET  | /api/contadores | No leídos (notificaciones y mensajes) |
| GET/POST | /api/conversaciones/{id}/mensajes | Leer / enviar mensajes del chat |
| GET  | /api/estadisticas | Indicadores (administrador) |

Autenticación: cabecera `Authorization: Bearer <token>`.
