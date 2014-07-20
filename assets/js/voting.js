(function ( $ ) {
	"use strict";

	$(function () {

		// catch the upvote/downvote action
		$( 'div.karma' ).on( 'click', 'span > a', function( e ){
			e.preventDefault();
			var value = 0;
			var comment_id = $(this).data('commentId');
			if ( $(this).hasClass( 'add-karma' ) ) {
				value = 1;
			} else if( $(this).hasClass( 'remove-karma' ) ) {
				value = -1;
			}

			$.post( comment_popularity.ajaxurl, {action: 'comment_vote', vote: value, comment_id: comment_id, hmn_vote_nonce: comment_popularity.hmn_vote_nonce}, function( data ){
				// update karma
				$( 'div#comment-' + data.data.comment_id + ' span.comment-karma' ).text( data.data.karma );
			});
		});

	});

}(jQuery));