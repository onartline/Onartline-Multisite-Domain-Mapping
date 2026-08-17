# Onartline Multisite Domain Mapping

Asigna dominios personalizados a sitios dentro de una red WordPress Multisitio.

| | |
|---|---|
| **Requiere WordPress** | 7.0 o superior |
| **Requiere PHP** | 8.3 o superior |
| **Probado hasta** | 7.1 |
| **Licencia** | GPLv2 o posterior |

## Descripción

Onartline Multisite Domain Mapping permite asignar cualquier dominio a un sitio dentro de su red WordPress Multisitio. Es un plugin ligero, fácil de configurar y diseñado tanto para principiantes como para administradores experimentados.

### Características

- Asignación de múltiples dominios a cualquier sitio de la red
- Definición de un dominio principal con redirección automática
- Forzado de HTTPS por dominio o de forma global
- Soporte de redirección 301 para dominios secundarios
- Visualización de información DNS para administradores de sitio
- Gestión de dominios a nivel de sitio (opcional, controlado por el Super Administrador)

### Requisitos

- PHP 8.3 o superior
- WordPress 7.0 o superior
- Instalación WordPress Multisitio

## Instalación

### Importante – Por favor, lea antes de instalar

Este plugin se recomienda para **nuevas instalaciones de red WordPress Multisitio**.

Instalar Onartline Multisite Domain Mapping en una **red Multisitio ya existente y activa no es recomendable** y se realiza bajo su propia responsabilidad. Puede interferir con configuraciones de dominio existentes, redirecciones u otros plugins con funciones similares.

Si ya administra una red Multisitio y desea utilizar este plugin, se recomienda encarecidamente configurar primero una **instalación Multisitio nueva y limpia**, y luego **migrar o importar su contenido y datos existentes** a esa nueva instalación, en lugar de añadir este plugin a su red activa actual.

### 1. Subir el plugin

Suba la carpeta `onartline-multisite-domain-mapping` a `/wp-content/plugins/` o instálelo directamente desde el Administrador de Red de WordPress en **Plugins → Añadir nuevo**.

### 2. Activar el plugin

Active el plugin desde **Administración de Red → Plugins → Activar para la red**.

### 3. Configurar sunrise.php

Onartline Multisite Domain Mapping requiere que `sunrise.php` se cargue antes de que WordPress se inicialice.

**Instalación automática:**
Si `wp-content/` tiene permisos de escritura, el plugin copia `sunrise.php` automáticamente durante la activación. Verá un mensaje de éxito en la Administración de Red.

**Instalación manual:**
Si la copia automática falla, copie `sunrise.php` manualmente:

1. Copie `sunrise.php` desde la carpeta del plugin a `/wp-content/sunrise.php`
2. Añada la siguiente línea a su `wp-config.php` – justo antes de `require_once ABSPATH . 'wp-settings.php';`:

define( 'SUNRISE', true );

### 4. Configurar wp-config.php

Asegúrese de que la siguiente línea esté presente en su `wp-config.php`:

define( 'SUNRISE', true );

### 5. ⚠️ Usuarios de Plesk – Desactivar "Dominio preferido"

Si su servidor utiliza Plesk, **debe** desactivar la configuración de "Dominio preferido" para cada dominio que desee asignar. De lo contrario, Plesk interceptará la redirección antes de que WordPress pueda procesarla, causando bucles de redirección o asignaciones incorrectas.

1. Inicie sesión en Plesk
2. Vaya a **Sitios web y dominios → su dominio → Configuración de hosting**
3. Establezca **Dominio preferido** en **Ninguno**
4. Guarde la configuración

### 6. Añadir su primera asignación de dominio

1. Vaya a **Administración de Red → Domain Mapping → Añadir dominio**
2. Seleccione el sitio de destino
3. Introduzca el dominio (sin `http://` ni `https://`)
4. Opcionalmente, defínalo como Dominio Principal y active HTTPS
5. Guarde

### 7. Configurar DNS

Apunte su dominio a su servidor configurando los siguientes registros DNS:

- **Registro A** – Nombre: `@` – Valor: La dirección IP de su servidor
- **Registro CNAME** – Nombre: `www` – Valor: Su dominio principal o CNAME del servidor

