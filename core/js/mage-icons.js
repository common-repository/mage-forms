/**
 * Dashicons Picker
 *
 * Based on: https://github.com/bradvin/dashicons-picker/
 */

( function ( $ ) {

	/**
	 *
	 * @returns {void}
	 */
	$.fn.dashiconsPicker = function () {

		/**
		 * Dashicons, in CSS order
		 *
		 * @type Array
		 */
		return this.each( function () {

			var button = $( this );

			button.on( 'click.dashiconsPicker', function () {
				createPopup( button );
			} );

			function createPopup( button ) {

				var target = $( button.data( 'target' ) ),
					popup  = $( '<div class="dashicon-picker-container"> \
						<div class="dashicon-picker-control" /> \
						<ul class="dashicon-picker-list" /> \
					</div>' )
						.css( {
							'top':  button.offset().top,
							'left': button.offset().left
						} ),
					list = popup.find( '.dashicon-picker-list' );

				for ( var i in icons ) {
					list.append( '<li data-icon="' + icons[i] + '"><a href="#" title="' + icons[i] + '"><img src="'+mageURL +'source/img/'+ icons[i] + '.png" width="16" height="16" /></a></li>' );
				};

				$( 'a', list ).click( function ( e ) {
					e.preventDefault();
					var src = $( this ).find('img').attr( 'src' );
					target.val( src );	
					sample = target.attr( 'id' );				
					$('#mage-'+sample+' img').attr('src',src);  
					removePopup();
				} );
				popup.appendTo( 'body' ).show();
				$( document ).bind( 'mouseup.dashicons-picker', function ( e ) {
					if ( ! popup.is( e.target ) && popup.has( e.target ).length === 0 ) {
						removePopup();
					}
				} );
			}

			function removePopup() {
				$( '.dashicon-picker-container' ).remove();
				$( document ).unbind( '.dashicons-picker' );
			}
		} );
	};
}( jQuery ) );
