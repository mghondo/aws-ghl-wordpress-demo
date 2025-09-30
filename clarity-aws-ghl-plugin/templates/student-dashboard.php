<?php
/**
 * Student Dashboard Template
 * 
 * Main dashboard for student course access and progress tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_manager = new Clarity_AWS_GHL_User_Manager();
$enrolled_courses = $user_manager->get_user_enrolled_courses($current_user->ID);
$access_level = $user_manager->get_user_access_level($current_user->ID);
$access_tier_name = $user_manager->access_levels[$access_level] ?? 'Free';

wp_enqueue_script('jquery');
?>

<div class="clarity-dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="user-welcome">
            <h1>Welcome back, <?php echo esc_html($current_user->first_name); ?>!</h1>
            <p>Continue your learning journey with our comprehensive course platform</p>
        </div>
        <div class="user-info">
            <div class="access-level-badge access-level-<?php echo esc_attr($access_level); ?>">
                <?php echo esc_html($access_tier_name); ?> Member
            </div>
            <div class="user-actions">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-book-fill"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count($enrolled_courses); ?></div>
                <div class="stat-label">Enrolled Courses</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-trophy-fill"></i>
            </div>
            <div class="stat-content">
                <?php
                $total_completed = 0;
                foreach ($enrolled_courses as $course) {
                    $progress = $user_manager->get_user_course_progress($current_user->ID, $course->id);
                    if ($progress['percentage'] >= 100) {
                        $total_completed++;
                    }
                }
                ?>
                <div class="stat-number"><?php echo $total_completed; ?></div>
                <div class="stat-label">Completed Courses</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-clock-fill"></i>
            </div>
            <div class="stat-content">
                <?php
                $avg_progress = 0;
                if (!empty($enrolled_courses)) {
                    $total_progress = 0;
                    foreach ($enrolled_courses as $course) {
                        $progress = $user_manager->get_user_course_progress($current_user->ID, $course->id);
                        $total_progress += $progress['percentage'];
                    }
                    $avg_progress = round($total_progress / count($enrolled_courses));
                }
                ?>
                <div class="stat-number"><?php echo $avg_progress; ?>%</div>
                <div class="stat-label">Average Progress</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <div class="stat-content">
                <?php
                $member_since = get_user_meta($current_user->ID, 'clarity_registration_date', true);
                if ($member_since) {
                    $days_since = floor((time() - strtotime($member_since)) / (60 * 60 * 24));
                } else {
                    $days_since = 0;
                }
                ?>
                <div class="stat-number"><?php echo $days_since; ?></div>
                <div class="stat-label">Days as Member</div>
            </div>
        </div>
    </div>

    <!-- Course Grid -->
    <div class="dashboard-content">
        <div class="section-header">
            <h2>Your Courses</h2>
            <p>Track your progress and continue learning</p>
        </div>

        <?php if (empty($enrolled_courses)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-book"></i>
                </div>
                <h3>No courses yet</h3>
                <p>You haven't enrolled in any courses yet. Browse our course catalog to get started!</p>
                <a href="/courses/" class="cta-btn">Browse Courses</a>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($enrolled_courses as $course): ?>
                    <?php 
                    $progress = $user_manager->get_user_course_progress($current_user->ID, $course->id);
                    $can_access = $user_manager->user_can_access_course($current_user->ID, $course->id);
                    ?>
                    <div class="course-card <?php echo $can_access ? 'accessible' : 'locked'; ?>">
                        <div class="course-header">
                            <div class="course-tier course-tier-<?php echo esc_attr($course->course_tier); ?>">
                                <?php
                                $tier_names = array(1 => 'Free', 2 => 'Core', 3 => 'Premium');
                                echo esc_html($tier_names[$course->course_tier] ?? 'Unknown');
                                ?>
                            </div>
                            <?php if (!$can_access): ?>
                                <div class="locked-indicator">
                                    <i class="bi bi-lock-fill"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="course-content">
                            <h3><?php echo esc_html($course->course_title); ?></h3>
                            <p><?php echo esc_html(wp_trim_words($course->course_description, 20)); ?></p>
                            
                            <div class="course-progress">
                                <div class="progress-label">
                                    <span>Progress: <?php echo $progress['completed']; ?>/<?php echo $progress['total']; ?> lessons</span>
                                    <span class="progress-percentage"><?php echo $progress['percentage']; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress['percentage']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="course-actions">
                            <?php if ($can_access): ?>
                                <?php if ($progress['percentage'] > 0): ?>
                                    <a href="/course/<?php echo esc_attr($course->id); ?>/" class="course-btn continue-btn">
                                        <i class="bi bi-play-circle-fill"></i> Continue Learning
                                    </a>
                                <?php else: ?>
                                    <a href="/course/<?php echo esc_attr($course->id); ?>/" class="course-btn start-btn">
                                        <i class="bi bi-play-fill"></i> Start Course
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($progress['percentage'] >= 100): ?>
                                    <a href="/certificate/<?php echo esc_attr($course->id); ?>/" class="course-btn certificate-btn">
                                        <i class="bi bi-award-fill"></i> Download Certificate
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="course-btn locked-btn" disabled>
                                    <i class="bi bi-lock-fill"></i> Upgrade Required
                                </button>
                                <a href="/upgrade/" class="course-btn upgrade-btn">
                                    <i class="bi bi-arrow-up-circle-fill"></i> Upgrade Access
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Account Settings -->
    <div class="dashboard-sidebar">
        <div class="sidebar-section">
            <h3>Account Settings</h3>
            <div class="account-info">
                <div class="info-item">
                    <label>Name:</label>
                    <span><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></span>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <span><?php echo esc_html($current_user->user_email); ?></span>
                </div>
                <div class="info-item">
                    <label>Access Level:</label>
                    <span class="access-level-text"><?php echo esc_html($access_tier_name); ?> Member</span>
                </div>
                <div class="info-item">
                    <label>Member Since:</label>
                    <span><?php echo $member_since ? date('M j, Y', strtotime($member_since)) : 'N/A'; ?></span>
                </div>
            </div>
            <div class="account-actions">
                <a href="#" class="settings-btn" id="edit-profile">
                    <i class="bi bi-pencil-fill"></i> Edit Profile
                </a>
                <a href="/upgrade/" class="upgrade-btn">
                    <i class="bi bi-star-fill"></i> Upgrade Account
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.clarity-dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.dashboard-header {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 2rem;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-welcome h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 600;
}

.user-welcome p {
    margin: 0;
    opacity: 0.9;
}

.user-info {
    text-align: right;
}

.access-level-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 1rem;
    display: inline-block;
}

.logout-btn {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    opacity: 0.9;
    transition: opacity 0.2s ease;
}

.logout-btn:hover {
    opacity: 1;
    text-decoration: none;
    color: #fff;
}

.dashboard-stats {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.dashboard-content {
    /* This will take the main content area */
}

