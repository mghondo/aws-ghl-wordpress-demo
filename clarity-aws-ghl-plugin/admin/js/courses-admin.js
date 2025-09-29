/**
 * Course Management Admin JavaScript
 *
 * Handles admin interface interactions for course management
 *
 * @package Clarity_AWS_GHL
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initCourseManagement();
        initTestingControls();
        initModals();
    });
    
    /**
     * Initialize course management functionality
     */
    function initCourseManagement() {
        // Add course button
        $('#add-course-btn').on('click', function() {
            $('#add-course-modal').show();
        });
        
        // Save course button
        $('#save-course-btn').on('click', function() {
            saveCourse();
        });
        
        // Edit course
        $(document).on('click', '.edit-course', function(e) {
            e.preventDefault();
            var courseId = $(this).data('course-id');
            editCourse(courseId);
        });
        
        // Update course button
        $('#update-course-btn').on('click', function() {
            updateCourse();
        });
        
        // Delete course
        $(document).on('click', '.delete-course', function(e) {
            e.preventDefault();
            
            if (confirm(clarityCoursesAjax.strings.confirm_delete)) {
                var courseId = $(this).data('course-id');
                deleteCourse(courseId);
            }
        });
        
        // Manage lessons
        $(document).on('click', '.manage-lessons', function() {
            var courseId = $(this).data('course-id');
            var courseName = $(this).data('course-name') || 'Course';
            openLessonManagement(courseId, courseName);
        });
        
        // Select all checkbox
        $('#select-all-courses').on('change', function() {
            $('input[name="course[]"]').prop('checked', this.checked);
        });
        
        // Auto-generate slug from title
        $('input[name="course_title"]').on('keyup', function() {
            var title = $(this).val();
            var slug = title.toLowerCase()
                          .replace(/[^a-z0-9 -]/g, '')
                          .replace(/\s+/g, '-')
                          .replace(/-+/g, '-');
            $('input[name="course_slug"]').val(slug);
        });
    }
    
    /**
     * Initialize testing controls functionality
     */
    function initTestingControls() {
        // Enroll user button
        $('#enroll-user-btn').on('click', function() {
            var userId = $('#test-user-select').val();
            var courseId = $('#test-course-select').val();
            
            if (!userId || !courseId) {
                alert('Please select both a user and a course.');
                return;
            }
            
            enrollUser(userId, courseId);
        });
        
        // Complete lesson button
        $('#complete-lesson-btn').on('click', function() {
            var userId = $('#test-user-select').val();
            var courseId = $('#test-course-select').val();
            
            if (!userId || !courseId) {
                alert('Please select both a user and a course.');
                return;
            }
            
            completeNextLesson(userId, courseId);
        });
        
        // Reset progress button
        $('#reset-progress-btn').on('click', function() {
            var userId = $('#test-user-select').val();
            var courseId = $('#test-course-select').val();
            
            if (!userId || !courseId) {
                alert('Please select both a user and a course.');
                return;
            }
            
            if (confirm(clarityCoursesAjax.strings.confirm_reset)) {
                resetUserProgress(userId, courseId);
            }
        });
        
        // View progress details
        $(document).on('click', '.view-progress', function() {
            var userId = $(this).data('user-id');
            var courseId = $(this).data('course-id');
            
            // For now, just show course stats - could be expanded to detailed view
            getCourseStats(userId, courseId);
        });
    }
    
    /**
     * Initialize modal functionality
     */
    function initModals() {
        // Close modal
        $('.clarity-modal-close').on('click', function() {
            $(this).closest('.clarity-modal').hide();
        });
        
        // Close modal when clicking outside
        $('.clarity-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    }
    
    /**
     * Save new course
     */
    function saveCourse() {
        var $form = $('#add-course-form');
        var $button = $('#save-course-btn');
        
        // Validate form
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        var formData = {
            action: 'clarity_create_course',
            nonce: clarityCoursesAjax.nonce,
            course_title: $form.find('[name="course_title"]').val(),
            course_slug: $form.find('[name="course_slug"]').val(),
            course_description: $form.find('[name="course_description"]').val(),
            course_tier: $form.find('[name="course_tier"]').val(),
            course_price: $form.find('[name="course_price"]').val(),
            course_status: $form.find('[name="course_status"]').val()
        };
        
        $button.prop('disabled', true).text(clarityCoursesAjax.strings.processing);
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $('#add-course-modal').hide();
                    $form[0].reset();
                    location.reload(); // Refresh to show new course
                } else {
                    showMessage(response.data || 'Failed to create course', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Create Course');
            }
        });
    }
    
    /**
     * Delete course
     */
    function deleteCourse(courseId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_delete_course',
                nonce: clarityCoursesAjax.nonce,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Course deleted successfully', 'success');
                    $('tr[data-course-id="' + courseId + '"]').fadeOut();
                } else {
                    showMessage(response.data || 'Failed to delete course', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Enroll user in course
     */
    function enrollUser(userId, courseId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_enroll_user',
                nonce: clarityCoursesAjax.nonce,
                user_id: userId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('User enrolled successfully', 'success');
                } else {
                    showMessage(response.data || 'Failed to enroll user', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Complete next lesson for user
     */
    function completeNextLesson(userId, courseId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_complete_lesson',
                nonce: clarityCoursesAjax.nonce,
                user_id: userId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson marked complete', 'success');
                    location.reload(); // Refresh to show updated progress
                } else {
                    showMessage(response.data || 'Failed to complete lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Reset user progress
     */
    function resetUserProgress(userId, courseId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_reset_user_progress',
                nonce: clarityCoursesAjax.nonce,
                user_id: userId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Progress reset successfully', 'success');
                    location.reload(); // Refresh to show updated progress
                } else {
                    showMessage(response.data || 'Failed to reset progress', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Get course statistics
     */
    function getCourseStats(userId, courseId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_course_stats',
                nonce: clarityCoursesAjax.nonce,
                user_id: userId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    // For now, just alert the stats - could be expanded to modal
                    var stats = response.data;
                    var message = 'Course Progress:\n' +
                                'Progress: ' + stats.progress + '%\n' +
                                'Completed Lessons: ' + stats.completed_lessons + '/' + stats.total_lessons + '\n' +
                                'Enrollment Date: ' + stats.enrollment_date;
                    alert(message);
                } else {
                    showMessage(response.data || 'Failed to get stats', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Show message to user
     */
    function showMessage(message, type) {
        var className = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + className + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
        
        // Handle dismiss button
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut();
        });
    }
    
    /**
     * Utility function to format currency
     */
    function formatCurrency(amount) {
        return '$' + parseFloat(amount).toFixed(2);
    }
    
    /**
     * Utility function to format percentage
     */
    function formatPercentage(value) {
        return Math.round(value) + '%';
    }
    
    /**
     * Open lesson management modal
     */
    function openLessonManagement(courseId, courseName) {
        $('#lesson-course-id').val(courseId);
        $('#lesson-course-name').text(courseName);
        loadLessonAssignmentData(courseId);
        $('#lesson-management-modal').show();
    }
    
    /**
     * Load lesson assignment data
     */
    function loadLessonAssignmentData(courseId) {
        // Load available lessons (unassigned)
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_available_lessons',
                nonce: clarityCoursesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayAvailableLessons(response.data);
                } else {
                    $('#available-lessons-list').html('<div class="no-lessons-available">No lessons available</div>');
                }
            },
            error: function() {
                $('#available-lessons-list').html('<div class="no-lessons-available">Error loading lessons</div>');
            }
        });
        
        // Load assigned lessons for this course
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_course_lessons',
                nonce: clarityCoursesAjax.nonce,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    displayAssignedLessons(response.data);
                } else {
                    $('#assigned-lessons-list').html('<div class="no-lessons-assigned">No lessons assigned to this course</div>');
                }
            },
            error: function() {
                $('#assigned-lessons-list').html('<div class="no-lessons-assigned">Error loading course lessons</div>');
            }
        });
    }
    
    /**
     * Display available lessons
     */
    function displayAvailableLessons(lessons) {
        var $container = $('#available-lessons-list');
        $container.empty();
        
        if (lessons.length === 0) {
            $container.html('<div class="no-lessons-available">All lessons are assigned<br><small>Create new lessons in the Lessons tab</small></div>');
            $('#available-count').text(0);
            return;
        }
        
        lessons.forEach(function(lesson) {
            var $lessonItem = $('<div class="available-lesson-item" data-lesson-id="' + lesson.id + '">' +
                '<div class="lesson-info">' +
                    '<div class="lesson-title">' + lesson.lesson_title + '</div>' +
                    '<div class="lesson-meta">' + (lesson.video_url ? 'Has video' : 'No video') + '</div>' +
                '</div>' +
                '<div class="add-icon">+</div>' +
            '</div>');
            
            $container.append($lessonItem);
        });
        
        $('#available-count').text(lessons.length);
        
        // Add click handlers for assigning lessons
        $container.find('.available-lesson-item').on('click', function() {
            var lessonId = $(this).data('lesson-id');
            assignLessonToCourse(lessonId);
        });
    }
    
    /**
     * Display assigned lessons
     */
    function displayAssignedLessons(lessons) {
        var $container = $('#assigned-lessons-list');
        $container.empty();
        
        if (lessons.length === 0) {
            $container.html('<div class="no-lessons-assigned">No lessons assigned to this course<br><small>Click lessons from the left to assign them</small></div>');
            $('#assigned-count').text(0);
            return;
        }
        
        lessons.forEach(function(lesson) {
            var $lessonItem = $('<div class="assigned-lesson-item" data-lesson-id="' + lesson.id + '">' +
                '<div class="assigned-lesson-header">' +
                    '<span class="lesson-order-badge">' + lesson.lesson_order + '</span>' +
                    '<div class="assigned-lesson-title">' + lesson.lesson_title + '</div>' +
                    '<button class="remove-lesson-btn" data-lesson-id="' + lesson.id + '">Remove</button>' +
                '</div>' +
                '<div class="assigned-lesson-content">' +
                    (lesson.video_url ? 'Video: ' + lesson.video_url.substring(0, 50) + '...' : 'No video') +
                '</div>' +
            '</div>');
            
            $container.append($lessonItem);
        });
        
        $('#assigned-count').text(lessons.length);
        
        // Make lessons sortable
        $container.sortable({
            handle: '.assigned-lesson-header',
            update: function(event, ui) {
                updateAssignedLessonOrder();
            }
        });
        
        // Add click handlers for removing lessons
        $container.find('.remove-lesson-btn').on('click', function(e) {
            e.stopPropagation();
            var lessonId = $(this).data('lesson-id');
            removeLessonFromCourse(lessonId);
        });
    }
    
    /**
     * Assign lesson to course
     */
    function assignLessonToCourse(lessonId) {
        var courseId = $('#lesson-course-id').val();
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_assign_lesson_to_course',
                nonce: clarityCoursesAjax.nonce,
                lesson_id: lessonId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson assigned successfully', 'success');
                    loadLessonAssignmentData(courseId); // Reload both lists
                } else {
                    showMessage(response.data || 'Failed to assign lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Remove lesson from course
     */
    function removeLessonFromCourse(lessonId) {
        var courseId = $('#lesson-course-id').val();
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_remove_lesson_from_course',
                nonce: clarityCoursesAjax.nonce,
                lesson_id: lessonId,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson removed successfully', 'success');
                    loadLessonAssignmentData(courseId); // Reload both lists
                } else {
                    showMessage(response.data || 'Failed to remove lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Update assigned lesson order after drag and drop
     */
    function updateAssignedLessonOrder() {
        var lessonIds = [];
        $('#assigned-lessons-list .assigned-lesson-item').each(function(index) {
            lessonIds.push({
                id: $(this).data('lesson-id'),
                order: index + 1
            });
        });
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_reorder_lessons',
                nonce: clarityCoursesAjax.nonce,
                lessons: JSON.stringify(lessonIds)
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson order updated', 'success');
                    // Update the order badges
                    $('#assigned-lessons-list .assigned-lesson-item').each(function(index) {
                        $(this).find('.lesson-order-badge').text(index + 1);
                    });
                } else {
                    showMessage(response.data || 'Failed to update order', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    // Initialize lesson management when document is ready
    $(document).ready(function() {
        initLessonManagement();
        initStandaloneLessonManagement();
    });
    
    /**
     * Initialize lesson management functionality
     */
    function initLessonManagement() {
        // Add lesson button
        $(document).on('click', '#add-lesson-btn', function() {
            $('#add-lesson-modal').show();
        });
        
        // Save lesson button
        $(document).on('click', '#save-lesson-btn', function() {
            saveLesson();
        });
        
        // Edit lesson
        $(document).on('click', '.edit-lesson', function() {
            var lessonId = $(this).data('lesson-id');
            editLesson(lessonId);
        });
        
        // Delete lesson
        $(document).on('click', '.delete-lesson', function() {
            var lessonId = $(this).data('lesson-id');
            if (confirm('Are you sure you want to delete this lesson?')) {
                deleteLesson(lessonId);
            }
        });
        
        // Update lesson button
        $(document).on('click', '#update-lesson-btn', function() {
            updateLesson();
        });
    }
    
    /**
     * Save new lesson
     */
    function saveLesson() {
        var $form = $('#add-lesson-form');
        var $button = $('#save-lesson-btn');
        
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        var formData = {
            action: 'clarity_create_lesson',
            nonce: clarityCoursesAjax.nonce,
            course_id: $('#lesson-course-id').val(),
            lesson_title: $form.find('[name="lesson_title"]').val(),
            lesson_content: $form.find('[name="lesson_content"]').val(),
            video_url: $form.find('[name="video_url"]').val()
        };
        
        $button.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson created successfully', 'success');
                    $('#add-lesson-modal').hide();
                    $form[0].reset();
                    loadLessonsForCourse($('#lesson-course-id').val());
                } else {
                    showMessage(response.data || 'Failed to create lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Add Lesson');
            }
        });
    }
    
    /**
     * Edit lesson
     */
    function editLesson(lessonId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_lesson',
                nonce: clarityCoursesAjax.nonce,
                lesson_id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    var lesson = response.data;
                    $('#edit-lesson-id').val(lesson.id);
                    $('#edit-lesson-form [name="lesson_title"]').val(lesson.lesson_title);
                    $('#edit-lesson-form [name="lesson_content"]').val(lesson.lesson_content);
                    $('#edit-lesson-form [name="video_url"]').val(lesson.video_url);
                    $('#edit-lesson-modal').show();
                } else {
                    showMessage(response.data || 'Failed to load lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Update lesson
     */
    function updateLesson() {
        var $form = $('#edit-lesson-form');
        var $button = $('#update-lesson-btn');
        
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        var formData = {
            action: 'clarity_edit_lesson',
            nonce: clarityCoursesAjax.nonce,
            lesson_id: $('#edit-lesson-id').val(),
            lesson_title: $form.find('[name="lesson_title"]').val(),
            lesson_content: $form.find('[name="lesson_content"]').val(),
            video_url: $form.find('[name="video_url"]').val()
        };
        
        $button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson updated successfully', 'success');
                    $('#edit-lesson-modal').hide();
                    loadLessonsForCourse($('#lesson-course-id').val());
                } else {
                    showMessage(response.data || 'Failed to update lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Update Lesson');
            }
        });
    }
    
    /**
     * Delete lesson
     */
    function deleteLesson(lessonId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_delete_lesson',
                nonce: clarityCoursesAjax.nonce,
                lesson_id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson deleted successfully', 'success');
                    loadLessonsForCourse($('#lesson-course-id').val());
                } else {
                    showMessage(response.data || 'Failed to delete lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Initialize standalone lesson management functionality
     */
    function initStandaloneLessonManagement() {
        // Add new lesson button
        $(document).on('click', '#add-new-lesson-btn', function() {
            $('#add-new-lesson-modal').show();
        });
        
        // Save new lesson button
        $(document).on('click', '#save-new-lesson-btn', function() {
            saveStandaloneLesson();
        });
        
        // Edit standalone lesson
        $(document).on('click', '.edit-standalone-lesson', function() {
            var lessonId = $(this).data('lesson-id');
            editStandaloneLesson(lessonId);
        });
        
        // Delete standalone lesson
        $(document).on('click', '.delete-standalone-lesson', function() {
            var lessonId = $(this).data('lesson-id');
            if (confirm('Are you sure you want to delete this lesson?')) {
                deleteStandaloneLesson(lessonId);
            }
        });
        
        // Update standalone lesson button
        $(document).on('click', '#update-standalone-lesson-btn', function() {
            updateStandaloneLesson();
        });
    }
    
    /**
     * Save new standalone lesson
     */
    function saveStandaloneLesson() {
        var $form = $('#add-new-lesson-form');
        var $button = $('#save-new-lesson-btn');
        
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        var formData = {
            action: 'clarity_create_standalone_lesson',
            nonce: clarityCoursesAjax.nonce,
            lesson_title: $form.find('[name="lesson_title"]').val(),
            lesson_content: $form.find('[name="lesson_content"]').val(),
            video_url: $form.find('[name="video_url"]').val()
        };
        
        $button.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson created successfully', 'success');
                    $('#add-new-lesson-modal').hide();
                    $form[0].reset();
                    location.reload(); // Refresh to show new lesson
                } else {
                    showMessage(response.data || 'Failed to create lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Create Lesson');
            }
        });
    }
    
    /**
     * Edit standalone lesson
     */
    function editStandaloneLesson(lessonId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_standalone_lesson',
                nonce: clarityCoursesAjax.nonce,
                lesson_id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    var lesson = response.data;
                    $('#edit-standalone-lesson-id').val(lesson.id);
                    $('#edit-standalone-lesson-form [name="lesson_title"]').val(lesson.lesson_title);
                    $('#edit-standalone-lesson-form [name="lesson_content"]').val(lesson.lesson_content);
                    $('#edit-standalone-lesson-form [name="video_url"]').val(lesson.video_url);
                    $('#edit-standalone-lesson-modal').show();
                } else {
                    showMessage(response.data || 'Failed to load lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Update standalone lesson
     */
    function updateStandaloneLesson() {
        var $form = $('#edit-standalone-lesson-form');
        var $button = $('#update-standalone-lesson-btn');
        
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        var formData = {
            action: 'clarity_edit_standalone_lesson',
            nonce: clarityCoursesAjax.nonce,
            lesson_id: $('#edit-standalone-lesson-id').val(),
            lesson_title: $form.find('[name="lesson_title"]').val(),
            lesson_content: $form.find('[name="lesson_content"]').val(),
            video_url: $form.find('[name="video_url"]').val()
        };
        
        $button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson updated successfully', 'success');
                    $('#edit-standalone-lesson-modal').hide();
                    location.reload(); // Refresh to show updated lesson
                } else {
                    showMessage(response.data || 'Failed to update lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Update Lesson');
            }
        });
    }
    
    /**
     * Edit course
     */
    function editCourse(courseId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_course',
                nonce: clarityCoursesAjax.nonce,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    var course = response.data;
                    $('#edit-course-id').val(course.id);
                    $('#edit-course-form [name="course_title"]').val(course.course_title);
                    $('#edit-course-form [name="course_description"]').val(course.course_description);
                    $('#edit-course-form [name="course_status"]').val(course.course_status);
                    $('#edit-course-modal').show();
                } else {
                    showMessage(response.data || 'Failed to load course', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
    /**
     * Update course
     */
    function updateCourse() {
        var $form = $('#edit-course-form');
        var $button = $('#update-course-btn');
        
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        var formData = {
            action: 'clarity_update_course',
            nonce: clarityCoursesAjax.nonce,
            course_id: $('#edit-course-id').val(),
            course_title: $form.find('[name="course_title"]').val(),
            course_description: $form.find('[name="course_description"]').val(),
            course_status: $form.find('[name="course_status"]').val()
        };
        
        $button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('Course updated successfully', 'success');
                    $('#edit-course-modal').hide();
                    location.reload(); // Refresh to show updated course
                } else {
                    showMessage(response.data || 'Failed to update course', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Update Course');
            }
        });
    }
    
    /**
     * Delete standalone lesson
     */
    function deleteStandaloneLesson(lessonId) {
        $.ajax({
            url: clarityCoursesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_delete_standalone_lesson',
                nonce: clarityCoursesAjax.nonce,
                lesson_id: lessonId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Lesson deleted successfully', 'success');
                    $('tr[data-lesson-id="' + lessonId + '"]').fadeOut();
                } else {
                    showMessage(response.data || 'Failed to delete lesson', 'error');
                }
            },
            error: function() {
                showMessage('Ajax error occurred', 'error');
            }
        });
    }
    
})(jQuery);