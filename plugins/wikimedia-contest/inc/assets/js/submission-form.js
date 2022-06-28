jQuery(document).ready(function($){
	$('#submission-form').on('submit', function(e){

		e.preventDefault();

		var form = $(this);
		form_data = new FormData();

		// regular fields that need to be processed
		form_data.append('wiki_username', form.prop('wiki_username').value);
		form_data.append('legal_name', form.prop('legal_name').value);
		form_data.append('date_birth', form.prop('date_birth').value);
		form_data.append('participant_email', form.prop('participant_email').value);
		form_data.append('phone_number', form.prop('phone_number').value);
		form_data.append('authors_contributed', form.prop('authors_contributed').value);
		form_data.append('explanation_creation', form.prop('explanation_creation').value);
		form_data.append('explanation_inspiration', form.prop('explanation_inspiration').value);

		// nonce
		form_data.append('_submissionnonce', form.prop('_submissionnonce').value);

		// audio file - unprocessed field
		file_data = form.prop('audio_file')['files'][0];
		form_data.append('audio_file', file_data);

		var url = '/wp-json/wikimedia-contest/v1/submission/';
		var method = form.attr('method');

		$.ajax({
			url: url,
			type: method,
			data: form_data,
			contentType: false,
			processData: false,
			success: function(response_data) {
				form.hide();
				if (response_data.status == 'success') {
					$('#submission_return_message').html(
						response_data.message
						+ "<br>"
						+ response_data.submission_code_message
						+ response_data.submission_unique_code
					);
				} else {
					$('#submission_return_message').html(response_data.message);
				}
			}
		});
	});
});
