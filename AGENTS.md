# AGENTS.md

Lineamientos para agentes que trabajen en este repositorio.

## Contexto del proyecto

Este repositorio contiene el plugin WordPress **WP LinkedIn Poster**. Su objetivo es publicar posts de WordPress en una pagina de empresa de LinkedIn usando OAuth, una organizacion seleccionada, contenido personalizado via ACF y publicacion manual desde el administrador.

El plugin esta pensado para WordPress 6.0+ y PHP 7.4+. Depende de Advanced Custom Fields para el campo `content_linkedin`.

## Estructura principal

- `wp2linkedin.php`: archivo principal del plugin, constantes, bootstrap y hook de activacion.
- `includes/bootstrap.php`: autoloader simple para clases `WPLP_` y carga de helpers.
- `includes/core/`: inicializacion general y logger.
- `includes/admin/`: pantallas de administracion, metaboxes, AJAX y columnas.
- `includes/content/`: registro/configuracion del campo de contenido para LinkedIn.
- `includes/linkedin/`: OAuth, organizaciones y publicacion en LinkedIn.
- `includes/helpers.php`: helpers compartidos para requests, token y contenido LinkedIn.
- `assets/css/admin.css` y `assets/js/admin.js`: estilos y comportamiento del admin.

## Convenciones de codigo

- Mantener el prefijo del plugin:
  - Clases: `WPLP_*`.
  - Funciones: `wplp_*` o `wp2linkedin_*`, segun el patron existente.
  - Opciones: `wp2linkedin_*` o `wplp_*`, segun corresponda.
  - Meta keys: usar el estilo existente, por ejemplo `_linkedin_posted` y `_linkedin_posted_date`.
- Las clases deben vivir en el directorio que corresponde a su responsabilidad y usar el nombre de archivo `class-...` en minusculas con guiones, compatible con el autoloader de `includes/bootstrap.php`.
- Evitar introducir frameworks, dependencias externas o cambios de arquitectura grandes sin una razon clara.
- Conservar el estilo PHP actual del proyecto: codigo simple, procedural solo para bootstrap/helpers, clases para responsabilidades principales.
- Mantener compatibilidad con PHP 7.4. No usar sintaxis exclusiva de PHP 8+.

## Seguridad WordPress

- Todos los archivos PHP deben empezar protegiendo acceso directo con:

```php
if (!defined('ABSPATH')) exit;
```

- Escapar salida HTML con funciones de WordPress:
  - `esc_html()` para texto.
  - `esc_attr()` para atributos.
  - `esc_url()` para URLs.
- Sanitizar entradas:
  - `sanitize_text_field()` para texto simple.
  - `intval()` para IDs.
  - Validar arrays antes de acceder a claves.
- En acciones AJAX, usar siempre:
  - `check_ajax_referer()` para nonce.
  - `current_user_can()` para permisos.
  - `wp_send_json_success()` o `wp_send_json_error()` para responder.
- No guardar secretos, tokens ni respuestas sensibles en archivos del repositorio.

## Flujo LinkedIn

- La publicacion debe seguir evitando duplicados con `_linkedin_posted`.
- Antes de publicar, validar:
  - Token presente y no expirado.
  - Organizacion por defecto configurada.
  - Campo `content_linkedin` con contenido real.
  - Post no publicado previamente.
- Usar los helpers de contenido:
  - `wplp_get_linkedin_content()`
  - `wplp_clean_linkedin_content()`
  - `wplp_has_linkedin_content()`
- Cuando se publiquen posts correctamente, actualizar `_linkedin_posted` y `_linkedin_posted_date`.
- Para errores de LinkedIn, devolver mensajes accionables en el admin y registrar detalles tecnicos con `WPLP_Logger`.
- Si se detecta token expirado, mantener el flujo existente: `wplp_token_expired` y pagina de reconexion.

## Admin y UX

- Las pantallas viven en `WPLP_Admin`.
- Las columnas del listado de posts viven en `WPLP_Admin_Columns`.
- Cargar assets solo donde se necesitan: pagina del plugin o pantalla de edicion/listado de posts.
- Mantener textos del admin en espanol, claros y orientados a accion.
- Si se agrega interaccion AJAX, actualizar tambien `assets/js/admin.js` y localizar datos con `wp_localize_script()` cuando haga falta.

## Logs y errores

- Usar `WPLP_Logger::info()` para eventos relevantes y `WPLP_Logger::error()` para fallos.
- Los logs deben incluir contexto util como `post_id`, `http_code`, `image_id`, `method` o `url`, evitando secretos.
- No exponer tokens, client secrets ni payloads sensibles completos.

## Verificacion recomendada

Antes de entregar cambios PHP, ejecutar lint sobre los archivos modificados:

```powershell
php -l wp2linkedin.php
php -l includes\bootstrap.php
php -l includes\helpers.php
php -l includes\core\class-wplp-plugin.php
php -l includes\admin\class-wplp-admin.php
php -l includes\admin\class-wplp-admin-columns.php
php -l includes\content\class-wplp-content-fields.php
php -l includes\linkedin\class-wplp-oauth.php
php -l includes\linkedin\class-wplp-organizations.php
php -l includes\linkedin\class-wplp-poster.php
```

Para cambios de UI/admin, revisar manualmente en WordPress:

- Pagina `LinkedIn Poster`.
- Flujo de conexion/reconexion.
- Carga y seleccion de organizaciones.
- Metabox "Publicar en LinkedIn" en posts.
- Columnas del listado de posts.

## Cuidado con cambios existentes

- No revertir cambios locales del usuario.
- Revisar `git status --short` antes de editar si el trabajo parece amplio.
- Mantener los cambios acotados al pedido actual.
- Si hay que tocar el flujo OAuth o publicacion, preferir cambios pequenos y verificables.
