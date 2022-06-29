jQuery(document).ready(function($){
	$('#submission-form').on('submit', function(e){

		e.preventDefault();

		var form = $(this);
		form_data = new FormData();

		// regular fields that need to be processed
		form_data.append('wiki_username', document.createTextNode(form.prop('wiki_username').value));
		form_data.append('legal_name', document.createTextNode(form.prop('legal_name').value));
		form_data.append('date_birth', document.createTextNode(form.prop('date_birth').value));
		form_data.append('participant_email', document.createTextNode(form.prop('participant_email').value));
		form_data.append('phone_number', document.createTextNode(form.prop('phone_number').value));
		form_data.append('authors_contributed', document.createTextNode(form.prop('authors_contributed').value));
		form_data.append('explanation_creation', document.createTextNode(form.prop('explanation_creation').value));
		form_data.append('explanation_inspiration', document.createTextNode(form.prop('explanation_inspiration').value));

		// nonce
		form_data.append('_submissionnonce', form.prop('_submissionnonce').value);

		// audio file - unprocessed field
		file_data = form.prop('audio_file')['files'][0];
		form_data.append('audio_file', file_data);

		var url = submission_form_ajax_object.api_url;
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
						+ response_data.submission_date_message
						+ "<br>"
						+ response_data.submission_code_message
					);
				} else {
					$('#submission_return_message').html(response_data.message);
				}
			}
		});
	});
});
