// ===================================
// FEEDBACK ADMIN JAVASCRIPT
// ===================================

class FeedbackAdmin {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Auto-submit filters on change
        const filterSelects = document.querySelectorAll('.filters-form select');
        filterSelects.forEach(select => {
            select.addEventListener('change', () => {
                document.querySelector('.filters-form').submit();
            });
        });
    }
}

// Global functions for admin actions
window.viewFeedback = function(feedbackId) {
    // Create modal to view feedback details
    Swal.fire({
        title: 'Loading Feedback...',
        text: 'Please wait while we fetch the feedback details.',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fetch feedback details
    fetch(`../api/get_feedback.php?id=${feedbackId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFeedbackDetails(data.feedback);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load feedback details: ' + error.message
            });
        });
};

window.updateStatus = function(feedbackId) {
    Swal.fire({
        title: 'Update Status',
        input: 'select',
        inputOptions: {
            'new': 'New',
            'in_progress': 'In Progress',
            'resolved': 'Resolved',
            'closed': 'Closed'
        },
        inputPlaceholder: 'Select status',
        showCancelButton: true,
        confirmButtonText: 'Update',
        showLoaderOnConfirm: true,
        preConfirm: (status) => {
            return fetch('../api/update_feedback_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    feedback_id: feedbackId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Feedback status has been updated.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        }
    });
};

function showFeedbackDetails(feedback) {
    const statusColors = {
        'new': '#f59e0b',
        'in_progress': '#3b82f6',
        'resolved': '#10b981',
        'closed': '#6b7280'
    };
    
    const priorityColors = {
        'low': '#6b7280',
        'medium': '#3b82f6',
        'high': '#f59e0b',
        'urgent': '#ef4444'
    };
    
    const ratingStars = feedback.rating ? 
        Array.from({length: 5}, (_, i) => 
            `<i class="fa${i < feedback.rating ? 's' : 'r'} fa-star" style="color: #fbbf24;"></i>`
        ).join('') : 
        '<span style="color: #9ca3af;">No rating</span>';
    
    Swal.fire({
        title: feedback.subject,
        html: `
            <div style="text-align: left; max-width: 600px;">
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong>User:</strong><br>
                            ${feedback.first_name} ${feedback.last_name}<br>
                            <small style="color: #6b7280;">${feedback.email}</small>
                        </div>
                        <div>
                            <strong>Date:</strong><br>
                            ${new Date(feedback.created_at).toLocaleString()}
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div>
                            <strong>Type:</strong><br>
                            <span style="background: #e5e7eb; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">
                                ${feedback.feedback_type.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                        <div>
                            <strong>Priority:</strong><br>
                            <span style="background: ${priorityColors[feedback.priority]}; color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">
                                ${feedback.priority.toUpperCase()}
                            </span>
                        </div>
                        <div>
                            <strong>Status:</strong><br>
                            <span style="background: ${statusColors[feedback.status]}; color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">
                                ${feedback.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    ${feedback.rating ? `
                        <div style="margin-top: 1rem;">
                            <strong>Rating:</strong><br>
                            ${ratingStars}
                        </div>
                    ` : ''}
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>Message:</strong>
                    <div style="background: white; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 0.5rem; white-space: pre-wrap;">${feedback.message}</div>
                </div>
                
                ${feedback.page_url ? `
                    <div style="margin-bottom: 1rem;">
                        <strong>Page URL:</strong><br>
                        <a href="${feedback.page_url}" target="_blank" style="color: #3b82f6; text-decoration: underline;">${feedback.page_url}</a>
                    </div>
                ` : ''}
                
                ${feedback.browser_info ? `
                    <details style="margin-bottom: 1rem;">
                        <summary style="cursor: pointer; font-weight: bold;">Browser Information</summary>
                        <pre style="background: #f8fafc; padding: 1rem; border-radius: 8px; font-size: 0.75rem; overflow-x: auto; margin-top: 0.5rem;">${JSON.stringify(JSON.parse(feedback.browser_info), null, 2)}</pre>
                    </details>
                ` : ''}
                
                ${feedback.admin_response ? `
                    <div style="margin-top: 1rem;">
                        <strong>Admin Response:</strong>
                        <div style="background: #ecfdf5; padding: 1rem; border: 1px solid #d1fae5; border-radius: 8px; margin-top: 0.5rem;">${feedback.admin_response}</div>
                    </div>
                ` : ''}
            </div>
        `,
        width: '800px',
        showCancelButton: true,
        confirmButtonText: 'Update Status',
        cancelButtonText: 'Close',
        customClass: {
            popup: 'feedback-details-modal'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            updateStatus(feedback.id);
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.feedbackAdmin = new FeedbackAdmin();
});
