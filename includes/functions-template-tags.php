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
		is_user_logged_in() || // logged in or
		!is_single() || // not a single post or
		!is_main_query() || // not the main loop or
		is_embed() // is a post embed
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