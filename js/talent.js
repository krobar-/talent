// JavaScript for Talent Casting Database Plugin
if( !( 'contains' in String.prototype ) )
{
  String.prototype.contains = function( str, startIndex ) { return -1 !== String.prototype.indexOf.call( this, str, startIndex ); };
}

if( !( 'empty' in String.prototype ) && !( 'empty' in Array.prototype ) )
{
	String.prototype.empty = 
	Array.prototype.empty =
	function( data ){
		return empty( data );
	};
	
}

function empty( data )
{
	if(typeof(data) == 'number' && data === 0)
	{
		return true;
	}
	if(typeof(data) == 'number' || typeof(data) == 'boolean')
	{
		return false;
	}
	if(typeof(data) == 'undefined' || data === null)
	{
		return true;
	}
	if(typeof(data.length) != 'undefined')
	{
		if(/^[s]*$/.test(data.toString()))
		{
			return true;
		}
		return data.length == 0;
	}
	var count = 0;
	for(var i in data)
	{
		if(data.hasOwnProperty(i))
		{
			count ++;
		}
	}
	return count == 0;
}

jQuery( document ).ready( function( $ ){

	function sendImage( type )
	{
		var formData = new FormData();

		//formData.append( 'tcd_headshot', file );
		if( type == 'headshot' )
		{
			formData.append( 'tcd_headshot', document.getElementById('headshot-upload').files[0] );
			formData.append( "tcd_call", "headshot" );
		}
		if( type == 'auxillary' )
		{
			formData.append( 'tcd_auxillary', document.getElementById('auxillary-upload').files[0] );
			formData.append( "tcd_call", "auxillary" );
		}

		formData.append( "action", "talent" );

		if( $("#tcd-person-id").length )
		{
			var pid = $("#tcd-person-id").val();
			formData.append( "tcd_person_id", pid );
		}

		$.ajax({
			type 			: 'POST',
			url 			: TCD.ajaxUrl,
			data 			: formData,
			cache 			: false,
			contentType 	: false,
			processData 	: false,
			success			: function( msg )
			{
				console.log( "success" );
				console.log( msg );

				if( type == 'headshot' )
				{
					$("#tcd-image-headshot").val(msg.full_image);
					$("#tcd-image-headshot-thumb").val(msg.thumbnail);
					$("#tcd-person-headshot").css({'background-image':'url('+ msg.upload_url + msg.thumbnail +')'});
					$("#tcd-person-headshot #tcd-ajax-loader").css({'display':'none'});
					// clear file input
					var fileSpan = $('#tcd-headshot-upload-input');
					fileSpan.html(fileSpan.html());
				}
				if( type == 'auxillary' )
				{
					var count = $("#tcd-auxillary-container .tcd-auxillary-image").length;
					addImageContainer( count );
					$("#tcd-image-" + count).val(msg.full_image);
					$("#tcd-image-thumb-" + count).val(msg.thumbnail);
					$("#tcd-aux-preview-" + count).css({'background-image':'url('+ msg.upload_url + msg.thumbnail +')'});
					var fileSpan = $('#tcd-auxillary-upload-input');
					fileSpan.html(fileSpan.html());
				}
			},
			error			: function( msg )
			{
				console.log( "AJAX upload error" );
				console.log( msg );
			}
		});
	}

	function addImageContainer( index )
	{
		//tcd-auxillary-container
		$('<div class="tcd-auxillary-image"></div>' )
		.attr("id", "tcd-aux-" + index)
		.appendTo("#tcd-auxillary-container");

		$('<div class="tcd-auxillary-preview"></div>')
		.attr("id", "tcd-aux-preview-" + index )
		.appendTo("#tcd-aux-" + index  );

		$('<input type="hidden" name="tcd_image['+index+'][full]" value="" />')
		.attr("id", "tcd-image-" + index )
		.appendTo("#tcd-aux-" + index  );

		$('<input type="hidden" name="tcd_image['+index+'][thumb]" value="" />')
		.attr("id", "tcd-image-thumb-" + index )
		.appendTo("#tcd-aux-" + index );
	}

	// headshot
	$("#headshot-upload").change(function()
	{
		sendImage( 'headshot' );
	});
	$("#headshot-upload").click(function()
	{
		$("#tcd-person-headshot").css({'background-image':'none'});
		$("#tcd-person-headshot #tcd-ajax-loader").css({'display':'block'});
	});

	// auxillary images
	$("#auxillary-upload").change(function()
	{
		sendImage( 'auxillary' );
	});
	$("#auxillary-upload").click(function()
	{
		//$("#tcd-person-headshot").css({'background-image':'none'});
		//$("#tcd-person-headshot #tcd-ajax-loader").css({'display':'block'});
	});

	$("#tcd-age").change(function()
	{
		displayOptions();
	});

	$("#tcd-gender").change(function()
	{
		displayOptions();
	});

	function displayOptions()
	{
		var age = parseInt($("#tcd-age").val()),
			sex = $("#tcd-gender").val();
		
		$(".tcd-size-mens").css({'display':'inline'});
		$(".tcd-size-womens").css({'display':'inline'});
		$(".tcd-size-childrens").css({'display':'inline'});
		
		if( !empty(age) )
		{
			if( age < 13 || sex === '0' || sex === '1' )
			{
				$(".tcd-size-mens").css({'display':'none'});
				$(".tcd-size-womens").css({'display':'none'});
				$(".tcd-size-childrens").css({'display':'inline'});
			}

			if( age > 12 )
			{
				if( sex === '0' || sex === '1' )
				{
					$(".tcd-size-childrens").css({'display':'none'});

					if( sex !== '1' )
					{
						$(".tcd-size-mens").css({'display':'inline'});
					}
					if( sex !== '0' )
					{
						$(".tcd-size-womens").css({'display':'inline'});
					}
				}
			}

			if( age < 18 )
			{
				$("#tcd-guardian").css({'display':'inline-block'});
			}
			else
			{
				$("#tcd-guardian").css({'display':'none'});
			}
		}
	}
	displayOptions();

} ); // end jQuery( document ).ready( function( $ ){

function empty(data)
{
	if(typeof(data) == 'number' || typeof(data) == 'boolean')
	{
		return false;
	}
	if(typeof(data) == 'undefined' || data === null)
	{
		return true;
	}
	if(typeof(data.length) != 'undefined')
	{
		return data.length == 0;
	}
	var count = 0;
	for(var i in data)
	{
		if(data.hasOwnProperty(i))
		{
			count ++;
		}
	}
	return count == 0;
}