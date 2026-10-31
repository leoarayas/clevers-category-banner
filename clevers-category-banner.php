<?php
/**
 * Plugin Name: Clevers Category Banner
 * Description: Banner full-width exclusivo por categoría de WooCommerce sobre el listado de productos. Optimizado en rendimiento, seguridad y responsivo.
 * Version: 1.3.0
 * Author: Clevers
 * Text Domain: clevers-category-banner
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CLEVERS_CATBAN_VERSION', '1.3.0' );
define( 'CLEVERS_CATBAN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CLEVERS_CATBAN_URL', plugin_dir_url( __FILE__ ) );
define( 'CLEVERS_CATBAN_META', '_clevers_category_banner' );
define( 'CLEVERS_CATBAN_NONCE', 'clevers_catban_save' );

/* ============================================================
 * Admin: campo "Banner de categoría"
 * ============================================================ */

add_action( 'product_cat_add_form_fields', 'clevers_catban_add_field' );
function clevers_catban_add_field() {
    wp_nonce_field( CLEVERS_CATBAN_NONCE, 'clevers_catban_nonce' );
    ?>
    <div class="form-field">
        <label><?php esc_html_e( 'Banner de categoría', 'clevers-category-banner' ); ?></label>
        <div class="clevers-catban-preview" style="margin-bottom:10px;"></div>
        <input type="hidden" name="clevers_category_banner" class="clevers-catban-input" value="" />
        <input type="hidden" name="clevers_catban_stay" value="1" />
        <button type="button" class="button clevers-catban-upload"><?php esc_html_e( 'Subir / Elegir imagen', 'clevers-category-banner' ); ?></button>
        <button type="button" class="button clevers-catban-remove" style="display:none;"><?php esc_html_e( 'Quitar', 'clevers-category-banner' ); ?></button>
        <p class="description"><?php esc_html_e( 'Imagen exclusiva para el banner superior del archivo de categoría. Se recomienda 1200×400 px o superior.', 'clevers-category-banner' ); ?></p>
    </div>
    <?php
}

add_action( 'product_cat_edit_form_fields', 'clevers_catban_edit_field' );
function clevers_catban_edit_field( $term ) {
    $banner_id = absint( get_term_meta( $term->term_id, CLEVERS_CATBAN_META, true ) );
    $img       = $banner_id ? wp_get_attachment_image_url( $banner_id, 'thumbnail' ) : '';
    wp_nonce_field( CLEVERS_CATBAN_NONCE, 'clevers_catban_nonce' );
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label><?php esc_html_e( 'Banner de categoría', 'clevers-category-banner' ); ?></label></th>
        <td>
            <div class="clevers-catban-preview" style="margin-bottom:10px;">
                <?php if ( $img ) echo '<img src="' . esc_url( $img ) . '" alt="" style="max-width:300px;height:auto;" />'; ?>
            </div>
            <input type="hidden" name="clevers_category_banner" class="clevers-catban-input" value="<?php echo esc_attr( $banner_id ); ?>" />
            <input type="hidden" name="clevers_catban_stay" value="1" />
            <button type="button" class="button clevers-catban-upload"><?php esc_html_e( 'Subir / Elegir imagen', 'clevers-category-banner' ); ?></button>
            <button type="button" class="button clevers-catban-remove" style="<?php echo $img ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Quitar', 'clevers-category-banner' ); ?></button>
            <p class="description"><?php esc_html_e( 'Imagen exclusiva para el banner superior del archivo de categoría. Se recomienda 1200×400 px o superior.', 'clevers-category-banner' ); ?></p>
        </td>
    </tr>
    <?php
}

add_action( 'created_product_cat', 'clevers_catban_save' );
add_action( 'edited_product_cat', 'clevers_catban_save' );
function clevers_catban_save( $term_id ) {
    if ( ! isset( $_POST['clevers_catban_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['clevers_catban_nonce'] ), CLEVERS_CATBAN_NONCE ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_product_terms' ) ) {
        return;
    }
    $val = isset( $_POST['clevers_category_banner'] ) ? absint( $_POST['clevers_category_banner'] ) : 0;
    $action = 'none';
    if ( $val && get_post_type( $val ) === 'attachment' ) {
        update_term_meta( $term_id, CLEVERS_CATBAN_META, $val );
        $action = 'updated';
    } else {
        delete_term_meta( $term_id, CLEVERS_CATBAN_META );
        $action = 'removed';
    }
    // Invalidar caché de render
    clevers_catban_flush_term_cache( $term_id );

    // Marcar notice en próxima carga
    set_transient( 'clevers_catban_notice_' . $term_id, $action, 60 );
}

/* ============================================================
 * Admin notice tras guardar
 * ============================================================ */

add_action( 'admin_notices', 'clevers_catban_admin_notice' );
function clevers_catban_admin_notice() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->taxonomy !== 'product_cat' ) return;
    if ( ! isset( $_GET['tag_ID'] ) ) return;
    $tid = absint( $_GET['tag_ID'] );
    $action = get_transient( 'clevers_catban_notice_' . $tid );
    if ( ! $action ) return;
    delete_transient( 'clevers_catban_notice_' . $tid );
    $msg = $action === 'updated'
        ? __( 'Banner de categoría actualizado.', 'clevers-category-banner' )
        : ( $action === 'removed' ? __( 'Banner de categoría eliminado.', 'clevers-category-banner' ) : '' );
    if ( $msg ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
    }
}

