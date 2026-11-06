# Changelog

Los cambios notables de SJB WP Leaflet Map. La versión del plugin es la de la cabecera (`1.0.1`).

## 1.0.1 — 2026-11-06

- Atributo `zoom_needs_ctrlkey` en los dos shortcodes: la rueda solo hace zoom con Ctrl (o Comando). El mismo interruptor está en el bloque y en WPBakery. Sin la tecla, la página sigue desplazándose y el mapa muestra un aviso.
- Bloques del editor de bloques, en **SJB Shortcodes**: **SJB Leaflet Map** y **SJB Leaflet Collection**, con los mismos campos que los shortcodes.
- En el editor se muestra una ficha (centro, zoom, tamaño y, si aplica, marcador o colección). El mapa Leaflet se pinta al ver la página.
- La ayuda de Ajustes menciona estos bloques junto a WPBakery.

## 1.0.0 — 2026-10-26

- Pestaña **Antes de empezar**, abierta por defecto, con los dos shortcodes y qué hace cada uno. **Marcadores** indica que las colecciones se usan con `[sjb_leaflet_collection]`.
- Icono propio en WPBakery para el mapa sencillo y otro para la colección.
- Aviso en el admin si no se pueden crear las tablas. El esquema se crea al activar el plugin.
- El autor queda como nota al pie, alineada a la derecha, en la pantalla de Ajustes.
- Importar colecciones en JSON, GeoJSON y KML, y exportarlas también en KMZ, con miniatura del pin.
- Icono por marcador: el de la colección o uno propio. La pestaña **Info** muestra el entorno (versión, servidor, PHP, base de datos, memoria, WordPress y WooCommerce).
- Icono del mapa: el de Leaflet o una imagen de la mediateca, por colección.
- Shortcode `[sjb_leaflet_collection]`: centro del mapa y marcadores activos de una colección (slug o ID). El mismo elemento está en WPBakery.
- Colecciones y marcadores en el admin: alta, edición y borrado por AJAX, en dos tablas.
- Marcador opcional en `[sjb_leaflet_map]` (`marker_lat`, `marker_lng`, `marker_text`): tooltip al pasar el ratón y popup al hacer clic.
- Shortcode `[sjb_leaflet_map]`, Leaflet 1.9.4 y teselas de OpenStreetMap. Los assets solo se cargan si la página usa el shortcode. El elemento equivalente está en WPBakery.
- Pantalla **Ajustes → SJB WP Leaflet Map** (Configuración, Marcadores e Info), guardado por AJAX y opción de borrar los datos al desinstalar (apagada por defecto).
