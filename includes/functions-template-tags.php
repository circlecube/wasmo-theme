<?php

/**
 * Posted by
 */
function wasmo_posted_by() {
	printf(
		/* translators: 1: SVG icon. 2: Post author, only visible to screen readers. 3: Author link. */
		'<span class="byline">%1$s<span class="screen-reader-text">%2$s</span><span class="author vcard"><a class="url fn n" href="%3$s">%4$s</a></span></span>',
		twentynineteen_get_icon_svg( 'person', 16 ),
		/* translators: Hidden accessibility text. */
		__( 'Posted by', 'twentynineteen' ),
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);
}

/**
 * Override twentynineteen_entry_footer
 */
function wasmo_entry_footer() {

	// Hide author, post date, category and tag text for pages.
	if ( 'post' === get_post_type() ) {
		$author = get_post_field( 'post_author', get_the_ID() );
		$user = get_user_by('id', $author);
		
		// Profile link
		if (has_category( 'spotlight', get_the_ID() ) && get_field( 'spotlight_for', get_the_ID() ) ) {
			$user_id = get_field( 'spotlight_for', get_the_ID() );
			$user = get_user_by( 'id', $user_id );
			?>
			<p>This post spotlights a real user's profile, please <a href="<?php echo get_author_posts_url($user_id); ?>">view the full profile for <?php echo $user->display_name;?> here</a>.</p>
			<?php
		}

		// Posted by
		if ( !$user->has_cap( 'manage_options' ) ) {
			twentynineteen_posted_by(); // hide author if admin
		}

		// Posted on
		twentynineteen_posted_on();

		/* translators: used between list items, there is a space after the comma. */
		$categories_list = get_the_category_list( __( ', ', 'wasmo' ) );
		if ( $categories_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of categories. */
				'<span class="cat-links">%1$s<span class="screen-reader-text">%2$s</span>%3$s</span>',
				wasmo_get_icon_svg( 'archive', 16 ),
				__( 'Posted in', 'wasmo' ),
				$categories_list
			); // WPCS: XSS OK.
		}

		/* translators: used between list items, there is a space after the comma. */
		$tags_list = get_the_tag_list( '', __( ', ', 'wasmo' ) );
		if ( $tags_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links">%1$s<span class="screen-reader-text">%2$s </span>%3$s</span>',
				wasmo_get_icon_svg( 'tag', 16 ),
				__( 'Tags:', 'wasmo' ),
				$tags_list
			); // WPCS: XSS OK.
		}

		// Related Shelf items
		$shelf_list = get_the_term_list( get_the_ID(), 'shelf', '', ', ', '' );
		if ( $shelf_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links shelf-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
				wasmo_get_icon_svg( 'shelf', 18, 'style="margin-top:-3px;"' ),
				__( 'Shelf items', 'wasmo' ),
				$shelf_list
			); // WPCS: XSS OK.
		}
		
		// Related Spectrum 
		$spectrum_list = get_the_term_list( get_the_ID(), 'spectrum', '', ', ', '' );
		if ( $spectrum_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links spectrum-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
				wasmo_get_icon_svg( 'spectrum', 16 ),
				__( 'Mormon Spectrum', 'wasmo' ),
				$spectrum_list
			); // WPCS: XSS OK.
		}

		// Related Questions
		$question_list = get_the_term_list( get_the_ID(), 'question', '', '<br>' . wasmo_get_icon_svg( 'question', 18, 'style="margin-top:-3px;"'), '' );
		if ( $question_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links question-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
				wasmo_get_icon_svg( 'question', 18, 'style="margin-top:-3px;"'),
				__( 'Questions', 'wasmo' ),
				$question_list
			); // WPCS: XSS OK.
		}

		// Related Church Leaders
		$related_leaders = get_field( 'related_leaders', get_the_ID() );
		if ( ! empty( $related_leaders ) ) {
			$leader_links = array();
			foreach ( $related_leaders as $leader_id ) {
				$leader = get_post( $leader_id );
				if ( $leader ) {
					$leader_links[] = '<a href="' . get_permalink( $leader_id ) . '">' . esc_html( $leader->post_title ) . '</a>';
				}
			}
			if ( ! empty( $leader_links ) ) {
				printf(
					'<span class="tags-links leader-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
					wasmo_get_icon_svg( 'saint', 16 ),
					__( 'Church Leaders:', 'wasmo' ),
					implode( ', ', $leader_links )
				);
			}
		}

		wasmo_post_navi();

	}

	// Comment count.
	if ( ! is_singular() ) {
		twentynineteen_comment_count();
	}

	// Edit post link.
	edit_post_link(
		sprintf(
			wp_kses(
				/* translators: %s: Name of current post. Only visible to screen readers. */
				__( 'Edit <span class="screen-reader-text">%s</span>', 'wasmo' ),
				array(
					'span' => array(
						'class' => array(),
					),
				)
			),
			get_the_title()
		),
		'<span class="edit-link">' . wasmo_get_icon_svg( 'edit', 16 ),
		'</span>'
	);
}

/**
 * Pagination Helper Method
 * 
 * @param Number $paged     Page Number
 * @param Number $max_page  Max Page
 * @param Boolean $profile   Flag for profile nav, this updates the baseurl and format so they work for the custom pagination
 * @return String Pagination links
 */
function wasmo_pagination( $paged = '', $max_page = '', $profiles = false ) {
	$big = 999999999; // need an unlikely integer

	if( ! $paged ) {
		$paged = get_query_var('paged');
	}

	if( ! $max_page ) {
		global $wp_query;
		$max_page = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
	}

	if ( $max_page > 7 ) {
		$show_all = false;
	} else {
		$show_all = true;
	}
	$base_url = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
	$format   = '?paged=%#%';

	if ( $profiles ) {
		$base_url = get_permalink( get_page_by_path( 'profiles' ) ) . 'page/%_%';
		$format   = '%#%';
	}
	$paginated_links = paginate_links( 
		array(
			'base'      => $base_url,
			'format'    => $format,
			'current'   => max( 1, $paged ),
			'total'     => $max_page,
			'mid_size'  => 1,
			'end_size'  => 1,
			'prev_text' => sprintf(
				'%s <span class="screen-reader-text">%s</span>',
				wasmo_get_icon_svg( 'chevron_left', 22 ),
				__( 'Newer', 'twentynineteen' )
			),
			'next_text' => sprintf(
				'<span class="screen-reader-text">%s</span> %s',
				__( 'Older', 'twentynineteen' ),
				wasmo_get_icon_svg( 'chevron_right', 22 )
			),
			'type'      => 'list',
			'show_all'  => $show_all,
		)
	);

	return '<div class="wasmo-pagination">' . $paginated_links . '</div>';
}

