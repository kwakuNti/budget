document.addEventListener('DOMContentLoaded', () => {
    loadMembers();
    const form = document.getElementById('contributionForm');
    form.addEventListener('submit', submitContribution);
});

// Load members from backend
async function loadMembers() {
    try {
        const response = await fetch('../api/member_handler.php?action=get_members');
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('contributionMember');
            select.innerHTML = '<option value="">Select Member</option>';
            data.members.forEach(member => {
                const option = document.createElement('option');
                option.value = member.id;
                option.textContent = `${member.first_name} ${member.last_name}`;
                select.appendChild(option);
            });
        } else {
            console.error(data.message || 'Failed to load members');
        }
    } catch (error) {
        console.error('Error loading members:', error);
    }
}

// Handle contribution form submission
async function submitContribution(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('member_id', document.getElementById('contributionMember').value);
    formData.append('amount', document.getElementById('contributionAmount').value);
    formData.append('notes', document.getElementById('contributionNote').value);
    formData.append('action', 'add_contribution');

    try {
        const response = await fetch('../api/contribution_handler.php', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        try {
            const data = JSON.parse(text);

            if (data.success) {
                showSnackbar(data.message, 'success');
                document.getElementById('contributionForm').reset();
                if (typeof loadDashboardData === 'function') {
                    loadDashboardData();
                }
            } else {
                showSnackbar(data.message || 'Error adding contribution', 'error');
            }
        } catch (jsonError) {
            console.error('Invalid JSON:', text);
            showSnackbar('Server error: invalid response format', 'error');
        }
    } catch (error) {
        console.error('Error submitting contribution:', error);
        showSnackbar('Failed to connect to server', 'error');
    }
}

// Snackbar utility
function showSnackbar(message, type = 'info') {
    const snackbar = document.createElement('div');
    snackbar.className = `snackbar ${type}`;
    snackbar.textContent = message;

    document.body.appendChild(snackbar);
    setTimeout(() => {
        snackbar.classList.add('show');
    }, 100);

    setTimeout(() => {
        snackbar.classList.remove('show');
        snackbar.remove();
    }, 4000);
}
