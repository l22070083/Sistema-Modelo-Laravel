# Migración Yii → Laravel

Estado al 2 de octubre de 2026. Proyecto instalado en `C:\wamp64\www\Sistema-Modelo-Laravel`, con Laravel 13, PHP 8.5, Blade y Bootstrap local. Las versiones de dependencias están fijadas en composer.lock.

## Datos e aislamiento

Base original Yii: `tutoriasmv`. Copia Laravel: `tutoriasmv_laravel_migracion_20261002`. El usuario MySQL de la aplicación tiene SELECT/INSERT/UPDATE/DELETE exclusivamente sobre la copia; no puede acceder a la original ni modificar el esquema. AppServiceProvider rechaza también la conexión a la base original.

Se copiaron las 17 tablas existentes mediante su definición SQL original, preservando IDs, hashes bcrypt, relaciones, fechas, respuestas y bitácoras. Se verificaron filas por SHA-256, estructura y 21 relaciones sin huérfanos. Se conservaron los motores históricos (15 InnoDB y 2 MyISAM). Las pruebas MySQL hacen rollback: no modifican las filas importadas; pueden avanzar los contadores AUTO_INCREMENT del destino.

Respaldo e inventario privado: `C:\wamp64\www\Sistema-Modelo\backups\laravel_20261002\`. Informe sin datos personales: `C:\wamp64\www\Sistema-Modelo\migration\verification.json`. Repetir la comprobación, sin escrituras en las bases:

```powershell
& 'C:/wamp64/bin/php/php8.5.0/php.exe' 'C:/wamp64/www/Sistema-Modelo/migration/verify-mysql.php'
```

Esta verificación confirma la instantánea inicial. Después de operar normalmente en Laravel, se esperan diferencias de filas en el destino: no restaurar ni sobrescribir datos para hacerla pasar. `copy-mysql.php` se niega a sobrescribir un destino existente. No ejecutar migrate:fresh, rollback ni seeders del template sobre la copia importada. Las migraciones preparadas omiten las tablas históricas existentes; el esquema de usuarios utilizado es `user`, no `users`.

Revisión posterior: las filas de las 17 tablas importadas del destino siguen coincidiendo con la instantánea. Yii tiene una entrada adicional en `auditoria_sistema` (5 frente a 4); la verificación estricta informa esa diferencia en el origen. Deben considerar los cambios posteriores en Yii al preparar el corte definitivo, sin sobrescribir ninguna de las bases.

## Funciones migradas

- Alta manual exclusiva del administrador: alumnos (`/alumnos/crear`), coordinadores (`/coordinadores`) y administradores (`/administradores`). Las cuentas quedan activas con credenciales locales, contraseña confirmada y rol fijado por cada formulario. Cada alta registra al responsable en auditoría dentro de la misma transacción. Los coordinadores nuevos comienzan con las cinco secciones vigentes sin permisos y se redirigen a la asignación de grupos/permisos. El registro público local y Microsoft conserva su aprobación pendiente.
- «Crear registros» en Mi Panel reúne los accesos a las altas de cuentas, licenciaturas, grupos, géneros, encuestas y preguntas. El enlace Yii `alumno/create` tiene equivalencia explícita y conserva los controles administrativos.
- Login con los mismos usernames y hashes de Yii; sesiones propias, CSRF y límite de intentos.
- Registro local como alumno pendiente (rol 3, estado 5), aviso de espera, verificación de correo y recuperación con token de un uso. La verificación no activa coordinadores ni reemplaza la aprobación administrativa.
- Notificaciones como único apartado de solicitudes: alta individual, seleccionada y global sobre todas las páginas. No reactiva bajas ni coordinadores.
- Solo administrador crea, designa cuentas registradas, edita, da de baja y reactiva coordinadores; asigna grupos y permisos por sección. La designación conserva el ID y vínculo Microsoft, revoca sesiones anteriores y comienza con cinco permisos de sección desactivados. Editar exige también permiso de consulta. La baja revoca la sesión y libera grupos.
- Licenciaturas, grupos y géneros: creación/edición; estados de baja/reactivación para licenciaturas y grupos. El esquema histórico de géneros no contiene estado.
- Encuestas de salud activas, preguntas, respuestas Si/No, paginación de 10, progreso, autoguardado y finalización completa. CHASIDE se excluye de las funciones, sin borrar ninguna fila histórica.
- Expediente propio del alumno y gestión institucional por secciones; validación, detección de cambios concurrentes, notas, archivo/restauración, bloqueo e historial.
- El formulario de salud contiene solamente Datos personales y Cuestionario de salud. Se retiraron los apartados APNP/APP, su captura, resumen clínico, permisos y campos en los PDF. El tipo de sangre y el factor RH pertenecen a Datos personales y respetan sus permisos de consulta/edición. Las columnas y valores históricos retirados se conservan en la copia importada; no se solicitan ni se modifican desde los formularios nuevos.
- Clasificación: Salud Física (1, 3, 4, 5); Atención Emocional/socioemocional (2, 9, 10 desfavorable y apoyo Psicológico en 11); Atención Psicopedagógica (6, 7 y De aprendizaje en 11); Sin dato de alarma cuando no se detecta categoría. Puede haber varias categorías.
- Administrador y coordinador con permiso de edición de clasificación pueden determinar categorías y atención prioritaria manualmente, con motivo, autor e historial. Esta valoración prevalece sobre la automática y no se pierde por una edición posterior del alumno.
- Resultados muestran las categorías y la valoración institucional. Los puntajes numéricos históricos se conservan; no se calculan puntajes nuevos sin una fórmula aprobada. Las alertas de encuestas cuentan afirmativas de riesgo medio/alto por separado.
- Atención con cuatro colores, constancias con folio y fechas, expedientes PDF, reportes Excel/PDF filtrados y auditoría de exportaciones con motivo. Los permisos limitan también los campos exportados.

## Estilos y paneles

Se reutilizan el escudo, la fotografía de la escuela, los colores y la tipografía de Yii. El portal público y estudiantil está en `/`; el acceso estudiantil, en `/login`; y el administrativo, en `/acceso-administrativo`. Ambos accesos determinan el destino por el rol guardado: alumnos en `/inicio`, administrador/coordinador en `/panel`.

El panel institucional tiene bienvenida, tarjetas de seguimiento y atención prioritaria, gráficas y búsqueda de alumnos. Sus datos respetan los permisos de consulta personal y clasificación. La segunda gráfica muestra categorías de salud y no ofrece CHASIDE. El expediente del alumno organiza el resumen clínico y las secciones desplegables siguiendo la referencia proporcionada, sin revelar notas ni motivos internos.

La revisión visual automatizada del sitio local no estuvo disponible porque se denegó el acceso del navegador. Las pruebas de Laravel comprueban el contenido, los permisos y los recorridos; no acreditan una comparación visual de las capturas.

## URLs Yii admitidas en el servidor Laravel

Se acepta `/?r=...`, `/index.php?r=...`, `/backend/web/index.php?r=...` y `/frontend/web/index.php?r=...`. Estos enlaces deben apuntar al origen Laravel (`http://127.0.0.1:8002` en esta vista). La instalación Yii que sigue en el puerto 80 conserva su comportamiento.