/**
 * Post navigation and pagination
 */
function wasmo_post_navi() {
	if( !is_singular('post') ) {
		return;
	}
	
	$prev_post = get_previous_post();
	$next_post = get_next_post();

	?>
	<h4 class="post-pagination">
		<span class="post-pagination-link post-pagination-prev">
			<?php
				if ( $prev_post ) {
					$prev_post_img = get_the_post_thumbnail( 
						$prev_post->ID, 
						'medium', 
						array('class' => 'pagination-prev')
					); 
					previous_post_link(
						'%link',
						wasmo_get_icon_svg( 'chevron_left', 22 ) . '<em>Older Post</em><span class="adjacent-post"><span class="adjacent-post-title">%title</span>' . $prev_post_img . '</span>'
					);
				}
			?>
		</span>
		<span class="post-pagination-link post-pagination-next">
			<?php
				if ( $next_post ) {
					$next_post_img = get_the_post_thumbnail( 
						$next_post->ID, 
						'medium', 
						array('class' => 'pagination-next')
					);
					next_post_link(
						'%link',
						'<em>Newer Post</em> '.wasmo_get_icon_svg( 'chevron_right', 22 ).'<span class="adjacent-post"><span class="adjacent-post-title">%title</span>' . $next_post_img . '</span>'
					);
				}
			?>
		</span>
	</h4>
	<?php
}

function wasmo_excerpt_link() {
	return '<a class="more-link button button-small" href="' . get_permalink() . '">Read more</a>';
}
add_filter( 'excerpt_more', 'wasmo_excerpt_link' );


/**
 * Add callout to create a profile to the top of each post
 * Only when user is not logged in
 * 
 * @param string $content The content.
 * @return string The modified content.
 */
function wasmo_before_after($content) {
	// skip if
	if (
		is_user_logged_in() // logged in or
		|| !is_single() // not a single post or
		|| !is_main_query() // not the main loop or
		|| is_embed() // is a post embed
		|| is_singular( 'saint' ) // is a saint post
	) {
		return $content;
	}

	// top
	if ( get_field( 'before_post_callout', 'option' ) ) {
		$top_callout = '<aside class="callout callout-top">';
		$top_callout .= get_field( 'before_post_callout', 'option' );
		$top_callout .= '<h5>Recent Profiles</h5>';
		ob_start();
		set_query_var( 'max_profiles', 4 );
		set_query_var( 'context', 'bumper' );
		get_template_part( 'template-parts/content/content', 'directory' );
		$top_callout .= ob_get_clean(); 
		$top_callout .= '</aside>';
	} else {
		ob_start();
		?>
		<aside class="callout callout-top">
			<h4>Thank you for visiting wasmormon.org!</h4>
			<p>This site is mainly a repository of mormon faith transition stories. Hearing others stories is therapeutic, check out the <a href="/profiles/">was mormon profiles</a>.</p>
			<p>Telling your own story is therapeutic too, consider joining the movement and <a class="register" href="/login/">tell your own story now</a>!</p>
		</aside>
		<?php 
		$top_callout = ob_get_clean();
	}

	// bottom
	if ( get_field( 'after_post_callout', 'option' ) ) {
		$bottom_callout = '<aside class="callout callout-bottom">' . get_field( 'after_post_callout', 'option' ) . '</aside>';
	} else {
		ob_start();
		?>
		<aside class="callout callout-bottom">
			<h4>Thank you for reading!</h4>
			<p>Don't forget to also check out the <a href="/profiles/">mormon faith transition stories</a>.</p>
			<div class="wp-block-button"><a class="wp-block-button__link" href="/login/">Tell Your Own Story</a></div>
		</aside>
		<?php 
		$bottom_callout = ob_get_clean();
	}
	
	$fullcontent = $top_callout . $content . $bottom_callout;

	return $fullcontent;
}
add_filter( 'the_content', 'wasmo_before_after' );

/**
 * Replace links in text with html links
 *
 * @param  string $text Text to add links to
 * @return string Text with links added
 */
function wasmo_auto_link_text( $text ) {
	$pattern = "#\b((?:https?:(?:/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)/)(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\)|[^\s`!()\[\]{};:'.,<>?«»“”‘’])|(?:(?<!@)[a-z0-9]+(?:[.\-][a-z0-9]+)*[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\b/?(?!@)))#";
	return preg_replace_callback( $pattern, function( $matches ) {
		$url = array_shift( $matches );

		// force http if no protocol included
		if ( !wasmo_starts_with( $url, 'http' ) ) {
			$url = 'http://' . $url;
		}

		// make link text from url - removing protocol
		$text = parse_url( $url, PHP_URL_HOST ) . parse_url( $url, PHP_URL_PATH );
		
		// remove the www from the link text
		$text = preg_replace( "/^www./", "", $text );

		// remove any long trailing path from url
		$last = -( strlen( strrchr( $text, "/" ) ) ) + 1;
		if ( $last < 0 ) {
			$text = substr( $text, 0, $last ) . "&hellip;";
		}

		// update 
		return sprintf(
			'<a rel="nofollow ugc" target="_blank" href="%s">%s</a>', 
			$url, 
			$text
		);
	}, $text );
}

/**
 * Check strings for starting match
 *
 * @param  string $string String to check.
 * @param  string $startString Startin string to match.
 * @return boolean Wether string begins with startString. 
 */
function wasmo_starts_with( $string, $startString ) {
	$len = strlen($startString); 
	return (substr($string, 0, $len) === $startString); 
}

/**
 * Replace pseudo __HTML__ in text with elements
 *
 * @param  string $text Text to add hrs to
 * @return string Text with hrs added
 */
