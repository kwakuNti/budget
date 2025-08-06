// Sidebar functionality
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 1024 && 
        sidebar.classList.contains('open') && 
        !sidebar.contains(e.target) && 
        !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// Snackbar functionality
function showSnackbar(message, type = 'default') {
    const snackbar = document.getElementById('snackbar');
    snackbar.textContent = message;
    snackbar.className = 'show';
    
    if (type !== 'default') {
        snackbar.classList.add(type);
    }
    
    setTimeout(() => {
        snackbar.className = snackbar.className.replace('show', '');
        snackbar.classList.remove('success', 'error', 'warning');
    }, 3000);
}

function confirmDelete(id) {
    document.getElementById('deleteMemberId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Modal functionality for Add Member
function showAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Reset form for new member
    document.getElementById('addMemberForm').reset();
    document.getElementById('editingMemberId').value = '';
    document.getElementById('memberModalTitle').textContent = 'Add New Member';
    document.getElementById('memberSubmitBtn').textContent = 'Add Member';
    document.querySelector('input[name="action"]').value = 'add_member';
}

function closeAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('addMemberForm').reset();
}

// Modal functionality for Add Existing User
function showAddExistingUserModal() {
    const modal = document.getElementById('addExistingUserModal');
    if (!modal) {
        showSnackbar('Add existing user feature not available', 'error');
        return;
    }
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('addExistingUserForm').reset();
}

function closeAddExistingUserModal() {
    const modal = document.getElementById('addExistingUserModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('addExistingUserForm').reset();
    }
}

// Modal functionality for Member Details
function closeMemberDetailsModal() {
    const modal = document.getElementById('memberDetailsModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    const addMemberModal = document.getElementById('addMemberModal');
    const addExistingModal = document.getElementById('addExistingUserModal');
    const detailsModal = document.getElementById('memberDetailsModal');
    
    if (e.target === addMemberModal) {
        closeAddMemberModal();
    }
    if (e.target === addExistingModal) {
        closeAddExistingUserModal();
    }
    if (e.target === detailsModal) {
        closeMemberDetailsModal();
    }
});

// Form submission handlers
// document.getElementById('addMemberForm').addEventListener('submit', function(e) {
//     e.preventDefault();
    
//     const formData = new FormData(this);
//     const submitBtn = document.getElementById('memberSubmitBtn');
//     const originalText = submitBtn.textContent;
    
//     submitBtn.disabled = true;
//     submitBtn.textContent = 'Processing...';
    
//     fetch('../../actions/member_actions.php', {
//         method: 'POST',
//         body: formData
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (data.success) {
//             showSnackbar(data.message, 'success');
//             closeAddMemberModal();
//             if (data.redirect) {
//                 setTimeout(() => {
//                     window.location.reload();
//                 }, 1500);
//             }
//         } else {
//             showSnackbar(data.message || 'An error occurred', 'error');
//         }
//     })
//     .catch(error => {
//         console.error('Error:', error);
//         showSnackbar('Network error occurred', 'error');
//     })
//     .finally(() => {
//         submitBtn.disabled = false;
//         submitBtn.textContent = originalText;
//     });
// });



function openEditModal(member) {
    const data = JSON.parse(member);

    document.getElementById('editMemberId').value = data.id;
    document.getElementById('editFirstName').value = data.first_name;
    document.getElementById('editLastName').value = data.last_name;
    document.getElementById('editPhone').value = data.phone_number;
    document.getElementById('editRole').value = data.role;
    document.getElementById('editMonthlyGoal').value = data.monthly_contribution_goal;

    document.getElementById('editMemberModal').style.display = 'block';
}

function closeEditMemberModal() {
    document.getElementById('editMemberModal').style.display = 'none';
}

// Edit Member functionality




// Remove Member functionality

// Sign out functionality
function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        showSnackbar('Signing out...', 'warning');
        setTimeout(() => {
            window.location.href = '../actions/signout';
        }, 1500);
    }
}

// Form validation helpers
function validateEmail(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = emailRegex.test(input.value);
    input.style.borderColor = isValid ? '#10b981' : '#ef4444';
    return isValid;
}

function validatePhone(input) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    const isValid = phoneRegex.test(input.value);
    input.style.borderColor = isValid ? '#10b981' : '#ef4444';
    return isValid;
}

function validateAmount(input) {
    const value = parseFloat(input.value);
    const isValid = !isNaN(value) && value >= 0;
    input.style.borderColor = isValid ? '#10b981' : '#ef4444';
    return isValid;
}

// Add real-time validation
document.getElementById('memberEmail').addEventListener('blur', function() {
    validateEmail(this);
});

document.getElementById('memberPhone').addEventListener('blur', function() {
    validatePhone(this);
});

document.getElementById('memberMonthlyGoal').addEventListener('input', function() {
    validateAmount(this);
});