.section-header {
    margin-bottom: 2rem;
}

.section-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: #333;
}

.section-header p {
    margin: 0;
    color: #6c757d;
}

.courses-grid {
    display: grid;
    gap: 1.5rem;
}

.course-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.course-card.locked {
    opacity: 0.7;
}

.course-header {
    padding: 1rem 1.5rem 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.course-tier {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.course-tier-1 { background: #d4edda; color: #155724; }
.course-tier-2 { background: #fff3cd; color: #856404; }
.course-tier-3 { background: #f8d7da; color: #721c24; }

.locked-indicator {
    color: #dc3545;
    font-size: 1.2rem;
}

.course-content {
    padding: 1rem 1.5rem;
}

.course-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: #333;
}

.course-content p {
    margin: 0 0 1rem 0;
    color: #6c757d;
    line-height: 1.5;
}

.course-progress {
    margin-top: 1rem;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: #6c757d;
}

.progress-percentage {
    font-weight: 600;
    color: #667eea;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.course-actions {
    padding: 1rem 1.5rem 1.5rem;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.course-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    justify-content: center;
}

.start-btn, .continue-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.certificate-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #fff;
}

.locked-btn {
    background: #6c757d;
    color: #fff;
    cursor: not-allowed;
}

.upgrade-btn {
    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
    color: #fff;
}

.course-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    text-decoration: none;
    color: #fff;
}

.dashboard-sidebar {
    /* This will take the sidebar area */
}

.sidebar-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.sidebar-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    color: #333;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f8f9fa;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-item label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
}

.info-item span {
    color: #333;
    font-size: 0.9rem;
}

.account-actions {
    margin-top: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.settings-btn {
    background: #f8f9fa;
    color: #6c757d;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: center;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.settings-btn:hover {
    background: #e9ecef;
    text-decoration: none;
    color: #495057;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-icon {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.empty-state p {
    margin: 0 0 2rem 0;
    color: #6c757d;
}

.cta-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    transition: transform 0.2s ease;
}

.cta-btn:hover {
    transform: translateY(-2px);
    text-decoration: none;
    color: #fff;
}

@media (max-width: 768px) {
    .clarity-dashboard-container {
        grid-template-columns: 1fr;
        padding: 1rem 0.5rem;
    }
    
    .dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .user-info {
        text-align: center;
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .course-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle edit profile
    $('#edit-profile').on('click', function(e) {
        e.preventDefault();
        alert('Profile editing functionality coming soon!');
    });
});
</script>