jQuery( document ).ready( function ( $ ) { //eslint-disable-line no-undef
	$( '.submission-status-change-button' ).on( 'click', function ( e ) {
		let button = $( this );
		let post_id = button.prop( 'value' );
		let new_post_status = button.prop( 'name' );

		button.prop( 'disabled', true );
		let button_text = button.html();
		let button_width = button.outerWidth();
		button.css( 'width', button_width );
		button.text( '...' );

		$.ajax( {
			type: 'POST',
			url: submission_cpt_scripts_ajax_object.ajax_url, //eslint-disable-line no-undef
			data: {
			   action: 'update_submission_cpt_status',
			   _status_change_nonce: submission_cpt_scripts_ajax_object.security, //eslint-disable-line no-undef
			   post_id: post_id,
			   new_post_status: new_post_status,
			},
			/**
			 * Parses the response from the server and displays the success or error message.
			 *
			 * @param {string} result - The response from the rest api.
			 * @returns {void}
			 */
			success: function ( result ) {
				button.text( button_text );
				button.prop( 'disabled', false );
				if ( result.success ) {
					button.siblings().removeClass( 'button-primary' );
					button.addClass( 'button-primary' );
				}
			},
		} );
	} );
} );
