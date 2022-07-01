jQuery( document ).ready( function ( $ ) { //eslint-disable-line no-undef
	$( '#submission-form' ).on( 'submit', function ( e ) {

		e.preventDefault();

		let form = $( this );
		let form_data = new FormData();

		// regular fields that need to be processed
		form_data.append( 'wiki_username', form.prop( 'wiki_username' ).value );
		form_data.append( 'legal_name', form.prop( 'legal_name' ).value );
		form_data.append( 'date_birth', form.prop( 'date_birth' ).value );
		form_data.append( 'participant_email', form.prop( 'participant_email' ).value );
		form_data.append( 'phone_number', form.prop( 'phone_number' ).value );
		form_data.append( 'authors_contributed', form.prop( 'authors_contributed' ).value );
		form_data.append( 'explanation_creation', form.prop( 'explanation_creation' ).value );
		form_data.append( 'explanation_inspiration', form.prop( 'explanation_inspiration' ).value );

		// nonce
		let submission_nonce = form.prop( '_submissionnonce' ).value;
		form_data.append( '_submissionnonce', submission_nonce );

		let files_upload_nonce = submission_form_ajax_object.files_upload_nonce; //eslint-disable-line no-undef
		form_data.append( '_filesuploadnonce', files_upload_nonce );

		// audio file - unprocessed field
		let file_data = form.prop( 'audio_file' )['files'][0];
		form_data.append( 'audio_file', file_data );

		let url = submission_form_ajax_object.api_url; //eslint-disable-line no-undef
		let method = form.attr( 'method' );

		$.ajax( {
			url: url,
			type: method,
			data: form_data,
			contentType: false,
			processData: false,
			xhrFields: {
				withCredentials: true,
			},
			/**
			 * Send the proper X-WP-Nonce header information along with the request
			 *
			 * @param {XMLHttpRequest} jqXhr jQuery XMLHttpRequest
			 */
			beforeSend: function ( jqXhr ) {
				jqXhr.setRequestHeader( 'X-WP-Nonce', submission_nonce );
			},
			/**
			 * Parses the response from the server and displays the success or error message.
			 *
			 * @param {string} response_data - The response from the rest api.
			 * @returns {void}
			 */
			 success: function ( response_data ) {
				form.hide();
				if ( response_data.status === 'success' ) {
					$( '#submission_return_message' ).html(
						response_data.message
						+ '<br>'
						+ response_data.submission_date_message
						+ '<br>'
						+ response_data.submission_code_message
					 );
				} else {
					$( '#submission_return_message' ).html( response_data.message );
				}
			},
		} );
	} );
} );