// Format phone number input
document.getElementById('memberPhone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.startsWith('233')) {
        value = '+' + value;
    } else if (value.startsWith('0')) {
        value = '+233 ' + value.substring(1);
    }
    this.value = value;
});

// Auto-populate display name from first name
document.getElementById('memberFirstName').addEventListener('input', function() {
    const displayNameField = document.getElementById('memberDisplayName');
    if (!displayNameField.value) {
        displayNameField.value = this.value;
    }
});

// Existing user dropdown change handler
const existingUserSelect = document.getElementById('existingUserId');
if (existingUserSelect) {
    existingUserSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            // Extract name from the option text (format: "First Last (email)")
            const optionText = selectedOption.textContent;
            const nameMatch = optionText.match(/^([^(]+)/);
            if (nameMatch) {
                const displayNameField = document.getElementById('existingUserDisplayName');
                if (!displayNameField.value) {
                    displayNameField.value = nameMatch[1].trim();
                }
            }
        }
    });
}

// Animate progress bars on load
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress bars
    setTimeout(() => {
        document.querySelectorAll('.progress-fill').forEach(fill => {
            const width = fill.style.width;
            fill.style.width = '0%';
            fill.style.transition = 'width 1s ease-in-out';
            setTimeout(() => {
                fill.style.width = width;
            }, 100);
        });
    }, 500);
    
    // Show welcome message
    setTimeout(() => {
        showSnackbar('Welcome to Members Management!', 'success');
    }, 1000);
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddMemberModal();
        closeAddExistingUserModal();
        closeMemberDetailsModal();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        showAddMemberModal();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        showAddExistingUserModal();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        sidebar.classList.toggle('open');
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        sidebar.classList.remove('open');
    }
});

// Search functionality (can be implemented later)
function searchMembers(query) {
    const cards = document.querySelectorAll('.member-card');
    cards.forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        const email = card.querySelector('.member-contact').textContent.toLowerCase();
        const visible = name.includes(query.toLowerCase()) || email.includes(query.toLowerCase());
        card.style.display = visible ? 'block' : 'none';
    });
}

// Update member goal (inline editing)
function updateMemberGoal(memberId, newGoal) {
    const formData = new FormData();
    formData.append('action', 'update_member_goal');
    formData.append('member_id', memberId);
    formData.append('new_goal', newGoal);
    
    fetch('../../actions/member_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSnackbar(data.message, 'success');
            // Optionally refresh the member card or update the display
        } else {
            showSnackbar(data.message || 'Failed to update goal', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSnackbar('Failed to update goal', 'error');
    });
}

// Common validators
function validatePhone(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.startsWith('233')) {
        input.value = '+' + value;
    } else if (value.startsWith('0')) {
        input.value = '+233 ' + value.substring(1);
    }
    const isValid = /^\+233\s?[2-5][0-9]{8}$/.test(input.value);
    input.style.borderColor = isValid ? '#10b981' : '#ef4444';
    return isValid;
}

function validateAmount(input) {
    const value = parseFloat(input.value);
    const isValid = !isNaN(value) && value >= 0;
    input.style.borderColor = isValid ? '#10b981' : '#ef4444';
    return isValid;
}

// Apply to Add Form
const addPhoneInput = document.querySelector('#addMemberForm input[name="phone"]');
const addGoalInput = document.querySelector('#addMemberForm input[name="monthly_goal"]');
addPhoneInput.addEventListener('blur', () => validatePhone(addPhoneInput));
addPhoneInput.addEventListener('input', () => validatePhone(addPhoneInput));
addGoalInput.addEventListener('input', () => validateAmount(addGoalInput));

// Apply to Edit Form
const editPhoneInput = document.querySelector('#editMemberForm input[name="phone"]');
const editGoalInput = document.querySelector('#editMemberForm input[name="monthly_goal"]');
editPhoneInput.addEventListener('blur', () => validatePhone(editPhoneInput));
editPhoneInput.addEventListener('input', () => validatePhone(editPhoneInput));
editGoalInput.addEventListener('input', () => validateAmount(editGoalInput));

// Dynamic statistics updates
document.addEventListener('DOMContentLoaded', function () {
    const total = parseInt(document.getElementById('totalMembers').textContent) || 0;
    const active = document.querySelectorAll('.member-status.status-active').length;
    const activeText = document.querySelector('.total-members .stat-change');
    const participationText = document.querySelector('.active-contributors .stat-change');

    // Update 'All members active'
    if (total > 0) {
        activeText.textContent = active === total ? '↗ All members active' : `${active}/${total} active`;
        participationText.textContent = `${Math.round((active / total) * 100)}% participation`;
    }

    // Optionally fetch or calculate change in average for 'Steady Growth'
    // You could replace it with '↗ Improved from last month' dynamically if backend provides it
});

