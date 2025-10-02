(function ( $ ) {
    'use strict';

    var transparentKeyword = 'transparent';

    function initColorPicker( context ) {
        $( '.jlg-color-picker', context ).each( function () {
            var $input = $( this );
            var defaultColor = $input.data( 'default-color' );
            var allowTransparent = $input.data( 'allow-transparent' ) === true || $input.data( 'allow-transparent' ) === 'true';
            var pickerOptions = {
                change: function ( event, ui ) {
                    if ( ui && ui.color ) {
                        $input.val( ui.color.toString() );
                    }

                    $input.trigger( 'change' );
                },
                clear: function () {
                    if ( allowTransparent ) {
                        $input.val( transparentKeyword );
                    } else {
                        $input.val( '' );
                    }

                    $input.trigger( 'change' );
                }
            };

            if ( typeof defaultColor !== 'undefined' && defaultColor !== '' && defaultColor.toString().toLowerCase() !== transparentKeyword ) {
                pickerOptions.defaultColor = defaultColor;
            }

            $input.wpColorPicker( pickerOptions );

            if ( allowTransparent && ( typeof $input.val() !== 'string' || $input.val().trim() === '' ) ) {
                $input.val( transparentKeyword );
            }
        } );
    }

    $( function () {
        initColorPicker( document );
    } );
})( jQuery );
