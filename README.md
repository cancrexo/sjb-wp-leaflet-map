# SJB WP Leaflet Map

Mapas interactivos con [Leaflet](https://leafletjs.com/) para WordPress.

Versión actual: **0.1.0**

---

## Requisitos

- WordPress ≥ 6.0
- PHP ≥ 8.3

---

## Instalación

1. Copia (o clona) la carpeta del plugin en `wp-content/plugins/sjb-wp-leaflet-map`.
2. En el escritorio de WordPress: **Plugins → Plugins instalados**.
3. Activa **SJB WP Leaflet Map**.

### Desarrollo (stubs IDE)

En la carpeta del plugin, para que Intelephense/IDE reconozca las APIs de WordPress:

```bash
composer install
```

Los stubs van en `vendor/` (ignorado por git). No son necesarios en producción.

---

## Configuración

Ruta: **Ajustes → SJB WP Leaflet Map**

| Pestaña | Estado |
|---------|--------|
| Configuración | Opción de borrar datos al desinstalar |
| Marcadores | Colecciones + marcadores (CRUD vía AJAX, dos tablas) |
| Info | Versión del plugin |

### Borrar datos al desinstalar

- **Desactivado** (por defecto): al desinstalar se conservan las opciones en la base de datos.
- **Activado**: al desinstalar se eliminan las opciones del plugin.

Guarda los cambios con el botón **Guardar cambios** (AJAX, sin recargar la página; feedback con toast).

---

## Uso básico (shortcode)

Inserta el mapa en una página o entrada:

```
[sjb_leaflet_map]
```

### Atributos

| Atributo | Por defecto | Descripción |
|----------|-------------|-------------|
| `lat` | `40.4168` | Latitud del centro |
| `lng` | `-3.7038` | Longitud del centro |
| `zoom` | `13` | Nivel de zoom |
| `width` | `100%` | Ancho del contenedor (número = px) |
| `height` | `400px` | Alto del contenedor (número = px) |
| `id` | `Leaflet Map` | Identificador del contenedor (se sanitiza a clase HTML) |

### Ejemplo

```
[sjb_leaflet_map lat="42.8805" lng="-8.5457" zoom="14" height="450" id="mapa-santiago"]
```

Los assets de Leaflet solo se cargan en páginas donde aparece el shortcode.

---

## WPBakery Page Builder

Si WPBakery está activo, el shortcode aparece en la categoría **SJB Shortcodes** como **SJB Leaflet Map**, con los mismos atributos (lat, lng, zoom, width, height, id).

### Regla importante (mapeo VC)

WPBakery exige que el shortcode **acepte al menos un parámetro** y que ese parámetro esté definido en `vc_map`. Si en el futuro se añade un shortcode sin opciones reales, hay que forzar un campo (p. ej. `el_class` tipo textfield para una clase CSS) aunque el shortcode no lo use. Sin eso, el elemento no se comporta bien en el builder.

---

## Estructura (resumen)

```
sjb-wp-leaflet-map/
├── sjb-wp-leaflet-map.php   # Bootstrap y admin
├── includes/
│   ├── class-shortcodes.php   # Shortcodes, assets públicos, WPBakery
│   ├── class-collections.php  # Tablas y CRUD colecciones/marcadores
│   └── class-ajax.php         # Handlers wp_ajax_* (escritura admin)
├── uninstall.php
├── docs/                    # Planes
├── assets/
│   ├── css/                 # admin.css, public.css
│   ├── js/                  # admin.js, public.js
│   └── vendor/
│       ├── bootstrap/       # UI admin
│       └── leaflet/         # Leaflet 1.9.4
├── templates/admin/         # Pantalla de ajustes
├── languages/
└── composer.json            # require-dev: wordpress-stubs
```

---

## Roadmap / pendiente

Planes detallados en [`docs/`](docs/).

- Colecciones de marcadores (admin, dos tablas): [`docs/20260821-plan-colecciones-marcadores.md`](docs/20260821-plan-colecciones-marcadores.md) — admin CRUD hecho; falta enlazar shortcode ↔ colección
- Ampliar pestaña Info
- (Más ítems según avance el desarrollo)

---

## Licencia

GPL v2 or later

## Autor

SJB Dixital — [sjbdixital.es](https://www.sjbdixital.es)
