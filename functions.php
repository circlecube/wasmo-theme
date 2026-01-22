<?php

require __DIR__ . '/vendor/autoload.php';

require_once( get_stylesheet_directory() . '/includes/wasmo-directory-widget.php' );
require_once( get_stylesheet_directory() . '/includes/wasmo-posts-widget.php' );
require_once( get_stylesheet_directory() . '/includes/updates.php' );
require_once( get_stylesheet_directory() . '/includes/functions-admin.php' );
require_once( get_stylesheet_directory() . '/includes/functions-acf.php' );
require_once( get_stylesheet_directory() . '/includes/functions-email.php' );
require_once( get_stylesheet_directory() . '/includes/functions-login.php' );
require_once( get_stylesheet_directory() . '/includes/functions-template-tags.php' );
require_once( get_stylesheet_directory() . '/includes/functions-theme.php' );
require_once( get_stylesheet_directory() . '/includes/functions-seo.php' );
require_once( get_stylesheet_directory() . '/includes/functions-saints.php' );
require_once( get_stylesheet_directory() . '/includes/functions-profile-interactions.php' );

require_once( get_stylesheet_directory() . '/includes/spotlight-posts-admin-page.php' );
require_once( get_stylesheet_directory() . '/includes/contributor-users-admin-page.php' );
require_once( get_stylesheet_directory() . '/includes/contributor-posts-admin-page.php' );
require_once( get_stylesheet_directory() . '/includes/draft-posts-admin-page.php' );
require_once( get_stylesheet_directory() . '/includes/saints-import.php' );
require_once( get_stylesheet_directory() . '/includes/taxonomy-import.php' );
require_once( get_stylesheet_directory() . '/includes/media-auto-tag.php' );
require_once( get_stylesheet_directory() . '/includes/saints-associations.php' );
require_once( get_stylesheet_directory() . '/includes/saints-images.php' );
require_once( get_stylesheet_directory() . '/includes/saints-settings.php' );

// Load custom blocks
require_once( get_stylesheet_directory() . '/blocks/index.php' );

/*

# Custom user hook summary/flow

## Register
- add custom has_received_welcome and set to false
- send password create email

## Login
- update Last login field
- check if has_received_welcome
- send welcome? 
- update has_received_welcome
 
## Profile Save/Update
- update user nicename/displayname from acf fields
- resave values back to user acf fields
- clear directory transients
- update question counts if user includes any
- update last_save timestamp for this user
- increment save_count
- admin notify email
- redirect user to their own profile

*/
