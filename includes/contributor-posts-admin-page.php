<?php

if( is_admin() )
{
    new wasmo_contributor_posts_Wp_List_Table();
}

/**
 * wasmo_contributor_posts_Wp_List_Table class will create the page to load the table
 */
class wasmo_contributor_posts_Wp_List_Table
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'add_menu_contributor_posts' ));
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_contributor_posts()
    {
        add_submenu_page(
            'wasmormon',
            'Contributor Posts',
            'Contributor Posts',
            'manage_options',
            'wasmo-contributor-posts',
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
        $wasmo_contributor_post_table = new Wasmo_Contributor_Post_List_Table();
        $wasmo_contributor_post_table->prepare_items();
        ?>
            <div class="wrap">
                <h2>Contributor Posts</h2>
                <?php $wasmo_contributor_post_table->display(); ?>
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
class Wasmo_Contributor_Post_List_Table extends WP_List_Table
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
            'author' => 'Author',
            'title'  => 'Title',
            // 'id'     => 'ID',
            'date'   => 'Date',
            'status' => 'Status',
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
            'id'     => array('id', false),
            'title'  => array('title', false),
            'author' => array('author', false),
            'date'   => array('date', false),
            'status' => array('status', false)
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
            'author__not_in' => array( 1 ),
            'post_status' => 'any',
        );

        $posts_query = new WP_Query( $args );
        
        if ( $posts_query->have_posts() ):
            //loop
            while ($posts_query->have_posts()): $posts_query->the_post();
                $data[] = array(
                    'id'          => '<a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_ID() . '</a>',
                    'title'       => get_the_title() . '<br>
<a href="' . get_the_permalink() . '">View</a> | <a href="' . get_edit_post_link(get_the_ID()) . '">Edit</a>',
                    'author'      => get_the_author_meta( 'display_name' )  . '<br>
<a href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '">View</a> | <a href="' . get_edit_user_link( get_the_author_meta( 'ID' ) ) . '">Edit</a>',
                    'date'        => get_the_date( 'Y-m-d' ),
                    'status'      => get_post_status(),
                );

            endwhile;
        
            wp_reset_postdata();
        
        endif;

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
            case 'id':
            case 'title':
            case 'author':
            case 'date':
            case 'status':
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
        $orderby = 'title';
        $order = 'asc';

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