/* ============================================================
 * Admin: media picker (solo en pantallas relevantes)
 * ============================================================ */

add_action( 'admin_enqueue_scripts', 'clevers_catban_admin_assets' );
function clevers_catban_admin_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || $screen->taxonomy !== 'product_cat' ) return;
    if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) return;

    // Parche runtime: WC 10.8.1 term-ordering.js rompe con FormData (i.data.indexOf).
    // Envolvemos jQuery.ajaxComplete para que data sea string antes de evaluar.
    wp_add_inline_script(
        'jquery-core',
        '(function(){if(typeof jQuery!=="undefined"){var _ac=jQuery.fn.ajaxComplete;jQuery.fn.ajaxComplete=function(fn){return _ac.call(this,function(e,xhr,opts){try{if(opts&&opts.data&&typeof opts.data!=="string"){Object.defineProperty(opts,"data",{get:function(){return"";},configurable:true});}fn.call(this,e,xhr,opts);}catch(err){console.warn("[clevers-catban] ajaxComplete handler skip:",err);}});};}})();'
    );

    wp_enqueue_media();
    wp_enqueue_script(
        'clevers-catban-admin',
        CLEVERS_CATBAN_URL . 'assets/admin.js',
        array( 'jquery', 'wp-util' ),
        CLEVERS_CATBAN_VERSION,
        true
    );
}

/* ============================================================
 * Front: assets condicionales
 * ============================================================ */

add_action( 'wp_enqueue_scripts', 'clevers_catban_assets', 20 );
function clevers_catban_assets() {
    if ( ! is_product_category() ) return;
    $term = get_queried_object();
    if ( empty( $term->term_id ) ) return;
    $banner_id = absint( get_term_meta( $term->term_id, CLEVERS_CATBAN_META, true ) );
    if ( ! $banner_id ) return;

    clevers_catban_enqueue_styles();
}

function clevers_catban_enqueue_styles() {
    if ( ! wp_style_is( 'clevers-catban', 'enqueued' ) ) {
        wp_enqueue_style(
            'clevers-catban',
            CLEVERS_CATBAN_URL . 'assets/banner.css',
            array(),
            CLEVERS_CATBAN_VERSION
        );
    }
}

/* ============================================================
 * Front: registro automático de hooks (Astra o universal WC)
 * ============================================================ */

add_action( 'wp', 'clevers_catban_register_render_hook' );
function clevers_catban_register_render_hook() {
    if ( is_admin() ) return;

    // Detectar Astra para 100% retrocompatibilidad
    $is_astra = defined( 'ASTRA_THEME_VERSION' ) || function_exists( 'astra_content_before' ) || get_template() === 'astra';
    $default_hook = $is_astra ? 'astra_content_before' : 'woocommerce_before_main_content';
    $default_prio = $is_astra ? 1 : 15;

    $hook     = apply_filters( 'clevers_catban_render_hook', $default_hook );
    $priority = (int) apply_filters( 'clevers_catban_render_priority', $default_prio );

    if ( ! empty( $hook ) && is_string( $hook ) ) {
        add_action( $hook, 'clevers_catban_render', $priority );
    }
}

/* ============================================================
 * Front: render automático
 * ============================================================ */

