<?php
/**
 * Custom Post Types for GHL Data
 *
 * Manages WordPress custom post types for GoHighLevel data
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_ghl_contact_posts_columns', array($this, 'contact_columns'));
        add_action('manage_ghl_contact_posts_custom_column', array($this, 'contact_column_content'), 10, 2);
        add_filter('manage_ghl_opportunity_posts_columns', array($this, 'opportunity_columns'));
        add_action('manage_ghl_opportunity_posts_custom_column', array($this, 'opportunity_column_content'), 10, 2);
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // GHL Contacts
        register_post_type('ghl_contact', array(
            'labels' => array(
                'name' => __('GHL Contacts', 'clarity-aws-ghl'),
                'singular_name' => __('GHL Contact', 'clarity-aws-ghl'),
                'menu_name' => __('GHL Contacts', 'clarity-aws-ghl'),
                'add_new' => __('Add New Contact', 'clarity-aws-ghl'),
                'add_new_item' => __('Add New GHL Contact', 'clarity-aws-ghl'),
                'edit_item' => __('Edit GHL Contact', 'clarity-aws-ghl'),
                'new_item' => __('New GHL Contact', 'clarity-aws-ghl'),
                'view_item' => __('View GHL Contact', 'clarity-aws-ghl'),
                'search_items' => __('Search GHL Contacts', 'clarity-aws-ghl'),
                'not_found' => __('No GHL contacts found', 'clarity-aws-ghl'),
                'not_found_in_trash' => __('No GHL contacts found in trash', 'clarity-aws-ghl'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add this to our plugin menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_posts' => 'manage_options',
            ),
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'menu_icon' => 'dashicons-groups',
            'show_in_rest' => false,
        ));
        
        // GHL Opportunities
        register_post_type('ghl_opportunity', array(
            'labels' => array(
                'name' => __('GHL Opportunities', 'clarity-aws-ghl'),
                'singular_name' => __('GHL Opportunity', 'clarity-aws-ghl'),
                'menu_name' => __('GHL Opportunities', 'clarity-aws-ghl'),
                'add_new' => __('Add New Opportunity', 'clarity-aws-ghl'),
                'add_new_item' => __('Add New GHL Opportunity', 'clarity-aws-ghl'),
                'edit_item' => __('Edit GHL Opportunity', 'clarity-aws-ghl'),
                'new_item' => __('New GHL Opportunity', 'clarity-aws-ghl'),
                'view_item' => __('View GHL Opportunity', 'clarity-aws-ghl'),
                'search_items' => __('Search GHL Opportunities', 'clarity-aws-ghl'),
                'not_found' => __('No GHL opportunities found', 'clarity-aws-ghl'),
                'not_found_in_trash' => __('No GHL opportunities found in trash', 'clarity-aws-ghl'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false, // We'll add this to our plugin menu
            'show_in_admin_bar' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_posts' => 'manage_options',
            ),
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'menu_icon' => 'dashicons-chart-line',
            'show_in_rest' => false,
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Contact Sources
        register_taxonomy('ghl_contact_source', 'ghl_contact', array(
            'labels' => array(
                'name' => __('Contact Sources', 'clarity-aws-ghl'),
                'singular_name' => __('Contact Source', 'clarity-aws-ghl'),
                'menu_name' => __('Sources', 'clarity-aws-ghl'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
        
        // Contact Tags
        register_taxonomy('ghl_contact_tag', 'ghl_contact', array(
            'labels' => array(
                'name' => __('Contact Tags', 'clarity-aws-ghl'),
                'singular_name' => __('Contact Tag', 'clarity-aws-ghl'),
                'menu_name' => __('Tags', 'clarity-aws-ghl'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
        
        // Opportunity Stages
        register_taxonomy('ghl_opportunity_stage', 'ghl_opportunity', array(
            'labels' => array(
                'name' => __('Opportunity Stages', 'clarity-aws-ghl'),
                'singular_name' => __('Opportunity Stage', 'clarity-aws-ghl'),
                'menu_name' => __('Stages', 'clarity-aws-ghl'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'rewrite' => false,
            'query_var' => false,
        ));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Contact meta boxes
        add_meta_box(
            'ghl_contact_details',
            __('Contact Details', 'clarity-aws-ghl'),
            array($this, 'contact_details_meta_box'),
            'ghl_contact',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ghl_contact_sync',
            __('Sync Information', 'clarity-aws-ghl'),
            array($this, 'contact_sync_meta_box'),
            'ghl_contact',
            'side',
            'default'
        );
        
        // Opportunity meta boxes
        add_meta_box(
            'ghl_opportunity_details',
            __('Opportunity Details', 'clarity-aws-ghl'),
            array($this, 'opportunity_details_meta_box'),
            'ghl_opportunity',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ghl_opportunity_sync',
            __('Sync Information', 'clarity-aws-ghl'),
            array($this, 'opportunity_sync_meta_box'),
            'ghl_opportunity',
            'side',
            'default'
        );
    }
    
    /**
     * Contact details meta box
     */
    public function contact_details_meta_box($post) {
        wp_nonce_field('ghl_contact_meta_nonce', 'ghl_contact_meta_nonce');
        
        $ghl_id = get_post_meta($post->ID, '_ghl_contact_id', true);
        $first_name = get_post_meta($post->ID, '_ghl_first_name', true);
        $last_name = get_post_meta($post->ID, '_ghl_last_name', true);
        $email = get_post_meta($post->ID, '_ghl_email', true);
        $phone = get_post_meta($post->ID, '_ghl_phone', true);
        $custom_fields = get_post_meta($post->ID, '_ghl_custom_fields', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ghl_contact_id"><?php _e('GHL Contact ID', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_contact_id" name="ghl_contact_id" value="<?php echo esc_attr($ghl_id); ?>" class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_first_name"><?php _e('First Name', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_first_name" name="ghl_first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_last_name"><?php _e('Last Name', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_last_name" name="ghl_last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_email"><?php _e('Email', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="email" id="ghl_email" name="ghl_email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_phone"><?php _e('Phone', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_phone" name="ghl_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_custom_fields"><?php _e('Custom Fields', 'clarity-aws-ghl'); ?></label></th>
                <td>
                    <textarea id="ghl_custom_fields" name="ghl_custom_fields" rows="5" class="large-text"><?php echo esc_textarea($custom_fields); ?></textarea>
                    <p class="description"><?php _e('JSON format for custom fields from GoHighLevel', 'clarity-aws-ghl'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Contact sync meta box
     */
    public function contact_sync_meta_box($post) {
        $last_sync = get_post_meta($post->ID, '_ghl_last_sync', true);
        $sync_status = get_post_meta($post->ID, '_ghl_sync_status', true);
        $wp_user_id = get_post_meta($post->ID, '_ghl_wp_user_id', true);
        
        ?>
        <p><strong><?php _e('Last Sync:', 'clarity-aws-ghl'); ?></strong><br>
        <?php echo $last_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync))) : __('Never', 'clarity-aws-ghl'); ?></p>
        
        <p><strong><?php _e('Sync Status:', 'clarity-aws-ghl'); ?></strong><br>
        <span class="ghl-sync-status status-<?php echo esc_attr($sync_status ?: 'unknown'); ?>">
            <?php echo esc_html($sync_status ?: __('Unknown', 'clarity-aws-ghl')); ?>
        </span></p>
        
        <?php if ($wp_user_id): ?>
        <p><strong><?php _e('WordPress User:', 'clarity-aws-ghl'); ?></strong><br>
        <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $wp_user_id)); ?>">
            <?php echo esc_html(get_userdata($wp_user_id)->display_name); ?>
        </a></p>
        <?php endif; ?>
        
        <p>
            <button type="button" class="button" id="sync-contact-now" data-contact-id="<?php echo esc_attr($post->ID); ?>">
                <?php _e('Sync Now', 'clarity-aws-ghl'); ?>
            </button>
        </p>
        <?php
    }
    
    /**
     * Opportunity details meta box
     */
    public function opportunity_details_meta_box($post) {
        wp_nonce_field('ghl_opportunity_meta_nonce', 'ghl_opportunity_meta_nonce');
        
        $ghl_id = get_post_meta($post->ID, '_ghl_opportunity_id', true);
        $contact_id = get_post_meta($post->ID, '_ghl_contact_id', true);
        $value = get_post_meta($post->ID, '_ghl_value', true);
        $currency = get_post_meta($post->ID, '_ghl_currency', true);
        $source = get_post_meta($post->ID, '_ghl_source', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ghl_opportunity_id"><?php _e('GHL Opportunity ID', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_opportunity_id" name="ghl_opportunity_id" value="<?php echo esc_attr($ghl_id); ?>" class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_contact_id"><?php _e('GHL Contact ID', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_contact_id" name="ghl_contact_id" value="<?php echo esc_attr($contact_id); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_value"><?php _e('Value', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="number" id="ghl_value" name="ghl_value" value="<?php echo esc_attr($value); ?>" class="regular-text" step="0.01" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_currency"><?php _e('Currency', 'clarity-aws-ghl'); ?></label></th>
                <td>
                    <select id="ghl_currency" name="ghl_currency">
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                        <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                        <option value="CAD" <?php selected($currency, 'CAD'); ?>>CAD</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ghl_source"><?php _e('Source', 'clarity-aws-ghl'); ?></label></th>
                <td><input type="text" id="ghl_source" name="ghl_source" value="<?php echo esc_attr($source); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Opportunity sync meta box
     */
    public function opportunity_sync_meta_box($post) {
        $last_sync = get_post_meta($post->ID, '_ghl_last_sync', true);
        $sync_status = get_post_meta($post->ID, '_ghl_sync_status', true);
        
        ?>
        <p><strong><?php _e('Last Sync:', 'clarity-aws-ghl'); ?></strong><br>
        <?php echo $last_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync))) : __('Never', 'clarity-aws-ghl'); ?></p>
        
        <p><strong><?php _e('Sync Status:', 'clarity-aws-ghl'); ?></strong><br>
        <span class="ghl-sync-status status-<?php echo esc_attr($sync_status ?: 'unknown'); ?>">
            <?php echo esc_html($sync_status ?: __('Unknown', 'clarity-aws-ghl')); ?>
        </span></p>
        
        <p>
            <button type="button" class="button" id="sync-opportunity-now" data-opportunity-id="<?php echo esc_attr($post->ID); ?>">
                <?php _e('Sync Now', 'clarity-aws-ghl'); ?>
            </button>
        </p>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        // Save contact meta
        if ($post_type === 'ghl_contact' && isset($_POST['ghl_contact_meta_nonce']) && wp_verify_nonce($_POST['ghl_contact_meta_nonce'], 'ghl_contact_meta_nonce')) {
            $fields = array(
                'ghl_contact_id' => '_ghl_contact_id',
                'ghl_first_name' => '_ghl_first_name',
                'ghl_last_name' => '_ghl_last_name',
                'ghl_email' => '_ghl_email',
                'ghl_phone' => '_ghl_phone',
                'ghl_custom_fields' => '_ghl_custom_fields',
            );
            
            foreach ($fields as $field => $meta_key) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
                }
            }
        }
        
        // Save opportunity meta
        if ($post_type === 'ghl_opportunity' && isset($_POST['ghl_opportunity_meta_nonce']) && wp_verify_nonce($_POST['ghl_opportunity_meta_nonce'], 'ghl_opportunity_meta_nonce')) {
            $fields = array(
                'ghl_opportunity_id' => '_ghl_opportunity_id',
                'ghl_contact_id' => '_ghl_contact_id',
                'ghl_value' => '_ghl_value',
                'ghl_currency' => '_ghl_currency',
                'ghl_source' => '_ghl_source',
            );
            
            foreach ($fields as $field => $meta_key) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }
    
    /**
     * Contact columns
     */
    public function contact_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['ghl_email'] = __('Email', 'clarity-aws-ghl');
        $new_columns['ghl_phone'] = __('Phone', 'clarity-aws-ghl');
        $new_columns['ghl_contact_source'] = $columns['taxonomy-ghl_contact_source'];
        $new_columns['ghl_sync_status'] = __('Sync Status', 'clarity-aws-ghl');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Contact column content
     */
    public function contact_column_content($column, $post_id) {
        switch ($column) {
            case 'ghl_email':
                echo esc_html(get_post_meta($post_id, '_ghl_email', true));
                break;
            case 'ghl_phone':
                echo esc_html(get_post_meta($post_id, '_ghl_phone', true));
                break;
            case 'ghl_sync_status':
                $status = get_post_meta($post_id, '_ghl_sync_status', true);
                echo '<span class="ghl-sync-status status-' . esc_attr($status ?: 'unknown') . '">';
                echo esc_html($status ?: __('Unknown', 'clarity-aws-ghl'));
                echo '</span>';
                break;
        }
    }
    
    /**
     * Opportunity columns
     */
    public function opportunity_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['ghl_value'] = __('Value', 'clarity-aws-ghl');
        $new_columns['ghl_opportunity_stage'] = $columns['taxonomy-ghl_opportunity_stage'];
        $new_columns['ghl_sync_status'] = __('Sync Status', 'clarity-aws-ghl');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Opportunity column content
     */
    public function opportunity_column_content($column, $post_id) {
        switch ($column) {
            case 'ghl_value':
                $value = get_post_meta($post_id, '_ghl_value', true);
                $currency = get_post_meta($post_id, '_ghl_currency', true) ?: 'USD';
                if ($value) {
                    echo esc_html($currency . ' ' . number_format($value, 2));
                }
                break;
            case 'ghl_sync_status':
                $status = get_post_meta($post_id, '_ghl_sync_status', true);
                echo '<span class="ghl-sync-status status-' . esc_attr($status ?: 'unknown') . '">';
                echo esc_html($status ?: __('Unknown', 'clarity-aws-ghl'));
                echo '</span>';
                break;
        }
    }
    
    /**
     * Create contact from GHL data
     */
    public function create_contact_from_ghl($ghl_contact_data) {
        $title = sprintf(
            '%s %s',
            $ghl_contact_data['firstName'] ?? '',
            $ghl_contact_data['lastName'] ?? ''
        );
        
        $post_data = array(
            'post_title' => trim($title) ?: __('Unnamed Contact', 'clarity-aws-ghl'),
            'post_type' => 'ghl_contact',
            'post_status' => 'publish',
            'post_content' => '',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Save meta data
            update_post_meta($post_id, '_ghl_contact_id', $ghl_contact_data['id']);
            update_post_meta($post_id, '_ghl_first_name', $ghl_contact_data['firstName'] ?? '');
            update_post_meta($post_id, '_ghl_last_name', $ghl_contact_data['lastName'] ?? '');
            update_post_meta($post_id, '_ghl_email', $ghl_contact_data['email'] ?? '');
            update_post_meta($post_id, '_ghl_phone', $ghl_contact_data['phone'] ?? '');
            update_post_meta($post_id, '_ghl_custom_fields', wp_json_encode($ghl_contact_data['customFields'] ?? array()));
            update_post_meta($post_id, '_ghl_last_sync', current_time('mysql'));
            update_post_meta($post_id, '_ghl_sync_status', 'synced');
            
            // Set taxonomies
            if (!empty($ghl_contact_data['source'])) {
                wp_set_object_terms($post_id, $ghl_contact_data['source'], 'ghl_contact_source');
            }
            
            if (!empty($ghl_contact_data['tags'])) {
                wp_set_object_terms($post_id, $ghl_contact_data['tags'], 'ghl_contact_tag');
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Create opportunity from GHL data
     */
    public function create_opportunity_from_ghl($ghl_opportunity_data) {
        $post_data = array(
            'post_title' => $ghl_opportunity_data['name'] ?? __('Unnamed Opportunity', 'clarity-aws-ghl'),
            'post_type' => 'ghl_opportunity',
            'post_status' => 'publish',
            'post_content' => '',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Save meta data
            update_post_meta($post_id, '_ghl_opportunity_id', $ghl_opportunity_data['id']);
            update_post_meta($post_id, '_ghl_contact_id', $ghl_opportunity_data['contactId'] ?? '');
            update_post_meta($post_id, '_ghl_value', $ghl_opportunity_data['value'] ?? 0);
            update_post_meta($post_id, '_ghl_currency', $ghl_opportunity_data['currency'] ?? 'USD');
            update_post_meta($post_id, '_ghl_source', $ghl_opportunity_data['source'] ?? '');
            update_post_meta($post_id, '_ghl_last_sync', current_time('mysql'));
            update_post_meta($post_id, '_ghl_sync_status', 'synced');
            
            // Set stage taxonomy
            if (!empty($ghl_opportunity_data['stage'])) {
                wp_set_object_terms($post_id, $ghl_opportunity_data['stage'], 'ghl_opportunity_stage');
            }
            
            return $post_id;
        }
        
        return false;
    }
}