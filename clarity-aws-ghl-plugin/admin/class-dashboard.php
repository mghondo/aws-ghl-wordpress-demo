<?php
/**
 * Admin Dashboard
 *
 * Main dashboard for the plugin showing system overview and stats
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Dashboard will be initialized when called
    }
    
    /**
     * Render dashboard page
     */
    public function render() {
        $plugin = clarity_aws_ghl();
        $database = $plugin->database;
        $stats = $database->get_stats();
        $plugin_info = $plugin->get_plugin_info();
        
        ?>
        <div class="wrap">
            <h1><?php _e('AWS GoHighLevel Integration Dashboard', 'clarity-aws-ghl'); ?></h1>
            
            <?php $this->render_status_cards($plugin_info, $stats); ?>
            
            <div class="clarity-dashboard-grid">
                <div class="clarity-dashboard-left">
                    <?php $this->render_recent_activity($database); ?>
                    <?php $this->render_webhook_stats($stats); ?>
                </div>
                
                <div class="clarity-dashboard-right">
                    <?php $this->render_quick_actions($plugin_info); ?>
                    <?php $this->render_system_info($plugin_info); ?>
                </div>
            </div>
        </div>
        
        <style>
        .clarity-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .clarity-status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .clarity-status-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .clarity-status-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .clarity-status-card .status-value {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .clarity-status-card .status-description {
            color: #666;
            font-size: 13px;
        }
        
        .status-success { color: #46b450; }
        .status-warning { color: #ffb900; }
        .status-error { color: #dc3232; }
        .status-info { color: #0073aa; }
        
        .clarity-dashboard-widget {
            background: #fff;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .clarity-dashboard-widget h3 {
            margin: 0;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
        }
        
        .clarity-dashboard-widget .widget-content {
            padding: 20px;
        }
        
        .clarity-quick-actions .button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .clarity-activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .clarity-activity-item:last-child {
            border-bottom: none;
        }
        
        .clarity-activity-time {
            color: #666;
            font-size: 12px;
        }
        
        .clarity-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .clarity-stat-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .clarity-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .clarity-stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Render status cards
     */
    private function render_status_cards($plugin_info, $stats) {
        ?>
        <div class="clarity-status-cards">
            <!-- S3 Status -->
            <div class="clarity-status-card">
                <h3><?php _e('AWS S3 Storage', 'clarity-aws-ghl'); ?></h3>
                <div class="status-value <?php echo $plugin_info['s3_status']['connected'] ? 'status-success' : 'status-error'; ?>">
                    <?php echo $plugin_info['s3_status']['connected'] ? __('Connected', 'clarity-aws-ghl') : __('Disconnected', 'clarity-aws-ghl'); ?>
                </div>
                <div class="status-description">
                    <?php 
                    if ($plugin_info['s3_status']['connected']) {
                        printf(__('Bucket: %s', 'clarity-aws-ghl'), esc_html($plugin_info['s3_status']['bucket']));
                    } else {
                        _e('Configuration required', 'clarity-aws-ghl');
                    }
                    ?>
                </div>
            </div>
            
            <!-- Webhook Status -->
            <div class="clarity-status-card">
                <h3><?php _e('Webhook Endpoint', 'clarity-aws-ghl'); ?></h3>
                <div class="status-value status-success">
                    <?php _e('Active', 'clarity-aws-ghl'); ?>
                </div>
                <div class="status-description">
                    <?php printf(__('%d webhooks today', 'clarity-aws-ghl'), $stats['todays_webhooks']); ?>
                </div>
            </div>
            
            <!-- Total Webhooks -->
            <div class="clarity-status-card">
                <h3><?php _e('Total Webhooks', 'clarity-aws-ghl'); ?></h3>
                <div class="status-value status-info">
                    <?php echo number_format($stats['total_webhooks']); ?>
                </div>
                <div class="status-description">
                    <?php printf(__('%d successful, %d failed', 'clarity-aws-ghl'), $stats['successful_webhooks'], $stats['failed_webhooks']); ?>
                </div>
            </div>
            
            <!-- GHL Contacts -->
            <div class="clarity-status-card">
                <h3><?php _e('GHL Contacts', 'clarity-aws-ghl'); ?></h3>
                <div class="status-value status-info">
                    <?php echo number_format($stats['total_contacts']); ?>
                </div>
                <div class="status-description">
                    <?php printf(__('%d new this week', 'clarity-aws-ghl'), $stats['recent_contacts']); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity($database) {
        $recent_logs = $database->get_webhook_logs(10);
        
        ?>
        <div class="clarity-dashboard-widget">
            <h3><?php _e('Recent Webhook Activity', 'clarity-aws-ghl'); ?></h3>
            <div class="widget-content">
                <?php if (!empty($recent_logs)): ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="clarity-activity-item">
                            <div class="clarity-activity-title">
                                <strong><?php echo esc_html($log->event_type); ?></strong>
                                <span class="status-<?php echo esc_attr($log->status); ?>">
                                    (<?php echo esc_html($log->status); ?>)
                                </span>
                            </div>
                            <div class="clarity-activity-time">
                                <?php echo esc_html(human_time_diff(strtotime($log->created_at), current_time('timestamp'))) . ' ' . __('ago', 'clarity-aws-ghl'); ?>
                                <?php if ($log->processing_time_ms): ?>
                                    - <?php printf(__('%dms', 'clarity-aws-ghl'), $log->processing_time_ms); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <p style="text-align: center; margin-top: 15px;">
                        <a href="<?php echo admin_url('admin.php?page=clarity-aws-ghl-logs'); ?>" class="button">
                            <?php _e('View All Logs', 'clarity-aws-ghl'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p><?php _e('No webhook activity yet.', 'clarity-aws-ghl'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render webhook statistics
     */
    private function render_webhook_stats($stats) {
        ?>
        <div class="clarity-dashboard-widget">
            <h3><?php _e('Webhook Statistics', 'clarity-aws-ghl'); ?></h3>
            <div class="widget-content">
                <div class="clarity-stats-grid">
                    <div class="clarity-stat-item">
                        <div class="clarity-stat-number"><?php echo number_format($stats['total_webhooks']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Total Webhooks', 'clarity-aws-ghl'); ?></div>
                    </div>
                    
                    <div class="clarity-stat-item">
                        <div class="clarity-stat-number"><?php echo number_format($stats['todays_webhooks']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Today', 'clarity-aws-ghl'); ?></div>
                    </div>
                    
                    <div class="clarity-stat-item">
                        <div class="clarity-stat-number"><?php echo number_format($stats['successful_webhooks']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Successful', 'clarity-aws-ghl'); ?></div>
                    </div>
                    
                    <div class="clarity-stat-item">
                        <div class="clarity-stat-number"><?php echo number_format($stats['failed_webhooks']); ?></div>
                        <div class="clarity-stat-label"><?php _e('Failed', 'clarity-aws-ghl'); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($stats['event_types'])): ?>
                    <h4 style="margin: 20px 0 10px 0;"><?php _e('Event Types', 'clarity-aws-ghl'); ?></h4>
                    <?php foreach ($stats['event_types'] as $event): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo esc_html($event->event_type); ?></span>
                            <span><strong><?php echo number_format($event->count); ?></strong></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render quick actions
     */
    private function render_quick_actions($plugin_info) {
        ?>
        <div class="clarity-dashboard-widget">
            <h3><?php _e('Quick Actions', 'clarity-aws-ghl'); ?></h3>
            <div class="widget-content clarity-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=clarity-aws-ghl-s3'); ?>" class="button button-primary">
                    <?php _e('Configure S3 Settings', 'clarity-aws-ghl'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=clarity-aws-ghl-ghl'); ?>" class="button button-primary">
                    <?php _e('Configure GHL Settings', 'clarity-aws-ghl'); ?>
                </a>
                
                <button type="button" class="button" id="test-s3-connection">
                    <?php _e('Test S3 Connection', 'clarity-aws-ghl'); ?>
                </button>
                
                <button type="button" class="button" id="test-webhook-endpoint">
                    <?php _e('Test Webhook Endpoint', 'clarity-aws-ghl'); ?>
                </button>
                
                <a href="<?php echo admin_url('edit.php?post_type=ghl_contact'); ?>" class="button">
                    <?php _e('View GHL Contacts', 'clarity-aws-ghl'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=clarity-aws-ghl-logs'); ?>" class="button">
                    <?php _e('View Webhook Logs', 'clarity-aws-ghl'); ?>
                </a>
                
                <button type="button" class="button button-secondary" id="clear-all-logs" style="margin-top: 10px;">
                    <?php _e('Clear All Logs', 'clarity-aws-ghl'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render system information
     */
    private function render_system_info($plugin_info) {
        ?>
        <div class="clarity-dashboard-widget">
            <h3><?php _e('System Information', 'clarity-aws-ghl'); ?></h3>
            <div class="widget-content">
                <table class="widefat" style="border: none;">
                    <tr>
                        <td><strong><?php _e('Plugin Version:', 'clarity-aws-ghl'); ?></strong></td>
                        <td><?php echo esc_html($plugin_info['version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WordPress Version:', 'clarity-aws-ghl'); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('PHP Version:', 'clarity-aws-ghl'); ?></strong></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Configuration Status:', 'clarity-aws-ghl'); ?></strong></td>
                        <td>
                            <span class="<?php echo $plugin_info['is_configured'] ? 'status-success' : 'status-warning'; ?>">
                                <?php echo $plugin_info['is_configured'] ? __('Complete', 'clarity-aws-ghl') : __('Incomplete', 'clarity-aws-ghl'); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Webhook URL:', 'clarity-aws-ghl'); ?></strong></td>
                        <td>
                            <input type="text" value="<?php echo esc_attr($plugin_info['webhook_url']); ?>" readonly class="regular-text" onclick="this.select();" />
                            <p class="description"><?php _e('Use this URL in your GoHighLevel webhook configuration', 'clarity-aws-ghl'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
}