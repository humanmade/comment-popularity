(function ( $ ) {
	"use strict";

	$(function () {

		var clicked = false;

		// catch the upvote/downvote action
		$( 'div.comment-weight-container' ).on( 'click', 'span > a', _.throttle( function( e ){
			e.preventDefault();
			var value = 0,
				comment_id = $(this).data( 'commentId' ),
				containerClass = $(this).closest( 'span' ).attr( 'class' );

			if ( containerClass !== 'upvote' && $(this).hasClass( 'vote-up' ) ) {
				value = 'upvote';
			} else if( containerClass !== 'downvote' && $(this).hasClass( 'vote-down' ) ) {
				value = 'downvote';
			} else if ( containerClass === 'downvote' && $(this).hasClass( 'vote-down' ) || containerClass === 'upvote' && $(this).hasClass( 'vote-up' ) ) {
				value = 'undo';
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

						// clear all classes
						commentWeightContainer.closest( '.comment-weight-container ' ).children().removeClass();

						if ( data.data.vote_type !== 'undo' ) {

							commentWeightContainer.addClass(data.data.vote_type);
							switch (data.data.vote_type) {
								case 'upvote':
									commentWeightContainer.prev().addClass(data.data.vote_type);
									break;
								case 'downvote':
									commentWeightContainer.next().addClass(data.data.vote_type);
									break;
								default:
									break;
							}


						}
						$.growl.notice({ message: data.data.success_message });
					}

					clicked = false;
				});

			}
		}, 500));

	});

}(jQuery));
