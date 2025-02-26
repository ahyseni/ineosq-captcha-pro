(function($) {
	"use strict";
	$( document ).ready(
		function() {
				var product = $( '.ineosq_product_box' ),
				max         = 0;
				$( product ).each(
					function () {
						if ( $( this ).height() > max ) {
							max = $( this ).height();
						}
					}
				);
				$( '.ineosq_product_box' ).css( 'height', max + 'px' );

			if ( $( '.ineosq-filter' ).length ) {
				var prvPos = $( '.ineosq-filter' ).offset().top;
				var maxPos = prvPos + $( '.ineosq-products' ).outerHeight() - $( '.ineosq-filter' ).outerHeight();

				$( window ).scroll(
					function() {
						if ( $( window ).width() > 580 ) {
							   var scrPos = Number( $( document ).scrollTop() ) + 40;
							if ( scrPos > maxPos ) {
								$( '.ineosq-filter' ).removeClass( 'ineosq_fixed' );
							} else if ( scrPos > prvPos ) {
								$( '.ineosq-filter' ).addClass( 'ineosq_fixed' );
							} else {
								$( '.ineosq-filter' ).removeClass( 'ineosq_fixed' );
							}
						}
					}
				);
			}
				$( '.ineosq-menu-item-icon' ).click(
					function() {
						if ( $( this ).hasClass( 'ineosq-active' ) ) {
							$( this ).removeClass( 'ineosq-active' );
							$( '.ineosq-nav-tab-wrapper, .ineosq-help-links-wrapper' ).hide();
						} else {
							$( this ).addClass( 'ineosq-active' );
							$( '.ineosq-nav-tab-wrapper, .ineosq-help-links-wrapper' ).css( 'display', 'inline-block' );
						}
					}
				);
				$( '.ineosq-filter-top h2' ).click(
					function() {
						if ( $( '.ineosq-filter-top' ).hasClass( 'ineosq-opened' ) ) {
							$( '.ineosq-filter-top' ).removeClass( 'ineosq-opened' );
						} else {
							$( '.ineosq-filter-top' ).addClass( 'ineosq-opened' );
						}
					}
				);

		}
	);
})( jQuery );