| Acción Yii | Equivalencia Laravel |
| --- | --- |
| site/index, login, logout | Inicio, login y logout (POST) |
| site/signup, completar-perfil | Registro y perfil |
| site/verify-email, resend-verification-email | Verificación con token y formulario/reenvío |
| site/request-password-reset, reset-password | Recuperación y cambio con token |
| site/microsoft-login, microsoft-callback, microsoft-vincular | OAuth y vínculo con contraseña local; deshabilitado hasta validación real |
| notificacion/index, alumno/aprobar | Notificaciones y aprobación POST |
| alumno/index, view, update | Alumnos, consulta y edición administrativa del perfil |
| coordinador/index, create, view, update, permisos, asignar-grupo, delete, reactivar | Gestión administrativa; acciones de escritura por POST |
| licenciatura/grupo index, create, view, update, delete, reactivar | Catálogos y cambio de estado por POST |
| genero/index, create, view, update | Catálogo de géneros |
| encuesta/index, index-salud, create, view, update; pregunta/index, create, view, update | Administración de salud; view/update muestran el panel con los formularios; POST update conserva el id |
| encuesta/lista-alumno; frontend encuesta/index, guardar-respuesta-ajax, finalizar | Respuestas de salud y comprobación de finalización |
| expediente/index, ver, editar, llenar, crear, constancia | Expedientes institucionales o propios según frontend/backend; crear usa user_id |
| expediente/delete, archivar, restaurar, agregar-nota, clasificar, bloqueo | Archivo, notas y valoración auditada por POST |
| atencion/index, reporte/index, salud/index, salud/resultado, coordinador-panel/index | Atención, alertas, resultados y panel institucional |

Parámetros conservados: id, token, alumno_id, user_id, id_encuesta y page según la acción. Se traducen formularios anidados de Yii y se conservan los controles de rol/CSRF. Una baja por GET se rechaza con 405. CHASIDE y encuesta/index-chaside responden 410; las acciones no incluidas responden 404. No hay redirección silenciosa que convierta escrituras POST en GET. Las exportaciones nuevas usan los formularios `/reportes` y `/expedientes/{id}`.

## Correo y Microsoft

Registro, verificación, recuperación y aviso de alta se probaron con sus correos y fallos. Se verificó además el transporte SMTP real contra un receptor TCP local. En esta vista `MAIL_MAILER=log`: no se entregan correos a los alumnos todavía. Configurar en `.env` el proveedor real, MAIL_HOST/PORT/SCHEME/USERNAME/PASSWORD y remitente autorizado, sin guardar secretos en Git. Falta comprobar TLS/autenticación y la recepción efectiva con dicho proveedor.