function wasmo_auto_htmlize_text( $text ) {
	
	$patterns = array(
		'<p>__HR__</p>', // make hr
		'BLOCKQUOTE__', // open blockquote
		'__BLOCKQUOTE', // close blockquote
		'CITE__', // open cite
		'__CITE', // close cite
		'STRONG__', // open strong
		'__STRONG', // close strong
		'EM__', // open italics
		'__EM', // close italics
	);
	$replacements = array(
		'<hr class="wp-block-separator profile-hr" />',
		'<blockquote class="wp-block-quote profile-blockquote">',
		'</blockquote>',
		'<cite class="profile-cite">',
		'</cite>',
		'<strong class="profile-strong">',
		'</strong>',
		'<em class="profile-em">',
		'</em>',
	);
	return str_replace( $patterns, $replacements, $text );
}

/**
 * Some custom structure to apply to the signup form page `local-signup` via NSUR plugin
 */
function wasmo_before_signup() {
	?>
	<div class="site-content entry">
		<div class="entry-content">
			<h1>Register</h1>
			<p>Choose a username (lowercase letters and numbers only) and enter your email address.</p>
	<?php
}
// add_action( 'before_signup_form', 'wasmo_before_signup', 10 ); // specific to multisite

/**
 * After signup form
 */
function wasmo_after_signup() {
	echo '</div></div>';
}
// add_action( 'after_signup_form', 'wasmo_after_signup', 10 ); // specific to multisite

/**
 * Random profile
 */
function wasmo_random_add_rewrite() {
	global $wp;
	$wp->add_query_var( 'randomprofile' );
	add_rewrite_rule( 'random/?$', '?randomprofile=1', 'top' );
}
add_action( 'init', 'wasmo_random_add_rewrite' );

/**
 * Redirect to random profile
 */
function wasmo_random_profile_template() {
   if ( get_query_var('randomprofile') ) {
			wp_redirect( wasmo_get_random_profile_url(), 307 );
			exit;
   }
}
add_action('template_redirect','wasmo_random_profile_template');

/**
 * Get random profile url
 */
function wasmo_get_random_profile_url() {
	$args = array(
		'orderby'     => 'rand',
		// 'numberposts' => 1
	);
	$users = get_users( $args );
	foreach ( $users as $user ) {
		// check that user has content and is public
		if (
			! get_field( 'hi', 'user_' . $user->ID ) ||
			'private' === get_user_meta( $user->ID, 'in_directory', true ) ||
			'false' === get_user_meta( $user->ID, 'in_directory', true )
		) {
			continue;
		}
		return get_author_posts_url( $user->ID );
	}
}

/**
 * Random user query
 * 
 * @param WP_User_Query $class The user query object.
 * @return WP_User_Query The modified user query object.
 */
function wasmo_random_user_query( $class ) {
	if( 'rand' == $class->query_vars['orderby'] ) {
		$class->query_orderby = str_replace(
			'user_login',
			'RAND()',
			$class->query_orderby
		);
	}
	return $class;
}
add_action( 'pre_user_query', 'wasmo_random_user_query' );

/**
 * Update amazon links with associate tag
 * 
 * @param string $content The content.
 * @param string $tag The tag.
 * @return string The modified content.
 */
function wasmo_add_zon_tag($content, $tag = 'circubstu-20' ) {
	$all_links = wp_extract_urls( $content );
	$zon_links = array_filter( 
		$all_links,
		function ($link) {
		if ( str_contains( $link, 'amazon.' ) ||  str_contains( $link, 'amzn.to' ) ) {
			return true;
		}
		return false;
		}
	);
	foreach( $zon_links as $link ) {
		$content = str_replace( $link, add_query_arg('tag', $tag, $link), $content );
	}
	return $content;
}
add_filter( 'the_content', 'wasmo_add_zon_tag' );

/**
 * Icon svg method for wasmo theme.
 * 
 * @param String $icon string value.
 * @param Number $size number pixel value.
 * @param String $styles a styles attribute for any custom styles, such as `style="margin-left:20px;"`.
 * @return String svg element.
 */
