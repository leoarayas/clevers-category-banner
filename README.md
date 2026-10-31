# Clevers Category Banner for WooCommerce

Banner full-width exclusivo por categoría de WooCommerce sobre el listado de productos. Optimizado en rendimiento, seguridad y responsivo.

## Características

- **Selector nativo en administración:** Sube o selecciona la imagen del banner directamente desde la pantalla de creación o edición de categorías de WooCommerce (`product_cat`).
- **Renderizado Universal o Astra:** Enganche automático a `astra_content_before` (si Astra está activo) o a `woocommerce_before_main_content` en cualquier otro tema.
- **Shortcode Flexible:** Incrusta el banner en cualquier maquetador visual o plantilla mediante `[clevers_category_banner]`.
- **Renderizado Responsivo:** Emplea `wp_get_attachment_image` con soporte completo para `srcset`, `sizes`, `decoding="async"` y `loading="lazy"`.
- **Caché por Transitorio:** El HTML generado se almacena en transients de WordPress y se invalida automáticamente al editar el término o la imagen adjunta.
- **Overlay informativo:** Muestra título de categoría y descripción estilizada sobre el banner.

## Requisitos

- WordPress 6.0 o superior
- WooCommerce 8.0 o superior
- PHP 7.4 o superior

## Instalación

1. Clona o copia la carpeta `clevers-category-banner` en `wp-content/plugins/`.
2. Activa el plugin desde la pantalla de **Plugins** en WordPress.
3. Dirígete a **Productos > Categorías**, selecciona una categoría y asigna una imagen en **Banner de categoría**.

## Uso por Shortcode

Puedes usar el shortcode en constructores visuales (Elementor, Divi, Brizy) o en el editor de bloques:

```text
[clevers_category_banner]
```

### Parámetros opcionales:

- `id`: ID numérico del término de categoría (opcional, si se omite usa la categoría actual).
- `slug`: Slug de la categoría (opcional).
- `show_title`: `yes` o `no` (por defecto `yes`).
- `show_desc`: `yes` o `no` (por defecto `yes`).
- `full_width`: `yes` o `no` (por defecto `yes`, si es `no` se ajusta al ancho del contenedor contenedor con la clase `.clevers-catban--contained`).

**Ejemplo:**
```text
[clevers_category_banner slug="accesorios" show_desc="no" full_width="no"]
```

## Filtros para Desarrolladores

- `clevers_catban_render_hook`: Modifica el hook de renderizado automático (o devuelve `false` para desactivar el renderizado automático).
- `clevers_catban_render_priority`: Modifica la prioridad del hook de renderizado.

```php
// Cambiar hook de renderizado
add_filter( 'clevers_catban_render_hook', function() {
    return 'woocommerce_archive_description';
} );
```

## Licencia

GPLv2 o posterior.
