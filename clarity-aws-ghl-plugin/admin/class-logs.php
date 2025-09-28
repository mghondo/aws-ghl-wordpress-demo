<?php
/**
 * Logs Management
 *
 * Handles webhook logs display and management
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Logs {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Logs will be initialized when called
    }
    
    /**
     * Render logs page
     */
    public function render() {
        $plugin = clarity_aws_ghl();
        $database = $plugin->database;
        
        // Handle filters
        $filters = array(
            'event_type' => isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '',
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
        );
        
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;
        
        $logs = $database->get_webhook_logs($per_page, $offset, $filters);
        $total_logs = $database->count_webhook_logs($filters);
        $total_pages = ceil($total_logs / $per_page);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Webhook Logs', 'clarity-aws-ghl'); ?></h1>
            
            <!-- Filters -->
            <div class="clarity-logs-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="clarity-aws-ghl-logs" />
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="event_type"><?php _e('Event Type', 'clarity-aws-ghl'); ?></label></th>
                            <td>
                                <select name="event_type" id="event_type">
                                    <option value=""><?php _e('All Events', 'clarity-aws-ghl'); ?></option>
                                    <?php
                                    $event_types = $database->get_event_types();
                                    foreach ($event_types as $type) {
                                        echo '<option value="' . esc_attr($type->event_type) . '" ' . selected($filters['event_type'], $type->event_type, false) . '>' . esc_html($type->event_type) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                            
                            <th><label for="status"><?php _e('Status', 'clarity-aws-ghl'); ?></label></th>
                            <td>
                                <select name="status" id="status">
                                    <option value=""><?php _e('All Statuses', 'clarity-aws-ghl'); ?></option>
                                    <option value="success" <?php selected($filters['status'], 'success'); ?>><?php _e('Success', 'clarity-aws-ghl'); ?></option>
                                    <option value="error" <?php selected($filters['status'], 'error'); ?>><?php _e('Error', 'clarity-aws-ghl'); ?></option>
                                    <option value="pending" <?php selected($filters['status'], 'pending'); ?>><?php _e('Pending', 'clarity-aws-ghl'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="date_from"><?php _e('Date From', 'clarity-aws-ghl'); ?></label></th>
                            <td><input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($filters['date_from']); ?>" /></td>
                            
                            <th><label for="date_to"><?php _e('Date To', 'clarity-aws-ghl'); ?></label></th>
                            <td><input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($filters['date_to']); ?>" /></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Filter Logs', 'clarity-aws-ghl'); ?>" />
                        <a href="<?php echo admin_url('admin.php?page=clarity-aws-ghl-logs'); ?>" class="button"><?php _e('Clear Filters', 'clarity-aws-ghl'); ?></a>
                    </p>
                </form>
            </div>
            
            <!-- Actions -->
            <div class="clarity-logs-actions">
                <button type="button" class="button" id="export-logs"><?php _e('Export Logs', 'clarity-aws-ghl'); ?></button>
                <button type="button" class="button button-secondary" id="clear-all-logs"><?php _e('Clear All Logs', 'clarity-aws-ghl'); ?></button>
            </div>
            
            <!-- Logs Table -->
            <div id="logs-table-container">
                <?php if (!empty($logs)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Event Type', 'clarity-aws-ghl'); ?></th>
                                <th><?php _e('Status', 'clarity-aws-ghl'); ?></th>
                                <th><?php _e('Contact ID', 'clarity-aws-ghl'); ?></th>
                                <th><?php _e('Processing Time', 'clarity-aws-ghl'); ?></th>
                                <th><?php _e('Date', 'clarity-aws-ghl'); ?></th>
                                <th><?php _e('Actions', 'clarity-aws-ghl'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($log->event_type); ?></strong></td>
                                    <td>
                                        <span class="ghl-sync-status status-<?php echo esc_attr($log->status); ?>">
                                            <?php echo esc_html($log->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log->contact_id ?: '-'); ?></td>
                                    <td>
                                        <?php 
                                        if ($log->processing_time_ms) {
                                            echo esc_html($log->processing_time_ms) . 'ms';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small view-log-details" data-log-id="<?php echo $log->id; ?>">
                                            <?php _e('View Details', 'clarity-aws-ghl'); ?>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Hidden details row -->
                                <tr id="log-details-<?php echo $log->id; ?>" class="log-details-row" style="display: none;">
                                    <td colspan="6">
                                        <div class="log-details">
                                            <h4><?php _e('Webhook Payload', 'clarity-aws-ghl'); ?></h4>
                                            <pre style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;"><?php echo esc_html(json_encode(json_decode($log->payload), JSON_PRETTY_PRINT)); ?></pre>
                                            
                                            <?php if ($log->response): ?>
                                                <h4><?php _e('Response', 'clarity-aws-ghl'); ?></h4>
                                                <pre style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;"><?php echo esc_html($log->response); ?></pre>
                                            <?php endif; ?>
                                            
                                            <?php if ($log->error_message): ?>
                                                <h4><?php _e('Error Message', 'clarity-aws-ghl'); ?></h4>
                                                <div style="color: #dc3232; padding: 10px; background: #fef7f7; border: 1px solid #dc3232;">
                                                    <?php echo esc_html($log->error_message); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p>
                                                <strong><?php _e('S3 File:', 'clarity-aws-ghl'); ?></strong>
                                                <?php echo $log->s3_key ? esc_html($log->s3_key) : __('Not uploaded', 'clarity-aws-ghl'); ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    <?php printf(__('%s items', 'clarity-aws-ghl'), number_format($total_logs)); ?>
                                </span>
                                
                                <?php
                                echo paginate_links(array(
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'prev_text' => __('&laquo;'),
                                    'next_text' => __('&raquo;'),
                                    'current' => $page,
                                    'total' => $total_pages,
                                    'add_args' => $filters
                                ));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="notice notice-info">
                        <p><?php _e('No webhook logs found.', 'clarity-aws-ghl'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle log details
            $('.view-log-details').on('click', function() {
                var logId = $(this).data('log-id');
                var detailsRow = $('#log-details-' + logId);
                
                if (detailsRow.is(':visible')) {
                    detailsRow.hide();
                    $(this).text('<?php _e('View Details', 'clarity-aws-ghl'); ?>');
                } else {
                    $('.log-details-row').hide();
                    $('.view-log-details').text('<?php _e('View Details', 'clarity-aws-ghl'); ?>');
                    detailsRow.show();
                    $(this).text('<?php _e('Hide Details', 'clarity-aws-ghl'); ?>');
                }
            });
        });
        </script>
        <?php
    }
}