function wasmo_get_icon_svg( $icon, $size = 24, $styles = '' ) {
	// map taxonomies to an icon
	switch ($icon) {
		case 'shelf':
			$icon = 'flag';
			break;
		case 'spectrum':
			$icon = 'nametag';
			break;
		case 'question':
			$icon = 'help';
			break;
		case 'saint':
			$icon = 'leader';
			break;
	}

	// collected from https://github.com/WordPress/dashicons/tree/master/sources/svg
	$arr = array(
		'warning' => /* warning - dashicon */ '
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 20 20">
	<path d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zM11.13 11.38l0.35-6.46h-2.96l0.35 6.46h2.26zM11.040 14.74c0.24-0.23 0.37-0.55 0.37-0.96 0-0.42-0.12-0.74-0.36-0.97s-0.59-0.35-1.060-0.35-0.82 0.12-1.070 0.35-0.37 0.55-0.37 0.97c0 0.41 0.13 0.73 0.38 0.96 0.26 0.23 0.61 0.34 1.060 0.34s0.8-0.11 1.050-0.34z"/>
</svg>',
		'help'    => /* help dashicon */'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 20 20">
	<path d="M17 10c0-3.87-3.14-7-7-7-3.87 0-7 3.13-7 7s3.13 7 7 7c3.86 0 7-3.13 7-7zM10.7 11.48h-1.56v-0.43c0-0.38 0.080-0.7 0.24-0.98s0.46-0.57 0.88-0.89c0.41-0.29 0.68-0.53 0.81-0.71 0.14-0.18 0.2-0.39 0.2-0.62 0-0.25-0.090-0.44-0.28-0.58-0.19-0.13-0.45-0.19-0.79-0.19-0.58 0-1.25 0.19-2 0.57l-0.64-1.28c0.87-0.49 1.8-0.74 2.77-0.74 0.81 0 1.45 0.2 1.92 0.58 0.48 0.39 0.71 0.91 0.71 1.55 0 0.43-0.090 0.8-0.29 1.11-0.19 0.32-0.57 0.67-1.11 1.060-0.38 0.28-0.61 0.49-0.71 0.63-0.1 0.15-0.15 0.34-0.15 0.57v0.35zM9.23 14.22c-0.18-0.17-0.27-0.42-0.27-0.73 0-0.33 0.080-0.58 0.26-0.75s0.43-0.25 0.77-0.25c0.32 0 0.57 0.090 0.75 0.26s0.27 0.42 0.27 0.74c0 0.3-0.090 0.55-0.27 0.72-0.18 0.18-0.43 0.27-0.75 0.27-0.33 0-0.58-0.090-0.76-0.26z"/>
</svg>',
		'flag'   => /* flag dashicon */ '
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 20 20">
	<path d="M5 18v-15h-2v15h2zM6 12v-8c3-1 7 1 11 0v8c-3 1.27-8-1-11 0z"/>
</svg>',
		'nametag' => /* dashicon nametag */ '
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">
	<path d="M12 5v-3c0-0.55-0.45-1-1-1h-2c-0.55 0-1 0.45-1 1v3c0 0.55 0.45 1 1 1h2c0.55 0 1-0.45 1-1zM10 2c0.55 0 1 0.45 1 1s-0.45 1-1 1-1-0.45-1-1 0.45-1 1-1zM18 15v-8c0-1.1-0.9-2-2-2h-3v0.33c0 0.92-0.75 1.67-1.67 1.67h-2.66c-0.92 0-1.67-0.75-1.67-1.67v-0.33h-3c-1.1 0-2 0.9-2 2v8c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2zM17 9v6h-14v-6h14zM9 11c0-0.55-0.22-1-0.5-1s-0.5 0.45-0.5 1 0.22 1 0.5 1 0.5-0.45 0.5-1zM12 11c0-0.55-0.22-1-0.5-1s-0.5 0.45-0.5 1 0.22 1 0.5 1 0.5-0.45 0.5-1zM6.040 12.21c0.92 0.48 2.34 0.79 3.96 0.79s3.040-0.31 3.96-0.79c-0.21 1-1.89 1.79-3.96 1.79s-3.75-0.79-3.96-1.79z"></path>
</svg>',
		'edit-page' => /* dashicon edit-page */ '
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20" style="enable-background:new 0 0 20 20;" xml:space="preserve">
   <path d="M4,5H2v13h10v-2H4V5z M17.9,3.4l-1.3-1.3C16.2,1.7,15.5,1.6,15,2l0,0l-1,1H5v12h9V9l4-4l0,0C18.4,4.5,18.3,3.8,17.9,3.4z M12.2,9.4l-2.5,0.9l0.9-2.5L15,3.4L16.6,5L12.2,9.4z"/>
</svg>',
		'location' => /* dashicon location */ '
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">
<path d="M10 2c-3.31 0-6 2.69-6 6 0 2.020 1.17 3.71 2.53 4.89 0.43 0.37 1.18 0.96 1.85 1.83 0.74 0.97 1.41 2.010 1.62 2.71 0.21-0.7 0.88-1.74 1.62-2.71 0.67-0.87 1.42-1.46 1.85-1.83 1.36-1.18 2.53-2.87 2.53-4.89 0-3.31-2.69-6-6-6zM10 4.56c1.9 0 3.44 1.54 3.44 3.44s-1.54 3.44-3.44 3.44-3.44-1.54-3.44-3.44 1.54-3.44 3.44-3.44z"></path>
</svg>',
		'join'   => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none"/><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
</svg>',
		'login'  => '
<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24">
	<g><rect fill="none" height="24" width="24"/></g>
	<g><path d="M11,7L9.6,8.4l2.6,2.6H2v2h10.2l-2.6,2.6L11,17l5-5L11,7z M20,19h-8v2h8c1.1,0,2-0.9,2-2V5c0-1.1-0.9-2-2-2h-8v2h8V19z"/></g>
</svg>',
		'link'   => /* material-design – link */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none"></path>
	<path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"></path>
</svg>',
		'watch'  => /* material-design – watch-later */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<defs><path id="a" d="M0 0h24v24H0V0z"></path></defs>
	<clipPath id="b"><use xlink:href="#a" overflow="visible"></use></clipPath>
	<path clip-path="url(#b)" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"></path>
</svg>',
		'archive' => /* material-design – folder */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'tag' => /* material-design – local_offer */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',
		'comment' => /* material-design – comment */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	<path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',
		'person' => /* material-design – person */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',
		'edit' => /* material-design – edit */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	<path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',
		'chevron_left' => /* material-design – chevron_left */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	<path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',
		'chevron_right' => /* material-design – chevron_right */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',
		'check' => /* material-design – check */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none"></path>
	<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path>
</svg>',
		'leader' => /* dashicon businessperson */ '
<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 20 20">
  <path d="M13.2 10 11 13l-1-1.4L9 13l-2.2-3C3 11 3 13 3 16.9c0 0 3 1.1 6.4 1.1h1.2c3.4-.1 6.4-1.1 6.4-1.1 0-3.9 0-5.9-3.8-6.9zm-3.2.7L8.4 10l1.6 1.6 1.6-1.6-1.6.7zm0-8.6c-1.9 0-3 1.8-2.7 3.8.3 2 1.3 3.4 2.7 3.4s2.4-1.4 2.7-3.4c.3-2.1-.8-3.8-2.7-3.8z"/>
</svg>',
		'businessman' => /* dashicon businessman */ '
<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 20 20">
  <path d="M17 16.9v-2.5a4.4 4.4 0 0 0-2.1-3.8c-.7-.5-2.2-.6-2.9-.6l-1.6 1.7.6 1.3v3l-1 1.1L9 16v-3l.7-1.3L8 10c-.8 0-2.3.1-3 .6-.7.4-1.1 1-1.5 1.7S3 13.6 3 14.4v2.5S5.6 18 10 18s7-1.1 7-1.1zM10 2.1c-1.9 0-3 1.8-2.7 3.8.3 2 1.3 3.4 2.7 3.4s2.4-1.4 2.7-3.4c.3-2.1-.8-3.8-2.7-3.8z"/>
</svg>',
		'businesswoman' => /* dashicon businesswoman */ '
<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 20 20">
  <path d="M16 11c-.9-.8-2.2-.9-3.4-1l1 2.1-3.6 3.7-3.6-3.6 1-2.2c-1.2 0-2.5.2-3.4 1-.8.7-1 1.9-1 3.1v2.8a22 22 0 0 0 14 0v-2.8c0-1.1-.2-2.3-1-3.1zM6.6 9.3c.8 0 2-.4 2.2-.7-.8-1-1.5-2-.8-3.9 0 0 1.1 1.2 4.3 1.5 0 1-.5 1.7-1.1 2.4.2.3 1.4.7 2.2.7s1.4-.2 1.4-.5-1.3-1.3-1.6-2.2c-.3-.9-.1-1.9-.5-3.1-.6-1.4-2-1.5-2.7-1.5-.7 0-2.1.1-2.7 1.5-.4 1.2-.2 2.2-.5 3.1-.3.9-1.6 1.9-1.6 2.2 0 .3.6.5 1.4.5z"/>
  <path d="m10 11-2.3-1 2.3 5.8 2.3-5.8z"/>
</svg>',
		'familysearch' => /* familysearch icon */ '
<svg class="fs-icon" viewBox="0 0 48 48" fill="currentColor" width="800" height="800">
	<defs>
		<style>
		.c{fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round}
		</style>
	</defs>
	<path d="M13.6 18.7h5.5v5.5h-5.5zm14.5-3.3h5.1v5.1h-5.1zm-6.7 1h4.3v4.3h-4.3zm3 6.7h5.4v5.4h-5.4zm7.7-.1h2.3v2.3h-2.3zm-15.7-9.5h2.9v2.9h-2.9zm5-3.6h4.3v4.3h-4.3z" class="c"/>
	<path d="M23 20.8c-3.8 12 3.7 17.3 3.7 17.3h-3.3s-6.7-6.6-.4-17.3" class="c"/>
	<path d="M25.6 28.5s-.8 2.3-3 3.3m-5.2-7.6s.4 1.9 2.7 3.5" class="c"/>
</svg>',

		// Twemoji reaction icons (MIT licensed from https://github.com/jdecked/twemoji)
		'reaction-heart' => /* Red Heart ❤️ */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#DD2E44" d="M35.885 11.833c0-5.45-4.418-9.868-9.867-9.868-3.308 0-6.227 1.633-8.018 4.129-1.791-2.496-4.71-4.129-8.017-4.129-5.45 0-9.868 4.417-9.868 9.868 0 .772.098 1.52.266 2.241C1.751 22.587 11.216 31.568 18 34.034c6.783-2.466 16.249-11.447 17.617-19.959.17-.721.268-1.469.268-2.242z"/></svg>',
		'reaction-hug' => /* Hugging Face 🤗 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#FFCC4D" d="M34 16c0 8.834-7.166 16-16 16-8.836 0-16-7.166-16-16C2 7.164 9.164 0 18 0c8.834 0 16 7.164 16 16"/><path fill="#664500" d="M25.861 16.129c-.15-.161-.374-.171-.535-.029-.033.029-3.303 2.9-7.326 2.9-4.013 0-7.293-2.871-7.326-2.9-.162-.142-.385-.13-.535.029-.149.16-.182.424-.079.628C10.168 16.972 12.769 22 18 22s7.833-5.028 7.94-5.243c.103-.205.07-.468-.079-.628z"/><path d="M13.393 23.154l-1.539-2.219s-.543-.867-1.411-.325c-.867.541-.324 1.409-.324 1.409l1.758 3.38s.144.768-.918-.091c-.463-.374-.197-.163-.197-.163l-.44-.361-5.491-4.508s-.709-.748-1.359.042c-.648.791.223 1.341.223 1.341l5.193 4.266c-.12.12-.482.534-.6.663l-5.557-4.56s-.71-.749-1.358.041c-.65.791.222 1.341.222 1.341l5.555 4.562c-.103.14-.363.476-.459.614l-4.792-3.934s-.71-.748-1.358.042c-.651.791.222 1.342.222 1.342l5.094 4.184c-.064.178-.363.646-.41.82l-3.672-3.012s-.709-.752-1.357.041c-.65.791.222 1.341.222 1.341l5.93 4.868.395.326c2.62 2.151 6.489 1.771 8.64-.849 1.971-2.403 1.817-5.853-.24-8.069-.729-.782-1.36-1.653-1.972-2.532zm22.19 4.916c-.648-.793-1.357-.041-1.357-.041l-3.672 3.012c-.047-.174-.346-.643-.41-.82l5.094-4.184s.873-.551.223-1.342c-.648-.79-1.358-.042-1.358-.042l-4.792 3.934c-.096-.139-.357-.475-.459-.614l5.555-4.562s.873-.55.223-1.341c-.648-.79-1.357-.041-1.357-.041l-5.558 4.56c-.117-.129-.479-.543-.6-.663l5.193-4.266s.87-.55.223-1.341c-.65-.79-1.359-.042-1.359-.042l-5.491 4.508-.439.361s.99-.841-.197.163c-1.188 1.004-.918.091-.918.091l1.758-3.38s.543-.868-.324-1.409c-.869-.542-1.411.325-1.411.325l-1.538 2.219c-.613.879-1.244 1.75-1.974 2.533-2.058 2.217-2.212 5.666-.239 8.069 2.15 2.62 6.02 3 8.64.849l.396-.326 5.93-4.868c-.005-.001.867-.551.218-1.342z" fill="#F4900C"/><path fill="#664500" d="M27.677 10.983C27.626 10.861 26.392 8 23.856 8c-2.534 0-3.768 2.861-3.819 2.983-.08.188-.028.406.123.536.149.128.365.132.523.012.01-.009 1.081-.816 3.173-.816 2.079 0 3.149.797 3.174.816.075.059.165.089.255.089.095 0 .189-.034.267-.099.153-.129.205-.35.125-.538zm-11 0C16.625 10.861 15.392 8 12.856 8c-2.534 0-3.768 2.861-3.82 2.983-.079.188-.028.406.124.536.15.128.366.132.523.012.011-.009 1.081-.816 3.173-.816 2.08 0 3.149.797 3.173.816.076.059.165.089.255.089.095 0 .189-.034.267-.099.154-.129.205-.35.126-.538z"/><path fill="#B55005" d="M11.182 25.478s-2.593 3.314.484 7.46c.212.285.75.146.521-.188-.25-.364-2.75-3.896-.4-7.057 0 0-.381-.047-.605-.215zm13.583.098s2.594 3.314-.484 7.46c-.212.285-.75.146-.521-.188.25-.364 2.75-3.896.4-7.057-.001.001.381-.046.605-.215z"/></svg>',
		'reaction-like' => /* Thumbs Up 👍 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#FFDB5E" d="M34.956 17.916c0-.503-.12-.975-.321-1.404-1.341-4.326-7.619-4.01-16.549-4.221-1.493-.035-.639-1.798-.115-5.668.341-2.517-1.282-6.382-4.01-6.382-4.498 0-.171 3.548-4.148 12.322-2.125 4.688-6.875 2.062-6.875 6.771v10.719c0 1.833.18 3.595 2.758 3.885C8.195 34.219 7.633 36 11.238 36h18.044c1.838 0 3.333-1.496 3.333-3.334 0-.762-.267-1.456-.698-2.018 1.02-.571 1.72-1.649 1.72-2.899 0-.76-.266-1.454-.696-2.015 1.023-.57 1.725-1.649 1.725-2.901 0-.909-.368-1.733-.961-2.336.757-.611 1.251-1.535 1.251-2.581z"/><path fill="#EE9547" d="M23.02 21.249h8.604c1.17 0 2.268-.626 2.866-1.633.246-.415.109-.952-.307-1.199-.415-.247-.952-.108-1.199.307-.283.479-.806.775-1.361.775h-8.81c-.873 0-1.583-.71-1.583-1.583s.71-1.583 1.583-1.583H28.7c.483 0 .875-.392.875-.875s-.392-.875-.875-.875h-5.888c-1.838 0-3.333 1.495-3.333 3.333 0 1.025.475 1.932 1.205 2.544-.615.605-.998 1.445-.998 2.373 0 1.028.478 1.938 1.212 2.549-.611.604-.99 1.441-.99 2.367 0 1.12.559 2.108 1.409 2.713-.524.589-.852 1.356-.852 2.204 0 1.838 1.495 3.333 3.333 3.333h5.484c1.17 0 2.269-.625 2.867-1.632.247-.415.11-.952-.305-1.199-.416-.245-.953-.11-1.199.305-.285.479-.808.776-1.363.776h-5.484c-.873 0-1.583-.71-1.583-1.583s.71-1.583 1.583-1.583h6.506c1.17 0 2.27-.626 2.867-1.633.247-.416.11-.953-.305-1.199-.419-.251-.954-.11-1.199.305-.289.487-.799.777-1.363.777h-7.063c-.873 0-1.583-.711-1.583-1.584s.71-1.583 1.583-1.583h8.091c1.17 0 2.269-.625 2.867-1.632.247-.415.11-.952-.305-1.199-.417-.246-.953-.11-1.199.305-.289.486-.799.776-1.363.776H23.02c-.873 0-1.583-.71-1.583-1.583s.709-1.584 1.583-1.584z"/></svg>',
		'reaction-hundred' => /* Hundred Points 💯 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#BB1A34" d="M1.728 21c-.617 0-.953-.256-1.127-.471-.171-.211-.348-.585-.225-1.165L3.104 6.658l-1.714.097h-.013c-.517 0-.892-.168-1.127-.459-.22-.272-.299-.621-.221-.98.15-.702.883-1.286 1.667-1.329l4.008-.227c.078-.005.15-.008.217-.008.147 0 .536 0 .783.306.252.312.167.709.139.839L3.719 19.454c-.187.884-.919 1.489-1.866 1.542L1.728 21zm10.743-2c-1.439 0-2.635-.539-3.459-1.559-1.163-1.439-1.467-3.651-.878-6.397 1.032-4.812 4.208-8.186 7.902-8.395 1.59-.089 2.906.452 3.793 1.549 1.163 1.439 1.467 3.651.878 6.397-1.032 4.81-4.208 8.184-7.904 8.394-.112.008-.223.011-.332.011zm3.414-13.746l-.137.004c-1.94.111-3.555 2.304-4.32 5.866-.478 2.228-.381 3.899.272 4.707.297.368.717.555 1.249.555l.14-.004c1.94-.109 3.554-2.301 4.318-5.864.478-2.228.382-3.9-.27-4.708-.296-.369-.718-.556-1.252-.556zm11.591 12.107c-1.439 0-2.637-.539-3.462-1.56-1.163-1.439-1.467-3.651-.878-6.397 1.033-4.813 4.209-8.186 7.903-8.394 1.603-.09 2.903.453 3.79 1.549 1.163 1.439 1.467 3.651.878 6.396-1.031 4.809-4.206 8.183-7.902 8.396-.112.008-.221.01-.329.01zm3.411-13.747l-.136.004c-1.941.111-3.556 2.304-4.32 5.865-.478 2.229-.381 3.901.272 4.708.297.368.719.555 1.251.555l.14-.004c1.939-.109 3.554-2.302 4.318-5.864.479-2.227.383-3.899-.27-4.707-.298-.37-.72-.557-1.255-.557zM11 35.001c-.81 0-1.572-.496-1.873-1.299-.388-1.034.136-2.187 1.17-2.575.337-.126 8.399-3.108 20.536-4.12 1.101-.096 2.067.727 2.159 1.827.092 1.101-.727 2.067-1.827 2.159-11.59.966-19.386 3.851-19.464 3.88-.23.086-.468.128-.701.128zM2.001 29c-.804 0-1.563-.488-1.868-1.283-.396-1.031.118-2.188 1.149-2.583.542-.209 13.516-5.126 32.612-6.131 1.113-.069 2.045.789 2.103 1.892.059 1.103-.789 2.045-1.892 2.103-18.423.97-31.261 5.821-31.389 5.87-.235.089-.477.132-.715.132z"/></svg>',
		'reaction-raised_hands' => /* Raising Hands 🙌 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#FFDC5D" d="M3 26h8v10H3zm22 0h8v10h-8z"/><path fill="#F9CA55" d="M33 28.72s-3 2-8 1v-5h8v4zm-30 0s3 2 8 1v-5H3v4z"/><path fill="#EF9645" d="M3.983 18.604h8v8h-8zm20.023-.5h8v8h-8z"/><path fill="#FFDC5D" d="M.373 11.835S.376 10.61 1.6 10.613c1.226.004 1.222 1.229 1.222 1.229l-.019 5.684c.195-.09.399-.171.613-.241l.025-7.889s.004-1.225 1.227-1.221c1.224.003 1.221 1.229 1.221 1.229l-.021 7.42c.199-.018.404-.032.61-.042l.028-8.602s.003-1.225 1.228-1.22c1.225.004 1.22 1.229 1.22 1.229l-.028 8.6c.21.012.412.033.614.052l.025-8.039s.004-1.225 1.227-1.22c1.224.003 1.219 1.227 1.219 1.227l-.024 8.501-.003.681v.611c-3.674-.009-6.133 3.042-6.144 6.104 0 .612.612.616.612.616.01-3.678 2.467-6.115 6.142-6.104l1.801-4.188s.395-1.159 1.556-.762c1.158.392.765 1.553.765 1.553l-.893 3.105c-.354 1.234-.685 2.476-.859 3.744-.498 3.584-3.581 6.34-7.299 6.33C3.61 28.983.33 25.685.343 21.63c.001-.214.014-.418.034-.61l-.032-.004.028-9.181zm35.244 0s-.003-1.225-1.227-1.222c-1.226.004-1.223 1.229-1.223 1.229l.019 5.684c-.194-.09-.399-.171-.612-.241l-.025-7.889s-.004-1.225-1.227-1.221c-1.225.003-1.222 1.229-1.222 1.229l.022 7.42c-.198-.018-.403-.032-.61-.042l-.027-8.602s-.004-1.225-1.229-1.22c-1.225.004-1.22 1.229-1.22 1.229l.028 8.6c-.21.012-.412.033-.614.052l-.025-8.039s-.005-1.225-1.228-1.22c-1.224.003-1.219 1.227-1.219 1.227l.024 8.501.003.681v.611c3.674-.009 6.133 3.042 6.145 6.104 0 .612-.612.616-.612.616-.011-3.678-2.468-6.115-6.142-6.104l-1.801-4.188s-.394-1.159-1.556-.762c-1.157.392-.765 1.553-.765 1.553l.893 3.105c.354 1.234.685 2.476.859 3.744.498 3.584 3.58 6.34 7.299 6.33 4.055-.017 7.336-3.315 7.322-7.37-.001-.214-.014-.418-.034-.61l.032-.004-.028-9.181z"/><path d="M23.541 6.458c-.213 0-.429-.068-.61-.208-.438-.338-.518-.966-.18-1.403L25.73.992c.335-.436.965-.52 1.402-.18.438.338.518.966.18 1.403L24.333 6.07c-.196.255-.492.388-.792.388zm-11.02 0c-.299 0-.595-.134-.792-.389L8.75 2.215c-.337-.437-.256-1.065.181-1.403.437-.337 1.064-.257 1.403.18l2.979 3.855c.337.437.257 1.065-.18 1.403-.183.141-.398.208-.612.208zM18 6c-.552 0-1-.448-1-1V1c0-.552.448-1 1-1 .553 0 1 .448 1 1v4c0 .552-.447 1-1 1z" fill="#5DADEC"/></svg>',
		'reaction-clap' => /* Clapping Hands 👏 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#EF9645" d="M32.302 24.347c-.695-1.01-.307-2.47-.48-4.082-.178-2.63-1.308-5.178-3.5-7.216l-7.466-6.942s-1.471-1.369-2.841.103c-1.368 1.471.104 2.84.104 2.84l3.154 2.934 2.734 2.542s-.685.736-3.711-2.078l-10.22-9.506s-1.473-1.368-2.842.104c-1.368 1.471.103 2.84.103 2.84l9.664 8.989c-.021-.02-.731.692-.744.68L5.917 5.938s-1.472-1.369-2.841.103c-1.369 1.472.103 2.84.103 2.84L13.52 18.5c.012.012-.654.764-.634.783l-8.92-8.298s-1.472-1.369-2.841.103c-1.369 1.472.103 2.841.103 2.841l9.484 8.82c.087.081-.5.908-.391 1.009l-6.834-6.356s-1.472-1.369-2.841.104c-1.369 1.472.103 2.841.103 2.841L11.896 30.71c1.861 1.731 3.772 2.607 6.076 2.928.469.065 1.069.065 1.315.096.777.098 1.459.374 2.372.934 1.175.72 2.938 1.02 3.951-.063l3.454-3.695 3.189-3.412c1.012-1.082.831-2.016.049-3.151z"/><path d="M1.956 35.026c-.256 0-.512-.098-.707-.293-.391-.391-.391-1.023 0-1.414L4.8 29.77c.391-.391 1.023-.391 1.414 0s.391 1.023 0 1.414l-3.551 3.55c-.195.195-.451.292-.707.292zm6.746.922c-.109 0-.221-.018-.331-.056-.521-.182-.796-.752-.613-1.274l.971-2.773c.182-.521.753-.795 1.274-.614.521.183.796.753.613 1.274l-.971 2.773c-.144.412-.53.67-.943.67zm-7.667-7.667c-.412 0-.798-.257-.943-.667-.184-.521.089-1.092.61-1.276l2.495-.881c.523-.18 1.092.091 1.276.61.184.521-.089 1.092-.61 1.276l-2.495.881c-.111.039-.223.057-.333.057zm29.46-21.767c-.256 0-.512-.098-.707-.293-.391-.391-.391-1.024 0-1.415l3.552-3.55c.391-.39 1.023-.39 1.414 0s.391 1.024 0 1.415l-3.552 3.55c-.195.196-.451.293-.707.293zm-4.164-1.697c-.109 0-.221-.019-.33-.057-.521-.182-.796-.752-.614-1.274l.97-2.773c.183-.521.752-.796 1.274-.614.521.182.796.752.614 1.274l-.97 2.773c-.144.413-.531.671-.944.671zm6.143 5.774c-.412 0-.798-.257-.943-.667-.184-.521.09-1.092.61-1.276l2.494-.881c.522-.185 1.092.09 1.276.61.184.521-.09 1.092-.61 1.276l-2.494.881c-.111.039-.223.057-.333.057z" fill="#FA743E"/><path fill="#FFDB5E" d="M35.39 23.822c-.661-1.032-.224-2.479-.342-4.096-.09-2.634-1.133-5.219-3.255-7.33l-7.228-7.189s-1.424-1.417-2.843.008c-1.417 1.424.008 2.842.008 2.842l3.054 3.039 2.646 2.632s-.71.712-3.639-2.202c-2.931-2.915-9.894-9.845-9.894-9.845s-1.425-1.417-2.843.008c-1.418 1.424.007 2.841.007 2.841l9.356 9.31c-.02-.02-.754.667-.767.654L9.64 4.534s-1.425-1.418-2.843.007c-1.417 1.425.007 2.842.007 2.842l10.011 9.962c.012.012-.68.741-.66.761L7.52 9.513s-1.425-1.417-2.843.008.007 2.843.007 2.843l9.181 9.135c.084.083-.53.891-.425.996l-6.616-6.583s-1.425-1.417-2.843.008.007 2.843.007 2.843l10.79 10.732c1.802 1.793 3.682 2.732 5.974 3.131.467.081 1.067.101 1.311.14.773.124 1.445.423 2.34 1.014 1.15.759 2.902 1.118 3.951.07l3.577-3.576 3.302-3.302c1.049-1.05.9-1.99.157-3.15z"/></svg>',
		'reaction-wow' => /* Face with Open Mouth 😮 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#FFCC4D" d="M36 18c0 9.941-8.059 18-18 18S0 27.941 0 18 8.059 0 18 0s18 8.059 18 18"/><ellipse fill="#664500" cx="18" cy="25" rx="4" ry="5"/><ellipse fill="#664500" cx="12" cy="13.5" rx="2.5" ry="3.5"/><ellipse fill="#664500" cx="24" cy="13.5" rx="2.5" ry="3.5"/></svg>',
		'reaction-crying' => /* Crying Face 😢 */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><path fill="#FFCC4D" d="M36 18c0 9.941-8.059 18-18 18-9.94 0-18-8.059-18-18C0 8.06 8.06 0 18 0c9.941 0 18 8.06 18 18"/><ellipse fill="#664500" cx="11.5" cy="17" rx="2.5" ry="3.5"/><ellipse fill="#664500" cx="24.5" cy="17" rx="2.5" ry="3.5"/><path fill="#664500" d="M5.999 13.5c-.208 0-.419-.065-.599-.2-.442-.331-.531-.958-.2-1.4 3.262-4.35 7.616-4.4 7.8-4.4.552 0 1 .448 1 1 0 .551-.445.998-.996 1-.155.002-3.568.086-6.204 3.6-.196.262-.497.4-.801.4zm24.002 0c-.305 0-.604-.138-.801-.4-2.641-3.521-6.061-3.599-6.206-3.6-.55-.006-.994-.456-.991-1.005.003-.551.447-.995.997-.995.184 0 4.537.05 7.8 4.4.332.442.242 1.069-.2 1.4-.18.135-.39.2-.599.2zm-6.516 14.879C23.474 28.335 22.34 24 18 24s-5.474 4.335-5.485 4.379c-.053.213.044.431.232.544.188.112.433.086.596-.06C13.352 28.855 14.356 28 18 28c3.59 0 4.617.83 4.656.863.095.09.219.137.344.137.084 0 .169-.021.246-.064.196-.112.294-.339.239-.557z"/><path fill="#5DADEC" d="M16 31c0 2.762-2.238 5-5 5s-5-2.238-5-5 4-10 5-10 5 7.238 5 10z"/></svg>',
		);

	if ( array_key_exists( $icon, $arr ) ) {
		$repl = sprintf( 
			'<svg class="svg-icon" width="%d" height="%d" aria-hidden="true" role="img" focusable="false" %s ',
			$size,
			$size,
			$styles
		);
		$svg  = preg_replace( '/^<svg /', $repl, trim( $arr[ $icon ] ) ); // Add extra attributes to SVG code.
		$svg  = preg_replace( "/([\n\t]+)/", ' ', $svg ); // Remove newlines & tabs.
		$svg  = preg_replace( '/>\s*</', '><', $svg );    // Remove whitespace between SVG tags.
		return $svg;
	}

	return null;
}

