<?php

if( is_admin() )
{
    new wasmo_spotlight_posts_Wp_List_Table();
}

/**
 * wasmo_spotlight_posts_Wp_List_Table class will create the page to load the table
 */
class wasmo_spotlight_posts_Wp_List_Table
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'add_menu_spotlight_posts' ));
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_spotlight_posts()
    {
        add_submenu_page(
            'wasmormon',
            'Spotlights',
            'Spotlight List',
            'manage_options',
            'wasmormon',
            array($this, 'list_table_page')
        );
    }

    /**
     * Display the list table page
     *
     * @return Void
     */
    public function list_table_page()
    {
        $wasmo_spotlight_posts_table = new Wasmo_Spotlight_Post_List_Table();
        $wasmo_spotlight_posts_table->prepare_items();
        ?>
            <div class="wrap">
                <h2>Users Ready for a Spotlight Post</h2>
                <?php $wasmo_spotlight_posts_table->display(); ?>
            </div>
        <?php
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Wasmo_Spotlight_Post_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 100;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            // 'id'         => 'ID',
            // 'public'     => 'Visibility',
            // 'contribute' => 'Wants to Contribute',
            'image'      => 'Photo',
            'username'   => 'Username',
            'display'    => 'Display Name',
            'rdate'      => 'Registered',
            'ldate'      => 'Last Login',
            'sdate'      => 'Last Save',
            'saves'      => 'Saves',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array(
            'id'         => array('id', false),
            'username'   => array('username', false),
            'display'    => array('display', false),
            'rdate'      => array('rdate', false),
            'ldate'      => array('ldate', false),
            'sdate'      => array('sdate', false),
            'pubilc'     => array('pubilc', false),
            'contribute' => array('contribute', false),
            'saves'      => array('saves', false),
            'image'      => array('image', false),
        );
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();

        $args = array(
            'orderby'  => 'meta_value',
            'meta_key' => 'last_save',
            'order'    => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'spotlight_post',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'in_directory',
                    'value' => 'true',
                    'compare' => '='
                ),
                array(
                    'key' => 'hi',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => 'tagline',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => 'about_me',
                    'value' => '',
                    'compare' => '!=',
                ),
                array(
                    'key' => 'photo',
                    'value' => '',
                    'compare' => '!=',
                ),
                array(
                    'key' => 'import_text',
                    'compare' => 'NOT EXISTS'
                ),
            ),
            'fields'   => 'all'
        );
    
        // Array of WP_User objects.
        $users = get_users( $args );

        foreach ( $users as $user) {
            $userid = $user->ID;
            
            // don't include user 1
            if ( $user->ID === 1) { continue; }

            // get data
            $last_login = date('Y-m-d H:i:s', intval( get_user_meta( $userid, 'last_login', true ) ) );
            $last_save = date('Y-m-d H:i:s', intval( get_user_meta( $userid, 'last_save', true ) ) );
            $save_count = intval( get_user_meta( $userid, 'save_count', true ) );
            $in_directory = get_user_meta( $userid, 'in_directory', true );
            $i_want_to_write_posts = get_user_meta( $userid, 'i_want_to_write_posts', true );
            $rdate = date( 'Y-m-d H:i:s', strtotime( get_userdata( $userid )->user_registered ) );
            $view_edit = '<br><a href="' . get_author_posts_url( $userid ) . '">View</a> | <a href="' . get_edit_user_link( $userid ) . '">Edit</a>';
            
            $data[] = array(
                'id'         => $userid,
                'image'      => '<img width="100" height="100" src="' . wasmo_get_user_image_url( $userid ) . '" style="object-fit: cover;" />',
                'username'   => get_the_author_meta( 'user_nicename', $userid ) . $view_edit,
                'display'    => get_the_author_meta( 'display_name', $userid ) . $view_edit,
                'rdate'      => $rdate === '1970-01-01 00:00:00' ? '' : $rdate,
                'ldate'      => $last_login === '1970-01-01 00:00:00' ? '' : $last_login,
                'sdate'      => $last_save === '1970-01-01 00:00:00' ? '' : $last_save,
                'public'     => $in_directory,
                'contribute' => $i_want_to_write_posts,
                'saves'      => $save_count,
            );
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'image':
            case 'username':
            case 'display':
            case 'rdate':
            case 'ldate':
            case 'sdate':
            case 'saves':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'sdate';
        $order = 'desc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
?>