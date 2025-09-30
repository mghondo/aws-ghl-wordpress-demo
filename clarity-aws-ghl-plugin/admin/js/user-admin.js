/**
 * User Administration JavaScript
 * 
 * Handles admin interface interactions for student management and testing tools
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize
    if ($('#students-table').length) {
        loadStudents();
    }
    
    if ($('#impersonate-user-select').length) {
        loadStudentsForImpersonation();
    }
    
    /**
     * Load students data
     */
    function loadStudents() {
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_students',
                nonce: clarityUserAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayStudents(response.data.students);
                    updateStats(response.data.stats);
                } else {
                    showMessage(response.data || 'Failed to load students', 'error');
                }
            },
            error: function() {
                showMessage('Error loading students data', 'error');
            }
        });
    }
    
    /**
     * Display students in table
     */
    function displayStudents(students) {
        var $tbody = $('#students-table-body');
        $tbody.empty();
        
        if (students.length === 0) {
            $tbody.append(`
                <tr>
                    <td colspan="8" class="no-students">
                        No students found. <a href="#" id="create-test-users-quick">Create test users</a> to get started.
                    </td>
                </tr>
            `);
            return;
        }
        
        students.forEach(function(student) {
            var accessLevelClass = 'access-level-' + student.access_level;
            var accessLevelText = ['', 'Free', 'Core', 'Premium'][student.access_level] || 'Unknown';
            
            var row = `
                <tr data-student-id="${student.id}">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="student[]" value="${student.id}">
                    </th>
                    <td>
                        <strong>${escapeHtml(student.name)}</strong>
                        <div class="row-actions">
                            <span class="view">
                                <a href="#" class="view-student" data-student-id="${student.id}">View Details</a> |
                            </span>
                            <span class="reset">
                                <a href="#" class="reset-progress" data-student-id="${student.id}">Reset Progress</a> |
                            </span>
                            <span class="delete">
                                <a href="#" class="delete-student" data-student-id="${student.id}">Delete</a>
                            </span>
                        </div>
                    </td>
                    <td>${escapeHtml(student.email)}</td>
                    <td><span class="access-badge ${accessLevelClass}">${accessLevelText}</span></td>
                    <td>${student.enrolled_courses}</td>
                    <td>
                        <div class="progress-display">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${student.avg_progress}%"></div>
                            </div>
                            <span class="progress-text">${student.avg_progress}%</span>
                        </div>
                    </td>
                    <td>${student.registration_date}</td>
                    <td>
                        <button type="button" class="button button-small impersonate-user" 
                                data-user-id="${student.id}" data-user-email="${student.email}">
                            Impersonate
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });
    }
    
    /**
     * Update statistics display
     */
    function updateStats(stats) {
        $('#total-students').text(stats.total_students);
        $('#active-students').text(Math.floor(stats.total_students * 0.7)); // Estimate
        $('#completed-courses').text(stats.total_completed);
    }
    
    /**
     * Load students for impersonation dropdown
     */
    function loadStudentsForImpersonation() {
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_get_students',
                nonce: clarityUserAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $select = $('#impersonate-user-select');
                    $select.empty().append('<option value="">Select a student...</option>');
                    
                    response.data.students.forEach(function(student) {
                        $select.append(`<option value="${student.id}" data-email="${student.email}">${student.name} (${student.email})</option>`);
                    });
                }
            }
        });
    }
    
    /**
     * Delete student
     */
    $(document).on('click', '.delete-student', function(e) {
        e.preventDefault();
        
        if (!confirm(clarityUserAdmin.strings.confirm_delete)) {
            return;
        }
        
        var studentId = $(this).data('student-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_delete_test_user',
                nonce: clarityUserAdmin.nonce,
                user_id: studentId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                        updateStudentCount();
                    });
                    showMessage('Student deleted successfully', 'success');
                } else {
                    showMessage(response.data || 'Failed to delete student', 'error');
                }
            },
            error: function() {
                showMessage('Error deleting student', 'error');
            }
        });
    });
    
    /**
     * Reset student progress
     */
    $(document).on('click', '.reset-progress', function(e) {
        e.preventDefault();
        
        if (!confirm(clarityUserAdmin.strings.confirm_reset)) {
            return;
        }
        
        var studentId = $(this).data('student-id');
        
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_reset_user_progress',
                nonce: clarityUserAdmin.nonce,
                user_id: studentId
            },
            success: function(response) {
                if (response.success) {
                    // Update progress display in the row
                    var $row = $(`tr[data-student-id="${studentId}"]`);
                    $row.find('.progress-fill').css('width', '0%');
                    $row.find('.progress-text').text('0%');
                    showMessage('Student progress reset successfully', 'success');
                } else {
                    showMessage(response.data || 'Failed to reset progress', 'error');
                }
            },
            error: function() {
                showMessage('Error resetting progress', 'error');
            }
        });
    });
    
    /**
     * Create test users
     */
    $('#create-test-users-btn, #create-test-users-quick').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(clarityUserAdmin.strings.processing);
        
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_create_test_users',
                nonce: clarityUserAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showTestResults('Test Users Created', response.data);
                    
                    // Reload students if on user management page
                    if ($('#students-table').length) {
                        setTimeout(loadStudents, 1000);
                    }
                    
                    // Reload impersonation dropdown if on testing page
                    if ($('#impersonate-user-select').length) {
                        setTimeout(loadStudentsForImpersonation, 1000);
                    }
                } else {
                    showMessage(response.data || 'Failed to create test users', 'error');
                }
            },
            error: function() {
                showMessage('Error creating test users', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    /**
     * Reset demo environment
     */
    $('#reset-demo-btn').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(clarityUserAdmin.strings.confirm_demo_reset)) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(clarityUserAdmin.strings.processing);
        
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_reset_demo',
                nonce: clarityUserAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Demo environment reset successfully', 'success');
                    
                    // Clear tables and dropdowns
                    $('#students-table-body').html('<tr><td colspan="8" class="no-students">No students found. Create test users to get started.</td></tr>');
                    $('#impersonate-user-select').empty().append('<option value="">Select a student...</option>');
                    updateStats({total_students: 0, total_completed: 0});
                } else {
                    showMessage(response.data || 'Failed to reset demo environment', 'error');
                }
            },
            error: function() {
                showMessage('Error resetting demo environment', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    /**
     * Impersonate user
     */
    $(document).on('click', '.impersonate-user, #impersonate-user-btn', function(e) {
        e.preventDefault();
        
        var userId;
        
        if ($(this).hasClass('impersonate-user')) {
            userId = $(this).data('user-id');
        } else {
            userId = $('#impersonate-user-select').val();
        }
        
        if (!userId) {
            showMessage('Please select a user to impersonate', 'error');
            return;
        }
        
        // For now, just show a message about impersonation
        // In a real implementation, you'd need a proper user switching plugin or mechanism
        showMessage('User impersonation would switch to user ID: ' + userId + '. Implement user switching mechanism.', 'info');
    });
    
    /**
     * Refresh students
     */
    $('#refresh-users-btn').on('click', function(e) {
        e.preventDefault();
        loadStudents();
        showMessage('Students data refreshed', 'success');
    });
    
    /**
     * Select all students
     */
    $('#select-all-students').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('#students-table tbody input[type="checkbox"]').prop('checked', isChecked);
    });
    
    /**
     * Bulk actions
     */
    $('#bulk-actions-btn').on('click', function(e) {
        e.preventDefault();
        
        var selectedStudents = getSelectedStudents();
        if (selectedStudents.length === 0) {
            showMessage('Please select at least one student', 'error');
            return;
        }
        
        $('#bulk-actions-modal').show();
    });
    
    /**
     * Bulk action selection change
     */
    $('#bulk-action-select').on('change', function() {
        var action = $(this).val();
        
        // Show/hide relevant fields
        $('#course-select-row').toggle(action === 'enroll');
        $('#access-level-row').toggle(action === 'update_access');
    });
    
    /**
     * Execute bulk action
     */
    $('#execute-bulk-action').on('click', function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action-select').val();
        var selectedStudents = getSelectedStudents();
        
        if (!action) {
            showMessage('Please select an action', 'error');
            return;
        }
        
        if (selectedStudents.length === 0) {
            showMessage('No students selected', 'error');
            return;
        }
        
        // Execute the appropriate action
        switch (action) {
            case 'enroll':
                executeBulkEnroll(selectedStudents);
                break;
            case 'update_access':
                executeBulkUpdateAccess(selectedStudents);
                break;
            case 'reset_progress':
                executeBulkResetProgress(selectedStudents);
                break;
            case 'delete':
                executeBulkDelete(selectedStudents);
                break;
        }
    });
    
    /**
     * Get selected students
     */
    function getSelectedStudents() {
        var selected = [];
        $('#students-table tbody input[type="checkbox"]:checked').each(function() {
            selected.push($(this).val());
        });
        return selected;
    }
    
    /**
     * Execute bulk enrollment
     */
    function executeBulkEnroll(studentIds) {
        var courseId = $('#bulk-course-select').val();
        
        if (!courseId) {
            showMessage('Please select a course', 'error');
            return;
        }
        
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_bulk_enroll_users',
                nonce: clarityUserAdmin.nonce,
                user_ids: studentIds,
                course_id: courseId
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $('#bulk-actions-modal').hide();
                    loadStudents();
                } else {
                    showMessage(response.data || 'Failed to enroll users', 'error');
                }
            },
            error: function() {
                showMessage('Error enrolling users', 'error');
            }
        });
    }
    
    /**
     * Execute bulk access level update
     */
    function executeBulkUpdateAccess(studentIds) {
        var accessLevel = $('#bulk-access-level').val();
        
        $.ajax({
            url: clarityUserAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'clarity_update_user_access',
                nonce: clarityUserAdmin.nonce,
                user_ids: studentIds,
                access_level: accessLevel
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    $('#bulk-actions-modal').hide();
                    loadStudents();
                } else {
                    showMessage(response.data || 'Failed to update access levels', 'error');
                }
            },
            error: function() {
                showMessage('Error updating access levels', 'error');
            }
        });
    }
    
    /**
     * Show test results modal
     */
    function showTestResults(title, data) {
        var content = `<h4>${title}</h4>`;
        
        if (data.users) {
            content += '<ul>';
            data.users.forEach(function(user) {
                content += `<li><strong>${user.name}</strong> (${user.email}) - ${user.progress}% progress</li>`;
            });
            content += '</ul>';
        }
        
        if (data.message) {
            content += `<p>${data.message}</p>`;
        }
        
        $('#test-results-content').html(content);
        $('#test-results-modal').show();
    }
    
    /**
     * Modal controls
     */
    $('.clarity-modal-close, #close-user-modal, #cancel-bulk-action, #close-test-results').on('click', function() {
        $(this).closest('.clarity-modal').hide();
    });
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        var className = type === 'error' ? 'notice-error' : type === 'success' ? 'notice-success' : 'notice-info';
        
        var $notice = $(`<div class="notice ${className} is-dismissible"><p>${message}</p></div>`);
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    /**
     * Update student count
     */
    function updateStudentCount() {
        var count = $('#students-table tbody tr').length;
        $('#total-students').text(count);
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Global impersonate function for testing scenarios
     */
    window.impersonateUser = function(email) {
        $('#impersonate-user-select option').each(function() {
            if ($(this).data('email') === email) {
                $(this).prop('selected', true);
                $('#impersonate-user-btn').click();
                return false;
            }
        });
    };
});