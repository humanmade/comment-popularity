(function ( $ ) {
	"use strict";

	$(function () {

		// catch the upvote/downvote action
		$( 'div.comment-weight-container' ).on( 'click', 'span > a', function( e ){
			e.preventDefault();
			var value = 0;
			var comment_id = $(this).data('commentId');
			if ( $(this).hasClass( 'vote-up' ) ) {
				value = 1;
			} else if( $(this).hasClass( 'vote-down' ) ) {
				value = -1;
			}

			$.post( comment_popularity.ajaxurl, {action: 'comment_vote', vote: value, comment_id: comment_id, hmn_vote_nonce: comment_popularity.hmn_vote_nonce}, function( data ){
				// update karma
				$( 'div#comment-' + data.data.comment_id + ' span.comment-weight' ).text( data.data.weight );
			});
		});

	});

}(jQuery));