Los valores necesarios se muestran en **Administración de Red → Domain Mapping → Configuración**.

### 8. Desinstalación

Al desactivar y eliminar Onartline Multisite Domain Mapping desde **Administración de Red → Plugins**, el plugin elimina automáticamente:

- Los archivos del plugin
- El archivo `sunrise.php` de `/wp-content/`
- Las tablas de la base de datos (solo si se activó "Eliminar datos al desinstalar" en la configuración del plugin)

**Importante – paso manual requerido**

El plugin **no puede eliminar automáticamente** la siguiente línea de su `wp-config.php`:

define( 'SUNRISE', true );

Esta línea se añadió manualmente durante la instalación y también debe **eliminarse manualmente** después de desinstalar el plugin. Si esta línea permanece en `wp-config.php` después de haber eliminado `sunrise.php`, WordPress intentará cargar un archivo que ya no existe, lo que provocará advertencias como la siguiente:

Warning: include_once(.../wp-content/sunrise.php): Failed to open stream: No such file or directory

y posiblemente errores de "headers already sent" en la página de inicio de sesión u otras partes del sitio.

**Para solucionarlo:** Abra su `wp-config.php` y elimine (o comente) la línea `define( 'SUNRISE', true );`, luego guarde el archivo.

## Capturas de pantalla

1. Añadir dominio – formulario para crear nuevas asignaciones de dominio
2. Resumen de Domain Mapping – gestión de todos los dominios asignados
3. Configuración de Domain Mapping – HTTPS, redirecciones e información DNS

## Registro de cambios

### 1.0.0
- Lanzamiento inicial

## Preguntas frecuentes

**¿Puedo instalar este plugin en una red Multisitio ya existente y activa?**
Esto no se recomienda y se realiza bajo su propia responsabilidad. Onartline Multisite Domain Mapping está diseñado para nuevas instalaciones Multisitio. Si ya administra una red Multisitio activa, se recomienda encarecidamente configurar primero una instalación nueva y migrar su contenido existente a ella, en lugar de añadir este plugin a su red actual. Consulte la nota al inicio de la sección **Instalación** para más detalles sobre el enfoque recomendado.

**El dominio redirige en bucle – ¿qué debo hacer?**
Compruebe si "Dominio preferido" está configurado en Plesk. Establézcalo en "Ninguno". Verifique también que `define( 'SUNRISE', true );` esté presente en `wp-config.php`.

Si está utilizando la función de redirección 301 del plugin, revise la configuración de hosting para ese dominio específico (por ejemplo, en Plesk, cPanel u otros paneles de hosting) y desactive cualquier regla de redirección existente si es necesario.

Si ya existen redirecciones 301 configuradas a nivel de hosting para ese dominio y desea mantenerlas, desactive en su lugar la opción de redirección 301 en la configuración del plugin – de lo contrario, se producirá un bucle de redirección.

**sunrise.php no se copió automáticamente – ¿qué hago ahora?**
Copie `sunrise.php` manualmente desde la carpeta del plugin a `/wp-content/sunrise.php` y añada `define( 'SUNRISE', true );` a su `wp-config.php`.

**El plugin no funciona en mi sitio web – ¿por qué?**
Onartline Multisite Domain Mapping requiere una instalación WordPress Multisitio y PHP 8.3+. Las instalaciones de sitio único no son compatibles.

**¿Pueden los administradores de sitio gestionar sus propios dominios?**
Sí – el Super Administrador puede habilitar esto en **Administración de Red → Domain Mapping → Configuración → Domain Mapping para administradores de sitio**.

**¿El plugin admite actualizaciones automáticas?**
Sí – una vez publicado en el repositorio de plugins de WordPress, las actualizaciones automáticas son totalmente compatibles.

**Desinstalé el plugin, pero ahora veo errores relacionados con sunrise.php o "headers already sent" – ¿qué ocurrió?**
Esto ocurre si la línea `define( 'SUNRISE', true );` no se eliminó de `wp-config.php` después de desinstalar el plugin. Dado que `sunrise.php` ya no existe tras la desinstalación, WordPress falla al intentar cargarlo. Simplemente elimine esa línea de `wp-config.php` para resolver el problema.