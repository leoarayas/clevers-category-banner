jQuery(function($){
    'use strict';

    var attempts = 0;
    var maxAttempts = 20; // 2 segundos

    function tryOpen( $wrap ) {
        attempts = 0;
        poll();
        function poll() {
            attempts++;
            try {
                if ( typeof wp !== 'undefined' && wp.media && typeof wp.media === 'function' ) {
                    openMediaPicker( $wrap );
                    return;
                }
            } catch (e) {
                console.error('[clevers-catban] Error al abrir:', e);
            }
            if (attempts < maxAttempts) {
                setTimeout(poll, 100);
            } else {
                alert('El selector de medios no se cargó. Recarga la página o desactiva extensiones que bloqueen wp.media.');
            }
        }
    }

    function openMediaPicker( $wrap ) {
        try {
            var frame = wp.media({
                title:    'Elegir imagen de banner',
                button:   { text: 'Usar como banner' },
                library:  { type: 'image' },
                multiple: false
            });

            frame.on('select', function(){
                try {
                    var att = frame.state().get('selection').first().toJSON();
                    var previewUrl = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
                    $wrap.find('.clevers-catban-input').val(att.id);
                    $wrap.find('.clevers-catban-preview').html('<img src="'+previewUrl+'" alt="" style="max-width:300px;height:auto;" />');
                    $wrap.find('.clevers-catban-remove').show();
                } catch(e) {
                    console.error('[clevers-catban] Error al procesar selección:', e);
                }
            });

            frame.on('open', function(){
                // Forzar el view de galería (no el de uploads)
                try {
                    var content = frame.state().get('content');
                    if (content && content.set && content.options && content.options.library && content.options.library.type) {
                        // ya está
                    } else if (content && content.set) {
                        content.set('library', wp.media.query({ type: 'image' }));
                    }
                } catch(e){}
            });

            frame.open();
        } catch (e) {
            console.error('[clevers-catban] No se pudo abrir el picker:', e);
            alert('Error al abrir el selector: '+e.message);
        }
    }

    $(document).on('click', '.clevers-catban-upload', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('td, .form-field');
        if ( ! $wrap.length ) return;
        tryOpen( $wrap );
    });

    $(document).on('click', '.clevers-catban-remove', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('td, .form-field');
        $wrap.find('.clevers-catban-input').val('');
        $wrap.find('.clevers-catban-preview').html('');
        $(this).hide();
    });
});
