<?php
use CommentPopularity\HMN_Comment_Popularity;

$GLOBALS['comment'] = $comment;

if ( 'pingback' == $comment->comment_type || 'trackback' == $comment->comment_type ) : ?>

<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
	<div class="comment-body">
		<?php _e( 'Pingback:', 'comment-popularity' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( __( 'Edit', 'comment-popularity' ), '<span class="edit-link">', '</span>' ); ?>
	</div>

	<?php else : ?>

<li id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'comment-parent' ); ?>>
	<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
		<header class="comment-header">
			<?php $hmn_cp_plugin = HMN_Comment_Popularity::get_instance(); $hmn_cp_plugin->render_ui( get_comment_ID() ); ?>
			<?php // Avatar
			if ( 0 != $args['avatar_size'] ) :
			echo get_avatar( $comment, $args['avatar_size'] );
			endif;

			?>

			<div class="comment-date">
				<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
					<time datetime="<?php comment_time( 'c' ); ?>">
						<?php
						printf(
						_x( '%1$s at %2$s', '1: date, 2: time', 'comment-popularity' ),
						get_comment_date(),
						get_comment_time()
						);
						?>
					</time>
				</a>
			</div>
			<div class="comment-author vcard">
				<?php
				$comment_author_email = get_comment_author_email( $comment->comment_ID );
				$author = get_user_by( 'email', $comment_author_email );
				$karma_element = '';
				if ( false !== $author ) {

					$author_karma = $hmn_cp_plugin->get_comment_author_karma( $author->ID );

					if ( false !== $author_karma ) {
						$karma_element = sprintf( _x( '%1$s( User Karma: %2$s )%3$s', '1: HTML tag 2: Karma value 3: HTML tag', 'comment-popularity' ), '<small class="user-karma">', esc_html( $author_karma ), '</small>' );
					}

				}
				printf(
				'%1$s %3$s <span class="says">%2$s</span>',
				sprintf(
				'<cite class="fn">%s</cite>',
				get_comment_author_link()
				),
				_x( 'says:', 'e.g. Bob says hello.', 'comment-popularity' ),
				$karma_element
				);

				?>
			</div>

			<?php if ( '0' == $comment->comment_approved ) : ?>
			<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'comment-popularity' ); ?></p>
			<?php endif; ?>
		</header>

		<div class="comment-content">
			<?php comment_text(); ?>
		</div>

		<?php
		comment_reply_link( array_merge( $args, array(
		'add_below' => 'div-comment',
		'depth'     => $depth,
		'max_depth' => $args['max_depth'],
		'before'    => '<footer class="comment-reply">',
		'after'     => '</footer>',
		) ) );
		?>
	</article>

<?php endif;