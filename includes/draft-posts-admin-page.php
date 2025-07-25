<?php

if( is_admin() )
{
    new wasmo_draft_posts_Wp_List_Table();
}

/**
 * wasmo_draft_posts_Wp_List_Table class will create the page to load the table
 */
class wasmo_draft_posts_Wp_List_Table
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'add_menu_draft_posts' ));
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_draft_posts()
    {
        add_submenu_page(
            'wasmormon',
            'Draft Posts by Length',
            'Drafts',
            'manage_options',
            'wasmo-draft-posts',
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
        $wasmo_draft_post_table = new Wasmo_Draft_Post_List_Table();
        $wasmo_draft_post_table->prepare_items();
        ?>
            <div class="wrap">
                <h2>Draft Posts (Sorted by Content Length)</h2>
                <p>Draft posts sorted by content length - longest first. Work on the posts with the most content already written.</p>
                <?php $wasmo_draft_post_table->display(); ?>
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
class Wasmo_Draft_Post_List_Table extends WP_List_Table
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

        $perPage = 50;
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
            'title'       => 'Title',
            'author'      => 'Author',
            'content_length' => 'Content Length',
            'word_count'  => 'Word Count',
            'date'        => 'Date',
            'actions'     => 'Actions'
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
            'title'          => array('title', false),
            'author'         => array('author', false),
            'content_length' => array('content_length', true), // Default sort by content length descending
            'word_count'     => array('word_count', false),
            'date'           => array('date', false)
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
            'post_status' => 'draft',
            'post_type'   => 'post',
            'posts_per_page' => -1, // Get all drafts
            'fields' => 'ids' // Only get IDs for efficiency
        );

        $draft_ids = get_posts( $args );
        
        foreach ( $draft_ids as $post_id ) {
            $post = get_post( $post_id );
            $content = $post->post_content;
            $content_length = strlen( $content );
            $word_count = str_word_count( strip_tags( $content ) );
            
            // Only include posts that actually have content
            if ( $content_length > 10 ) {
                $author_info = get_userdata( $post->post_author );
                
                $data[] = array(
                    'id'             => $post_id,
                    'title'          => !empty($post->post_title) ? $post->post_title : '(No title)',
                    'author'         => $author_info ? $author_info->display_name : 'Unknown',
                    'content_length' => $content_length,
                    'word_count'     => $word_count,
                    'date'           => get_the_date( 'Y-m-d', $post_id ),
                    'actions'        => sprintf(
                        '<a href="%s">Edit</a> | <a href="%s" target="_blank">Preview</a>',
                        get_edit_post_link( $post_id ),
                        get_preview_post_link( $post_id )
                    )
                );
            }
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
            case 'title':
                return '<strong>' . esc_html( $item[ $column_name ] ) . '</strong>';
                
            case 'author':
                return esc_html( $item[ $column_name ] );
                
            case 'content_length':
                return number_format( $item[ $column_name ] ) . ' chars';
                
            case 'word_count':
                return number_format( $item[ $column_name ] ) . ' words';
                
            case 'date':
            case 'actions':
                return $item[ $column_name ];

            default:
                return print_r( $item, true );
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults - sort by content length descending by default
        $orderby = 'content_length';
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

        // Handle numeric sorting for content_length and word_count
        if ( $orderby === 'content_length' || $orderby === 'word_count' ) {
            $result = $a[$orderby] - $b[$orderby];
        } else {
            $result = strcmp( $a[$orderby], $b[$orderby] );
        }

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
?> 