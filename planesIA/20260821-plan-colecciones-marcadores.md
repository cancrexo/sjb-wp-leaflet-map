# Plan: Colecciones de marcadores (admin)

**Fecha:** 2026-08-21  
**Alcance:** Plugin `sjb-wp-leaflet-map` — pestaña admin **Marcadores**  
**Objetivo:** Gestionar **colecciones** (grupos) de marcadores reutilizables, con persistencia en base de datos.

---

## Contexto

- El shortcode `[sjb_leaflet_map]` ya permite un **marcador suelto** (`marker_lat`, `marker_lng`, `marker_text`) y mapeo en WPBakery (pestaña Marcador).
- En admin existe la pestaña **Marcadores** (pendiente de implementar).
- Este plan cubre el **CRUD admin** de colecciones y marcadores. Enlazar shortcode/mapa ↔ colección queda para un paso posterior.

---

## Decisión de diseño

Persistencia en **dos tablas** propias del plugin (prefijo `$wpdb->prefix` + slug del plugin):

1. **Colecciones** — grupo con nombre, ID/slug y descripción.
2. **Marcadores** — filas ligadas a una colección (FK): coordenadas, texto HTML básico y modo de visualización (hover / clic / ambos).

### HTML permitido en el texto del marcador

Etiquetas básicas: `strong` / `b`, `u`, `br`, `a` (y atributos seguros de enlace). Sanitizar con `wp_kses` (lista blanca).

### Visualización del texto

Por marcador, indicar si el texto sale en:

- **Hover** → tooltip Leaflet
- **Clic** → popup Leaflet
- **Ambos**

---

## Esquema propuesto (borrador)

### Tabla colecciones

| Campo | Notas |
|-------|--------|
| `id` | PK |
| `name` | Nombre visible |
| `slug` | ID usable (único) |
| `description` | Texto opcional |
| `created_at` / `updated_at` | Timestamps |

### Tabla marcadores

| Campo | Notas |
|-------|--------|
| `id` | PK |
| `collection_id` | FK → colecciones |
| `lat` / `lng` | Coordenadas |
| `text` | HTML permitido (kses) |
| `show_on_hover` | Tinyint / bool |
| `show_on_click` | Tinyint / bool |
| `sort_order` | Orden opcional |
| `created_at` / `updated_at` | Timestamps |

Nombres definitivos de tabla: p. ej. `{prefix}sjb_wp_leaflet_map_collections` y `{prefix}sjb_wp_leaflet_map_markers` (ajustar al estilo del plugin al implementar).

---

## Alcance de implementación (admin)

1. Crear tablas en **activación** (`dbDelta`). Sin upgrade de esquema por ahora (reinstalar en desarrollo).
2. UI en pestaña **Marcadores**: listar / crear / editar / borrar colecciones.
3. Dentro de una colección: listar / añadir / editar / borrar marcadores (lat, lng, texto, flags hover/clic).
4. En **desinstalación**, si «borrar datos al desinstalar» está activo: drop de tablas + borrar opciones.

### Fuera de este plan (posterior)

- Atributo de shortcode / WPBakery para cargar una colección por slug/ID.
- Sustituir o complementar el marcador suelto actual.

---

## Notas

- Principio KISS: no anticipar multiidioma ni iconos custom de marcador en esta fase.
- Reutilizar Bootstrap admin ya presente en el plugin para la UI.
- **Escritura en BD vía AJAX** (`includes/class-ajax.php` + `assets/js/admin.js`): settings, CRUD colecciones y marcadores. Sin POST clásico ni recarga por redirect; feedback con toast. La navegación por pestañas sigue siendo GET (`tab=marcadores`, `collection_id`, etc.).