/**
 * Check if user has an image
 * 
 * @param Number $userid The user's id.
 * @return String id of image or false if no image
 */
function wasmo_user_has_image( $userid ) {
	$userimg = get_field( 'photo', 'user_' . $userid );
	if ( $userimg ) {
		return $userimg;
	}
	return false;
}

/**
 * Get user image url
 * 
 * @param Number $userid the user's id
 * @return String url to image
 */
function wasmo_get_user_image_url( $userid ) {
	$userimg = wasmo_user_has_image( $userid );
	if ( $userimg ) {
		return wp_get_attachment_image_url( $userimg, 'medium' );
	} else {
		$user = get_userdata( $userid );
		$hash = md5( strtolower( trim( $user->user_email ) ) );
		$default_img = urlencode( 'https://raw.githubusercontent.com/circlecube/wasmo-theme/main/img/default.png' );
		$gravatar = $hash . '?s=300&d='.$default_img;
		return "https://www.gravatar.com/avatar/" . $gravatar;
	}
}

/**
 * Get user image
 * 
 * @param Number $userid The user's id.
 * @param Boolean $isItempropImage Flag to determine wether to include itemProp=image (for structured data) (default false).
 * @return String html for image tag
 */
function wasmo_get_user_image( $userid, $isItempropImage = false ) {
	$userimg = wasmo_user_has_image( $userid );
	$user = get_userdata( $userid );
	$alt = $user->display_name . ' profile image for wasmormon.org';

	if ( $userimg ) {
		return wp_get_attachment_image( $userimg, 'medium', false, array(
			'alt' => $alt,
			'itemProp' => $isItempropImage ? 'image' : '',
			'loading' => 'lazy',
		) );
	} else {
		$img_url = wasmo_get_user_image_url( $userid );
		$atts = $isItempropImage ? 'itemProm="image"' : '';
		return '<img src="' . $img_url . '" alt="' . $alt . '" ' . $atts . ' loading="lazy" />';
	}
}
 
/**
 * Get last login time
 * 
 * @return string The last login time.
 */
function wasmo_get_lastlogin() { 
	$last_login = get_the_author_meta('last_login');
	$the_login_date = human_time_diff($last_login);
	return $the_login_date; 
}