Microsoft usa state, nonce, PKCE, firma RS256, emisor/tenant/audiencia, identidad tid/oid y HTTPS validado. Las pruebas cubren registro pendiente, alta posterior, colisión de correo y rechazo de firmas/nonce falsos. No vincula cuentas por coincidencia de correo. El rol de coordinador solamente lo asigna el administrador en la aplicación. `MICROSOFT_ENABLED=false` muestra el botón deshabilitado y rechaza el inicio directo con 503. Falta registrar el callback `http://127.0.0.1:8002/microsoft/callback` en Entra y probar una cuenta real. Las pruebas locales no sustituyen esa validación externa.

Para registrar el retorno local:

1. Abrir https://entra.microsoft.com e ir a **Entra ID → Registros de aplicaciones → Todas las aplicaciones**. Seleccionar la misma aplicación utilizada por Yii.
2. Abrir **Manifiesto**. En el formato Microsoft Graph, localizar `web` y añadir `"http://127.0.0.1:8002/microsoft/callback"` al arreglo `redirectUris`, conservando las direcciones existentes y las demás propiedades.
3. Si el editor muestra el formato antiguo con `replyUrlsWithType`, añadir un objeto `{"url":"http://127.0.0.1:8002/microsoft/callback","type":"Web"}` a ese arreglo en lugar de crear la propiedad Graph.
4. Guardar y revisar **Autenticación**: la dirección debe corresponder a la plataforma **Web**. No activar el flujo implícito ni crear un secreto nuevo para agregar esta dirección.
5. Tras guardar, confirmar para habilitar la integración y realizar la prueba con una cuenta real. Las credenciales existentes deben conservarse fuera de Git.

La dirección `/microsoft/callback` se registra en Entra; no se abre manualmente para iniciar sesión. El recorrido empieza en `/login` o `/acceso-administrativo`, con **Continuar con Microsoft**. Una visita directa al retorno vuelve al login con un aviso, sin consumir una solicitud de inicio pendiente. Solicitudes vencidas, estados que no coinciden o cancelaciones tampoco autentican al usuario y muestran un aviso para reintentar.

Microsoft documenta que una dirección `http://127.0.0.1` debe agregarse desde el manifiesto: https://learn.microsoft.com/en-us/entra/identity-platform/reply-url. El formato actual usa `web.redirectUris`: https://learn.microsoft.com/en-us/entra/identity-platform/reference-microsoft-graph-app-manifest.

## Apache y ejecución

Portal actualmente disponible: http://127.0.0.1:8002/. Acceso administrativo: http://127.0.0.1:8002/acceso-administrativo. Utilizar las cuentas activas importadas de Yii; no se creó ninguna contraseña administrativa predefinida.

Apache se ejecuta como instancia local independiente, con document root en `C:/wamp64/www/Sistema-Modelo-Laravel/public`. Su configuración está en `C:\wamp64\www\Sistema-Modelo\migration\apache-laravel.conf`. No se cambió el servicio Wamp del puerto 80. El acceso a .env está bloqueado, incluso desde la carpeta padre por Wamp.

La instancia actual no se instaló como servicio de inicio automático. Si no está escuchando el puerto 8002, iniciarla desde PowerShell:

```powershell
Start-Process -FilePath 'C:/wamp64/bin/apache/apache2.4.65/bin/httpd.exe' -ArgumentList @('-f','C:/wamp64/www/Sistema-Modelo/migration/apache-laravel.conf') -WindowStyle Hidden
```

Para un servidor definitivo aún se requiere dominio/HTTPS, configuración de producción, proveedor SMTP, validación real de Microsoft y planificación del corte de datos. Yii permanece disponible hasta realizar ese corte.

## Verificación

Ejecutar desde `C:\wamp64\www\Sistema-Modelo-Laravel`, para usar el PHPUnit de Laravel:

```powershell
& 'C:/wamp64/bin/php/php8.5.0/php.exe' artisan test --compact
& 'C:/wamp64/bin/php/php8.5.0/php.exe' artisan route:list
& 'C:/wamp64/bin/php/php8.5.0/php.exe' artisan view:cache
& 'C:/wamp64/bin/php/php8.5.0/php.exe' artisan migrate:status
```

La mayoría de pruebas usa SQLite en memoria. MysqlCompatibilityTest exige el nombre exacto de la copia, comprueba pantallas/escrituras y rollback; verifica que la cuenta MySQL no acceda a la original. SmtpTransportTest recibe un mensaje local sin enviar a una persona. Las pruebas ignoran las cachés de rutas/configuración de la vista local.

Fuentes revisables: `C:\wamp64\www\Sistema-Modelo\migration\laravel-stage\`. El instalador copia los archivos preparados a la instalación Laravel existente y conserva `.env` y datos. No instala dependencias automáticamente: las dependencias están declaradas en composer.json y fijadas en composer.lock. Incorporar al stage las ediciones hechas directamente en Laravel antes de volver a instalarlo.
