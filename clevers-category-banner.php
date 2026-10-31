<?php
/**
 * Plugin Name: Clevers Category Banner
 * Description: Banner full-width exclusivo por categoría de WooCommerce sobre el listado de productos. Optimizado en rendimiento, seguridad y responsivo.
 * Version: 1.2.0
 * Author: Clevers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CLEVERS_CATBAN_VERSION', '1.2.0' );
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
        <label><?php esc_html_e( 'Banner de categoría', 'clevers' ); ?></label>
        <div class="clevers-catban-preview" style="margin-bottom:10px;"></div>
        <input type="hidden" name="clevers_category_banner" class="clevers-catban-input" value="" />
        <input type="hidden" name="clevers_catban_stay" value="1" />
        <button type="button" class="button clevers-catban-upload"><?php esc_html_e( 'Subir / Elegir imagen', 'clevers' ); ?></button>
        <button type="button" class="button clevers-catban-remove" style="display:none;"><?php esc_html_e( 'Quitar', 'clevers' ); ?></button>
        <p class="description"><?php esc_html_e( 'Imagen exclusiva para el banner superior del archivo de categoría. Se recomienda 1200×400 px o superior.', 'clevers' ); ?></p>
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
        <th scope="row" valign="top"><label><?php esc_html_e( 'Banner de categoría', 'clevers' ); ?></label></th>
        <td>
            <div class="clevers-catban-preview" style="margin-bottom:10px;">
                <?php if ( $img ) echo '<img src="' . esc_url( $img ) . '" alt="" style="max-width:300px;height:auto;" />'; ?>
            </div>
            <input type="hidden" name="clevers_category_banner" class="clevers-catban-input" value="<?php echo esc_attr( $banner_id ); ?>" />
            <input type="hidden" name="clevers_catban_stay" value="1" />
            <button type="button" class="button clevers-catban-upload"><?php esc_html_e( 'Subir / Elegir imagen', 'clevers' ); ?></button>
            <button type="button" class="button clevers-catban-remove" style="<?php echo $img ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Quitar', 'clevers' ); ?></button>
            <p class="description"><?php esc_html_e( 'Imagen exclusiva para el banner superior del archivo de categoría. Se recomienda 1200×400 px o superior.', 'clevers' ); ?></p>
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
    delete_transient( 'clevers_catban_html_' . $term_id );

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
        ? __( 'Banner de categoría actualizado.', 'clevers' )
        : ( $action === 'removed' ? __( 'Banner de categoría eliminado.', 'clevers' ) : '' );
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

    wp_enqueue_style(
        'clevers-catban',
        CLEVERS_CATBAN_URL . 'assets/banner.css',
        array(),
        CLEVERS_CATBAN_VERSION
    );
}

/* ============================================================
 * Front: render (caché transient)
 * ============================================================ */

add_action( 'astra_content_before', 'clevers_catban_render', 1 );
function clevers_catban_render() {
    if ( ! is_product_category() ) return;
    $term = get_queried_object();
    if ( empty( $term->term_id ) ) return;
    $banner_id = absint( get_term_meta( $term->term_id, CLEVERS_CATBAN_META, true ) );
    if ( ! $banner_id ) return;

    $cache_key = 'clevers_catban_html_' . $term->term_id;
    $html      = get_transient( $cache_key );
    if ( false !== $html ) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — cached HTML
        return;
    }

    $html = clevers_catban_build_html( $term, $banner_id );
    set_transient( $cache_key, $html, MONTH_IN_SECONDS );
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Construye el HTML del banner con imagen responsive (srcset + sizes).
 */
function clevers_catban_build_html( $term, $banner_id ) {
    $name        = esc_html( $term->name );
    $description = wp_kses_post( term_description( $term ) );

    $img = wp_get_attachment_image(
        $banner_id,
        'full',
        false,
        array(
            'class'      => 'clevers-catban__img',
            'alt'        => esc_attr( $term->name ),
            'loading'    => 'lazy',
            'decoding'   => 'async',
            'fetchpriority' => 'high',
        )
    );
    if ( ! $img ) return '';

    ob_start();
    ?>
    <figure class="clevers-catban" role="img" aria-label="<?php echo esc_attr( $term->name ); ?>">
        <?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — wp_get_attachment_image ya escapa ?>
        <figcaption class="clevers-catban__overlay">
            <h1 class="clevers-catban__title"><?php echo $name; ?></h1>
            <?php if ( $description ) : ?>
                <div class="clevers-catban__desc"><?php echo $description; // phpcs:ignore — ya saneado ?></div>
            <?php endif; ?>
        </figcaption>
    </figure>
    <?php
    return ob_get_clean();
}

/* ============================================================
 * Flush de caché al editar el attachment o el término
 * ============================================================ */

add_action( 'edited_term', 'clevers_catban_flush_on_term_edit', 10, 2 );
function clevers_catban_flush_on_term_edit( $term_id, $tt_id ) {
    if ( ! function_exists( 'get_term' ) ) return;
    $term = get_term( $term_id );
    if ( $term && $term->taxonomy === 'product_cat' ) {
        delete_transient( 'clevers_catban_html_' . $term_id );
    }
}

add_action( 'wp_update_attachment_metadata', 'clevers_catban_flush_all', 10, 1 );
function clevers_catban_flush_all( $post_id ) {
    $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'meta_key' => CLEVERS_CATBAN_META, 'meta_value' => (string) $post_id, 'fields' => 'ids' ) );
    if ( is_array( $terms ) ) {
        foreach ( $terms as $tid ) {
            delete_transient( 'clevers_catban_html_' . $tid );
        }
    }
}