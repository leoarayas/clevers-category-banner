# Clevers Category Banner for WooCommerce

Banner full-width exclusivo por categoría de WooCommerce sobre el listado de productos. Optimizado en rendimiento, seguridad y responsivo.

## Características

- **Selector nativo en administración:** Sube o selecciona la imagen del banner directamente desde la pantalla de creación o edición de categorías de WooCommerce (`product_cat`).
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

## Licencia

GPLv2 o posterior.
