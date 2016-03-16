(function ( $ ) {
	"use strict";

	$(function () {

		var clicked = false;

		// catch the upvote/downvote action
		$( 'div.comment-weight-container' ).on( 'click', 'span > a', function( e ){
			e.preventDefault();
			var value = 0;
			var comment_id = $(this).data('commentId');
			if ( $(this).hasClass( 'vote-up' ) ) {
				value = 'upvote';
			} else if( $(this).hasClass( 'vote-down' ) ) {
				value = 'downvote';
			}

			if ( false === clicked ) {
				clicked = true;
				var post = $.post(
					comment_popularity.ajaxurl, {
						action: 'comment_vote_callback',
						vote: value,
						comment_id: comment_id,
						hmn_vote_nonce: comment_popularity.hmn_vote_nonce
					}
				);

			post.done( function( data ) {
				var commentWeightContainer = $( '#comment-weight-value-' + data.data.comment_id );
				if ( data.success === false ) {
					$.growl.error({ message: data.data.error_message });
				} else {
					// update karma
					commentWeightContainer.text( data.data.weight );

					commentWeightContainer.closest( '.comment-weight-container ' ).children().removeClass();

					commentWeightContainer.addClass( data.data.vote_type );
					switch ( data.data.vote_type ) {
						case 'upvote':
							commentWeightContainer.prev().addClass( data.data.vote_type );
							break;
						case 'downvote':
							commentWeightContainer.next().addClass( data.data.vote_type );
							break;
						default:
							break;
					}

					$.growl.notice({ message: data.data.success_message });
				}

				clicked = false;
			});

			}
		});

	});

}(jQuery));
