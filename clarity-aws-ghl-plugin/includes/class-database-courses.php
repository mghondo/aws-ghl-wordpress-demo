<?php
/**
 * Database Course Tables Extension
 *
 * Extends database functionality for course management system
 *
 * @package Clarity_AWS_GHL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Clarity_AWS_GHL_Database_Courses {
    
    /**
     * Database version for course tables
     */
    const COURSE_DB_VERSION = '1.0.0';
    
    /**
     * Table names
     */
    private $courses_table;
    private $lessons_table;
    private $user_progress_table;
    private $enrollments_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->courses_table = $wpdb->prefix . 'clarity_courses';
        $this->lessons_table = $wpdb->prefix . 'clarity_lessons';
        $this->user_progress_table = $wpdb->prefix . 'clarity_user_progress';
        $this->enrollments_table = $wpdb->prefix . 'clarity_course_enrollments';
    }
    
    /**
     * Create course database tables
     */
    public function create_course_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Courses table
        $courses_sql = "CREATE TABLE {$this->courses_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_title varchar(255) NOT NULL,
            course_slug varchar(255) NOT NULL,
            course_description text,
            course_tier int(1) NOT NULL DEFAULT 1,
            course_price decimal(10,2) NOT NULL DEFAULT 0.00,
            course_status varchar(20) NOT NULL DEFAULT 'draft',
            course_order int(10) NOT NULL DEFAULT 0,
            total_lessons int(10) NOT NULL DEFAULT 0,
            completion_certificate tinyint(1) NOT NULL DEFAULT 1,
            featured_image varchar(500) DEFAULT NULL,
            access_requirements text,
            meta_data text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY course_slug (course_slug),
            KEY course_tier (course_tier),
            KEY course_status (course_status),
            KEY course_order (course_order)
        ) $charset_collate;";
        
        // Lessons table
        $lessons_sql = "CREATE TABLE {$this->lessons_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_id bigint(20) unsigned NOT NULL,
            lesson_title varchar(255) NOT NULL,
            lesson_slug varchar(255) NOT NULL,
            lesson_description text,
            lesson_content longtext,
            video_url varchar(500) DEFAULT NULL,
            video_type varchar(50) DEFAULT 'youtube',
            lesson_order int(10) NOT NULL DEFAULT 0,
            duration_minutes int(10) DEFAULT NULL,
            is_preview tinyint(1) NOT NULL DEFAULT 0,
            resources text,
            meta_data text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY course_id (course_id),
            KEY lesson_order (lesson_order),
            KEY lesson_slug (lesson_slug),
            KEY is_preview (is_preview)
        ) $charset_collate;";
        
        // User progress table
        $user_progress_sql = "CREATE TABLE {$this->user_progress_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            lesson_id bigint(20) unsigned NOT NULL,
            is_completed tinyint(1) NOT NULL DEFAULT 0,
            completion_date datetime DEFAULT NULL,
            time_spent_seconds int(10) DEFAULT 0,
            last_position_seconds int(10) DEFAULT 0,
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY lesson_id (lesson_id),
            KEY is_completed (is_completed),
            KEY completion_date (completion_date)
        ) $charset_collate;";
        
        // Course enrollments table
        $enrollments_sql = "CREATE TABLE {$this->enrollments_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            enrollment_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completion_date datetime DEFAULT NULL,
            progress_percentage int(3) NOT NULL DEFAULT 0,
            certificate_issued tinyint(1) NOT NULL DEFAULT 0,
            certificate_url varchar(500) DEFAULT NULL,
            access_expires datetime DEFAULT NULL,
            enrollment_status varchar(20) NOT NULL DEFAULT 'active',
            payment_status varchar(20) DEFAULT 'pending',
            payment_amount decimal(10,2) DEFAULT 0.00,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_course (user_id, course_id),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY enrollment_status (enrollment_status),
            KEY completion_date (completion_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($courses_sql);
        dbDelta($lessons_sql);
        dbDelta($user_progress_sql);
        dbDelta($enrollments_sql);
        
        // Update database version
        update_option('clarity_aws_ghl_course_db_version', self::COURSE_DB_VERSION);
        
        // Insert default course tiers if they don't exist
        $this->insert_default_courses();
    }
    
    /**
     * Insert default course structure
     */
    private function insert_default_courses() {
        global $wpdb;
        
        // Check if courses already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$this->courses_table}");
        if ($existing > 0) {
            return;
        }
        
        // Tier 1: Real Estate Foundations
        $tier1_id = $wpdb->insert($this->courses_table, array(
            'course_title' => 'Real Estate Foundations',
            'course_slug' => 'real-estate-foundations',
            'course_description' => 'Start your real estate journey with fundamental concepts taught by Alex Hormozi. Perfect for beginners looking to understand the basics of real estate investing.',
            'course_tier' => 1,
            'course_price' => 0.00,
            'course_status' => 'published',
            'course_order' => 1,
            'total_lessons' => 5,
            'access_requirements' => json_encode(array('registration' => true))
        ));
        
        // Tier 2: Real Estate Mastery Course
        $tier2_id = $wpdb->insert($this->courses_table, array(
            'course_title' => 'Real Estate Mastery Course',
            'course_slug' => 'real-estate-mastery',
            'course_description' => 'Advanced strategies for intermediate real estate investors. Learn proven techniques to maximize your returns and build a sustainable portfolio.',
            'course_tier' => 2,
            'course_price' => 497.00,
            'course_status' => 'published',
            'course_order' => 2,
            'total_lessons' => 5,
            'access_requirements' => json_encode(array('prerequisite' => 'tier1_complete'))
        ));
        
        // Tier 3: Elite Real Estate Empire Builder
        $tier3_id = $wpdb->insert($this->courses_table, array(
            'course_title' => 'Elite Real Estate Empire Builder',
            'course_slug' => 'elite-empire-builder',
            'course_description' => 'Scale your real estate business to 7-figures and beyond. Advanced wealth-building strategies for serious investors ready to build an empire.',
            'course_tier' => 3,
            'course_price' => 1997.00,
            'course_status' => 'published',
            'course_order' => 3,
            'total_lessons' => 5,
            'access_requirements' => json_encode(array('prerequisite' => 'tier2_complete'))
        ));
    }
    
    /**
     * Drop course tables
     */
    public function drop_course_tables() {
        global $wpdb;
        
        $tables = array(
            $this->user_progress_table,
            $this->enrollments_table,
            $this->lessons_table,
            $this->courses_table
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        delete_option('clarity_aws_ghl_course_db_version');
    }
    
    /**
     * Get table names for external access
     */
    public function get_table_names() {
        return array(
            'courses' => $this->courses_table,
            'lessons' => $this->lessons_table,
            'user_progress' => $this->user_progress_table,
            'enrollments' => $this->enrollments_table
        );
    }
}