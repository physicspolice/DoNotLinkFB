$(document).ready(function()
{
	$('input[name="url"]').focus();
	$('div, p.submit img, #link').hide();

	$('input[name="url"]').keyup(function()
	{
		var url = $(this).val();
		$('input[type="submit"]').prop('disabled', !url.match(/^https?:\/\//));
	});

	$('#images').on('click', 'img', function()
	{
		$('input[name="image"]').val($(this).attr('src'));
	});

	$('form').submit(function()
	{
		$('ul').empty();
		$('p.submit img').show();
		$('input[type="submit"]').prop('disabled', true);
		if($('input[type="submit"]').val() == 'Continue')
		{
			$('div').hide();
			$.ajax({
				'type': 'post',
				'dataType': 'json',
				'data': {
					'action': 'load',
					'load': $('input[name="url"]').val()
				}
			})
			.done(function(response)
			{
				if(response.errors)
				{
					for(var i in response.errors)
						$('ul').append($('<li />').text(response.errors[i]));
					return;
				}
				$('div').slideDown();
				$('input[type="submit"]').val('Create Good Link');
				$('input[name="title"]').val(response.title);
				$('input[name="description"]').val(response.description);
				$('#images').empty();
				if(response.images.length)
				{
					$('input[name="image"]').val(response.images[0]);
					for(var i in response.images)
						$('#images').append($('<img />').attr('src', response.images[i]));
				}
			})
			.fail(function()
			{
				$('ul').append($('<li />').text('Sorry, an error occurred. Please try again later.'));
			})
			.always(function()
			{
				$('p.submit img').hide();
				$('input[type="submit"]').prop('disabled', false);
			});
		}
		else
		{
			$.ajax({
				'type': 'post',
				'dataType': 'json',
				'data': {
					'action': 'create',
					'url':          $('input[name="url"]').val(),
					'title':        $('input[name="title"]').val(),
					'description':  $('input[name="description"]').val(),
					'image':        $('input[name="image"]').val() 
				}
			})
			.done(function(response)
			{
				if(response.errors)
				{
					for(var i in response.errors)
						$('ul').append($('<li />').text(response.errors[i]));
					return;
				}
				$('#link').slideDown().find('input').val(response.link);
			})
			.fail(function()
			{
				$('ul').append($('<li />').text('Sorry, an error occurred. Please try again later.'));
			})
			.always(function()
			{
				$('p.submit img').hide();
				$('input[type="submit"]').prop('disabled', false);
			});
		}
		return false;
	});

	$('#link input').focus(function()
	{
		$(this).select();
	});

	if($('#loading').length)
	{
		var url = base64decode($('#loading').attr('data-url'));
		$('#loading h3').text('Redirecting to...');
		$('#loading p').text(url);
		window.location.href = url;
	}
});
