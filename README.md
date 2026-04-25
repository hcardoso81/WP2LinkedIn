# WP LinkedIn Poster

WP LinkedIn Poster es un plugin para WordPress que permite publicar posts directamente en una pagina de empresa de LinkedIn.

El plugin conecta WordPress con LinkedIn mediante OAuth, permite seleccionar la organizacion donde se publicara el contenido, agrega un campo personalizado para preparar el texto especifico de LinkedIn y muestra estados claros dentro del administrador de WordPress.

## Funcionalidades

### Autenticacion con LinkedIn

El plugin usa el flujo OAuth de LinkedIn para conectar la cuenta desde el panel de administracion de WordPress.

### Seleccion de organizacion

Desde la pagina de configuracion se pueden cargar las organizaciones disponibles y elegir la pagina de LinkedIn donde se publicaran los posts.

### Reconexion de cuenta

Incluye una pantalla para reconectar LinkedIn cuando el token expira. Si el plugin detecta un token vencido, muestra un aviso en el administrador con acceso directo a la reconexion.

### Contenido personalizado para LinkedIn

Agrega al editor de posts un campo ACF llamado `content_linkedin`, visible como "Contenido para LinkedIn".

Ese campo permite escribir una version especifica del contenido que se enviara a LinkedIn. El plugin valida que tenga texto real antes de permitir la publicacion.

### Publicacion desde el post

Cada post incluye un metabox "Publicar en LinkedIn" con:

* Estado de publicacion en LinkedIn.
* Indicador de si el contenido para LinkedIn esta cargado.
* Boton para publicar manualmente en LinkedIn.
* Mensajes de error o reconexion cuando corresponde.

Si el campo de contenido para LinkedIn esta vacio, el boton de publicacion queda deshabilitado.

### Imagen destacada

Cuando el post tiene imagen destacada, el plugin intenta subirla a LinkedIn y asociarla a la publicacion.

### Columnas en el listado de posts

El listado de posts muestra informacion rapida para administrar publicaciones:

* **Contenido LinkedIn**: muestra un check si el campo `content_linkedin` tiene contenido, o una X si esta vacio.
* **LinkedIn**: muestra si el post esta pendiente o si ya fue publicado. Cuando fue publicado, tambien muestra la fecha y hora.

### Prevencion de duplicados

El plugin evita publicar dos veces el mismo post en LinkedIn usando el meta `_linkedin_posted`.

### Logs

Registra informacion y errores relevantes del proceso de conexion, carga de imagenes y publicacion en LinkedIn.

## Requisitos

* WordPress 6.0+
* PHP 7.4+
* Advanced Custom Fields (ACF)
* Una aplicacion de LinkedIn configurada con permisos de publicacion
* Permisos de administracion sobre la pagina de LinkedIn donde se publicara

## Instalacion

1. Sube el plugin al directorio `/wp-content/plugins/`.
2. Activalo desde el administrador de WordPress.
3. Configura el Client ID, Client Secret y Redirect URI en LinkedIn Poster.
4. Conecta la cuenta de LinkedIn.
5. Carga las organizaciones y selecciona la pagina de LinkedIn por defecto.
6. Edita un post y completa el campo "Contenido para LinkedIn".
7. Publica manualmente desde el metabox "Publicar en LinkedIn".

## Notas de uso

El contenido enviado a LinkedIn se construye con el texto limpio del campo `content_linkedin` y el enlace permanente del post.

Para poder publicar, el post debe cumplir estas condiciones:

* Tener contenido en el campo "Contenido para LinkedIn".
* No haber sido publicado previamente en LinkedIn.
* Tener un token de LinkedIn valido.
* Tener una organizacion de LinkedIn seleccionada.

## Autor

Hernan Cardoso  
https://www.linkedin.com/in/cardosohernan/
