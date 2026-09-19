# Prototipo SNWO — Notas

## Qué incluye
Las 8 pantallas de tu documentación, navegables entre sí, con datos de ejemplo (no requieren base de datos para verse funcionando):

1. `login.php` — inicio de sesión (ya lo tenías)
2. `dashboard.php` — estadísticas + accesos rápidos + últimas dietas
3. `pacientes.php` — gestión de pacientes (con buscador funcional)
4. `generador.php` — menú semanal + totales del día
5. `personalizar.php` — búsqueda y reemplazo de alimentos (funcional con Alpine)
6. `exportar.php` — vista previa de PDF (el botón simula la generación)
7. `progreso.php` — gráfica de evolución de peso (Chart.js)
8. `alimentos.php` — catálogo de alimentos (con buscador funcional)
9. `usuarios.php` — gestión de usuarios y roles

Arriba de cada pantalla hay una barra de pestañas (Dashboard / Pacientes / Generador / etc.) — **esa barra es solo para que puedas navegar el prototipo en la demo**, no forma parte del diseño final de tus mockups. Si no la quieres en la versión final, la puedes quitar de `includes/header.php`.

## Cómo instalarlo
1. Copia toda la carpeta `snwo_prototipo` a `C:\xampp\htdocs\` (puedes renombrarla, ej. `pg2`).
2. Abre `http://localhost/pg2/login.php` — al iniciar sesión correctamente te lleva al `dashboard.php` nuevo (ya no al placeholder anterior).
3. Desde ahí puedes navegar todas las pantallas.

## Qué es "de verdad" y qué es simulado
- **Funcional de verdad:** los buscadores de pacientes/alimentos filtran en tiempo real (Alpine.js), la personalización de alimentos actualiza la vista al seleccionar una alternativa, el login valida contra tu base de datos.
- **Simulado (datos fijos, aún no conectado a SQL Server):** los números del dashboard, la tabla de pacientes, el menú semanal, la gráfica de progreso, el catálogo de alimentos y la tabla de usuarios. Todo viene de arreglos de PHP (`$pacientes = [...]`, etc.) al inicio de cada archivo — ahí es donde luego conectas tus consultas reales con PDO.
- El botón "Generar y descargar PDF" en `exportar.php` es una simulación visual (con `setTimeout` en Alpine); ahí luego conectas tu librería real (TCPDF, como dice el mockup).

## Siguiente paso lógico
Cuando quieras, conectamos una pantalla a la vez a tu base de datos real (empezando por `pacientes.php`, que es la más sencilla), reemplazando el arreglo `$pacientes = [...]` por una consulta PDO real usando `config/conexion.php`. Dime con cuál quieres seguir.
