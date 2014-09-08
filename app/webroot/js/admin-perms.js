(function($){

	$('#admin_refreshall').on('click', function(){

		if (!confirm('Are you sure you want to synchronize admin permissions with the Sourcebans database?')) {
			return false;
		}

		var el = $(this);

		$.ajax(el.attr('href') || el.data('href'), {

			type: 'post',
			beforeSend: function(){
				$('#admin_loading').fadeIn();
			},

			success: function(data, textStatus) {
				$('#admin_data').html(data);
				$('#admin_loading').fadeOut();
			}
		});

		return false;
	});

})(jQuery);