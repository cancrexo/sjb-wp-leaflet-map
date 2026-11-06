# SJB WP Leaflet Map

Mapas interactivos con [Leaflet](https://leafletjs.com/) para WordPress.

Versión actual: **1.0.1**

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

| Pestaña | Qué hay |
|---------|--------|
| Antes de empezar | Los dos shortcodes y cuándo usar cada uno. Abierta por defecto |
| Configuración | Icono de marcador por defecto y borrar datos al desinstalar |
| Marcadores | Colecciones y marcadores. Importar JSON, GeoJSON y KML. Exportar JSON, GeoJSON, KML y KMZ |
| Info | Versión del plugin, servidor, PHP, BD, memoria, WP y WooCommerce |

### Borrar datos al desinstalar

- **Desactivado** (por defecto): al desinstalar se conservan las opciones y las tablas.
- **Activado**: al desinstalar se eliminan las opciones y las tablas de colecciones y marcadores.

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
| `lat` | `42.4034506` | Latitud del centro |
| `lng` | `-8.8091448` | Longitud del centro |
| `zoom` | `13` | Nivel de zoom (0–19, límites Leaflet/OSM) |
| `zoom_needs_ctrlkey` | *(apagado)* | `1` para que la rueda solo haga zoom con Ctrl (o Comando). Sin la tecla, la página se desplaza y el mapa avisa. |
| `width` | `100%` | Ancho del contenedor (número = px) |
| `height` | `400px` | Alto del contenedor (número = px) |
| `id` | `Leaflet Map` | Identificador del contenedor (se sanitiza a clase HTML) |
| `marker_lat` | *(vacío)* | Latitud del pin. Vacío = sin marcador. No usa las colecciones |
| `marker_lng` | *(vacío)* | Longitud del pin |
| `marker_text` | *(vacío)* | Texto del pin: tooltip al pasar el ratón y popup al hacer clic |

### Ejemplo

```
[sjb_leaflet_map lat="42.8805" lng="-8.5457" zoom="14" height="450" id="mapa-santiago" zoom_needs_ctrlkey="1"]
```

Los assets de Leaflet solo se cargan en páginas donde aparece el shortcode.

---

## Colección de marcadores

Mapa centrado en lat/lng/zoom con los marcadores **activos** de una colección (por slug o ID):

```
[sjb_leaflet_collection collection="mi-coleccion" lat="42.8805" lng="-8.5457" zoom="14"]
```

### Atributos

| Atributo | Por defecto | Descripción |
|----------|-------------|-------------|
| `collection` | *(vacío)* | Slug o ID de la colección |
| `lat` | `42.4034506` | Latitud del centro |
| `lng` | `-8.8091448` | Longitud del centro |
| `zoom` | `13` | Nivel de zoom (0–19, límites Leaflet/OSM) |
| `zoom_needs_ctrlkey` | *(apagado)* | `1` para que la rueda solo haga zoom con Ctrl (o Comando). Sin la tecla, la página se desplaza y el mapa avisa. |
| `width` | `100%` | Ancho del contenedor |
| `height` | `400px` | Alto del contenedor |
| `id` | `Leaflet Collection` | Identificador del contenedor |

Cada marcador respeta su modo de texto (`hover`, `click`, `both`, `always`). Los inactivos no se muestran.

---

## WPBakery Page Builder

Si WPBakery está activo, en **SJB Shortcodes** aparecen:

- **SJB Leaflet Map** — mapa simple (lat, lng, zoom, rueda con Ctrl, width, height, id, marcador opcional).
- **SJB Leaflet Collection** — mapa con desplegable de colecciones, el mismo centro y tamaño, y la misma opción de rueda con Ctrl.

### Regla importante (mapeo VC)

WPBakery exige que el shortcode **acepte al menos un parámetro** y que ese parámetro esté definido en `vc_map`. Si en el futuro se añade un shortcode sin opciones reales, hay que forzar un campo (p. ej. `el_class` tipo textfield para una clase CSS) aunque el shortcode no lo use. Sin eso, el elemento no se comporta bien en el builder.

---

## Editor de bloques

En **SJB Shortcodes** están **SJB Leaflet Map** y **SJB Leaflet Collection**, con los mismos campos que los shortcodes. En el editor se ve una ficha (centro, zoom, tamaño y, si aplica, marcador o colección). El mapa se pinta al ver la página.

---

## Estructura (resumen)

```
sjb-wp-leaflet-map/
├── sjb-wp-leaflet-map.php   # Bootstrap y admin
├── includes/
│   ├── class-shortcodes.php   # Shortcodes, bloques, assets públicos, WPBakery
│   ├── class-collections.php  # Tablas y CRUD colecciones/marcadores
│   ├── class-exchange.php     # Importar y exportar colecciones
│   └── class-ajax.php         # Handlers wp_ajax_* (escritura admin)
├── blocks/                  # block.json de mapa y colección
├── uninstall.php
├── planesIA/                # Planes de desarrollo
├── assets/
│   ├── css/                 # admin.css, public.css
│   ├── js/                  # admin.js, public.js, blocks.js
│   └── vendor/
│       ├── bootstrap/       # UI admin
│       └── leaflet/         # Leaflet 1.9.4
├── templates/admin/         # Pantalla de ajustes
├── languages/
└── composer.json            # require-dev: wordpress-stubs
```

---

## Planes

El plan de colecciones está en [`planesIA/20260821-plan-colecciones-marcadores.md`](planesIA/20260821-plan-colecciones-marcadores.md). El admin, el shortcode `[sjb_leaflet_collection]` y los bloques ya están hechos. El documento conserva el diseño original.

---

## Licencia

GPL v2 or later

## Autor

SJB Dixital — [sjbdixital.es](https://www.sjbdixital.es)