function clevers_catban_render() {
    if ( ! is_product_category() ) return;
    $term = get_queried_object();
    if ( empty( $term->term_id ) ) return;
    $banner_id = absint( get_term_meta( $term->term_id, CLEVERS_CATBAN_META, true ) );
    if ( ! $banner_id ) return;

    echo clevers_catban_get_html( $term, $banner_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Obtiene el HTML del banner (con caché transient).
 */
function clevers_catban_get_html( $term, $banner_id, $args = array() ) {
    $defaults = array(
        'show_title' => 'yes',
        'show_desc'  => 'yes',
        'full_width' => 'yes',
    );
    $args = wp_parse_args( $args, $defaults );

    $is_default_view = ( 'yes' === $args['show_title'] && 'yes' === $args['show_desc'] && 'yes' === $args['full_width'] );
    $cache_key       = $is_default_view 
        ? 'clevers_catban_html_' . $term->term_id 
        : 'clevers_catban_html_' . $term->term_id . '_' . substr( md5( wp_json_encode( $args ) ), 0, 8 );

    $html = get_transient( $cache_key );
    if ( false !== $html ) {
        return $html;
    }

    $html = clevers_catban_build_html( $term, $banner_id, $args );
    set_transient( $cache_key, $html, MONTH_IN_SECONDS );
    return $html;
}

/**
 * Construye el HTML del banner con imagen responsive (srcset + sizes).
 */
function clevers_catban_build_html( $term, $banner_id, $args = array() ) {
    $defaults = array(
        'show_title' => 'yes',
        'show_desc'  => 'yes',
        'full_width' => 'yes',
    );
    $args = wp_parse_args( $args, $defaults );

    $name        = esc_html( $term->name );
    $description = wp_kses_post( term_description( $term ) );

    $img = wp_get_attachment_image(
        $banner_id,
        'full',
        false,
        array(
            'class'         => 'clevers-catban__img',
            'alt'           => esc_attr( $term->name ),
            'loading'       => 'lazy',
            'decoding'      => 'async',
            'fetchpriority' => 'high',
        )
    );
    if ( ! $img ) return '';

    $figure_classes = array( 'clevers-catban' );
    if ( 'no' === $args['full_width'] || 'false' === $args['full_width'] ) {
        $figure_classes[] = 'clevers-catban--contained';
    }

    $show_title = ( 'yes' === $args['show_title'] || 'true' === $args['show_title'] || true === $args['show_title'] );
    $show_desc  = ( 'yes' === $args['show_desc'] || 'true' === $args['show_desc'] || true === $args['show_desc'] );

    ob_start();
    ?>
    <figure class="<?php echo esc_attr( implode( ' ', $figure_classes ) ); ?>" role="img" aria-label="<?php echo esc_attr( $term->name ); ?>">
        <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — wp_get_attachment_image ya escapa ?>
        <?php if ( $show_title || ( $show_desc && $description ) ) : ?>
            <figcaption class="clevers-catban__overlay">
                <?php if ( $show_title ) : ?>
                    <h1 class="clevers-catban__title"><?php echo $name; ?></h1>
                <?php endif; ?>
                <?php if ( $show_desc && $description ) : ?>
                    <div class="clevers-catban__desc"><?php echo $description; // phpcs:ignore — ya saneado con wp_kses_post ?></div>
                <?php endif; ?>
            </figcaption>
        <?php endif; ?>
    </figure>
    <?php
    return ob_get_clean();
}

/* ============================================================
 * Shortcode: [clevers_category_banner]
 * ============================================================ */

add_shortcode( 'clevers_category_banner', 'clevers_catban_shortcode' );
function clevers_catban_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'         => 0,
        'slug'       => '',
        'show_title' => 'yes',
        'show_desc'  => 'yes',
        'full_width' => 'yes',
    ), $atts, 'clevers_category_banner' );

    $term = null;
    if ( ! empty( $atts['id'] ) ) {
        $term = get_term( absint( $atts['id'] ), 'product_cat' );
    } elseif ( ! empty( $atts['slug'] ) ) {
        $term = get_term_by( 'slug', sanitize_title( $atts['slug'] ), 'product_cat' );
    } elseif ( is_product_category() ) {
        $term = get_queried_object();
    }

    if ( ! $term || is_wp_error( $term ) || empty( $term->term_id ) ) {
        return '';
    }

    $banner_id = absint( get_term_meta( $term->term_id, CLEVERS_CATBAN_META, true ) );
    if ( ! $banner_id ) {
        return '';
    }

    clevers_catban_enqueue_styles();

    return clevers_catban_get_html( $term, $banner_id, $atts );
}

/* ============================================================
 * Flush de caché al editar el attachment o el término
 * ============================================================ */

function clevers_catban_flush_term_cache( $term_id ) {
    delete_transient( 'clevers_catban_html_' . $term_id );
}

add_action( 'edited_term', 'clevers_catban_flush_on_term_edit', 10, 2 );
function clevers_catban_flush_on_term_edit( $term_id, $tt_id ) {
    if ( ! function_exists( 'get_term' ) ) return;
    $term = get_term( $term_id );
    if ( $term && $term->taxonomy === 'product_cat' ) {
        clevers_catban_flush_term_cache( $term_id );
    }
}

add_action( 'wp_update_attachment_metadata', 'clevers_catban_flush_all', 10, 1 );
function clevers_catban_flush_all( $post_id ) {
    $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'meta_key' => CLEVERS_CATBAN_META, 'meta_value' => (string) $post_id, 'fields' => 'ids' ) );
    if ( is_array( $terms ) ) {
        foreach ( $terms as $tid ) {
            clevers_catban_flush_term_cache( $tid );
        }
    }
}
