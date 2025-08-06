// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    initializeContributionsPage();
    setupEventListeners();
    
    // Set today's date as default
    const dateInput = document.getElementById('newContributionDate');
    if (dateInput) {
        dateInput.valueAsDate = new Date();
    }
    
    // Create chart after DOM is loaded and Chart.js is available
    if (typeof Chart !== 'undefined' && typeof chartData !== 'undefined') {
        console.log('Creating chart with data:', chartData);
        createMemberComparisonChart();
    } else {
        console.error('Chart.js or chartData not available');
        if (typeof Chart === 'undefined') console.error('Chart.js not loaded');
        if (typeof chartData === 'undefined') console.error('chartData not defined');
    }
    
    // Check if filters were applied and show snackbar
    checkForAppliedFilters();
    
    // Setup contribution form submission
    setupContributionFormSubmission();
    
    // Setup goal form submission
    setupGoalFormSubmission();
});

function setupGoalFormSubmission() {
    const goalForm = document.getElementById("goalForm");
    if (goalForm) {
        goalForm.addEventListener("submit", function(e) {
            e.preventDefault();

            const member = document.getElementById("goalMember").value;
            const goal = document.getElementById("goalAmount").value;
            const description = document.getElementById("goalDescription").value;

            const formData = new FormData();
            formData.append("action", "update_goal");
            formData.append("member", member);
            formData.append("goal", goal);
            formData.append("description", description);

            fetch("../actions/contribution.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showSnackbar(data.message, data.success ? "success" : "error");
                if (data.success) {
                    closeGoalModal();
                    setTimeout(() => location.reload(), 1000);
                }
            })
            .catch(err => {
                console.error(err);
                showSnackbar("Something went wrong", "error");
            });
        });
    }
}

function initializeContributionsPage() {
    // Animate progress bars
    setTimeout(() => {
        document.querySelectorAll('.progress-fill').forEach(fill => {
            const width = fill.style.width;
            fill.style.width = '0%';
            setTimeout(() => {
                fill.style.width = width;
            }, 100);
        });
    }, 500);
}

// Add this new function
function checkForAppliedFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilters = urlParams.has('member') || urlParams.has('dateFrom') || urlParams.has('dateTo') || urlParams.has('amountRange');
    
    if (hasFilters) {
        let filterMessage = 'Filters applied';
        
        // Create more specific message based on filters
        const appliedFilters = [];
        if (urlParams.get('member')) appliedFilters.push('member');
        if (urlParams.get('dateFrom') || urlParams.get('dateTo')) appliedFilters.push('date range');
        if (urlParams.get('amountRange')) appliedFilters.push('amount range');
        
        if (appliedFilters.length > 0) {
            filterMessage = `Filtered by: ${appliedFilters.join(', ')}`;
        }
        
        // Show snackbar after a short delay to ensure page is loaded
        setTimeout(() => {
            showSnackbar(filterMessage, 'success');
        }, 500);
    }
}

function setupEventListeners() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    document.addEventListener('click', (e) => {
        if (
            window.innerWidth <= 1024 &&
            sidebar?.classList.contains('open') &&
            !sidebar.contains(e.target) &&
            !toggleBtn.contains(e.target)
        ) {
            sidebar.classList.remove('open');
        }
    });
}

// Separate function for contribution form submission
function setupContributionFormSubmission() {
    const form = document.getElementById("newContributionForm");
    
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const member = document.getElementById("newContributionMember").value;
            const amount = document.getElementById("newContributionAmount").value;
            const date = document.getElementById("newContributionDate").value;
            const note = document.getElementById("newContributionNote").value;

            // Validation
            if (!member || !amount || !date) {
                showSnackbar("Please fill all required fields.", 'error');
                return;
            }

            if (parseFloat(amount) <= 0) {
                showSnackbar("Amount must be greater than 0.", 'error');
                return;
            }

            // Show loading message
            showSnackbar("Adding contribution...", 'default');

            const formData = new FormData();
            formData.append("action", "add_contribution");
            formData.append("member", member);
            formData.append("amount", amount);
            formData.append("date", date);
            formData.append("note", note);

            fetch("../actions/contribution.php", {
                method: "POST",
                body: formData,
            })
                .then((res) => {
                    if (!res.ok) {
                        throw new Error(`HTTP error! status: ${res.status}`);
                    }
                    return res.json();
                })
                .then((data) => {
                    if (data.success) {
                        showSnackbar(data.message, 'success');
                        form.reset();
                        closeContributionModal();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showSnackbar(data.message, 'error');
                    }
                })
                .catch((err) => {
                    console.error(err);
                    showSnackbar("Something went wrong. Please try again.", 'error');
                });
        });
    }
}

function createMemberComparisonChart() {
    const ctx = document.getElementById('memberComparisonChart');
    if (!ctx) {
        console.error('Chart canvas element not found');
        return;
    }

    if (typeof chartData === 'undefined') {
        console.error('chartData is not defined');
        return;
    }

    console.log('Chart data:', chartData);

    // Destroy existing chart if it exists
    if (window.memberChart) {
        window.memberChart.destroy();
    }

    try {
        window.memberChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    label: 'This Month',
                    data: chartData.thisMonth || [],
                    backgroundColor: '#1e293b',
                    borderRadius: 8,
                    borderWidth: 0
                }, {
                    label: 'Goal',
                    data: chartData.goals || [],
                    backgroundColor: '#e2e8f0',
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₵' + value;
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    bar: {
                        borderRadius: 8
                    }
                }
            }
        });
        console.log('Chart created successfully');
    } catch (error) {
        console.error('Error creating chart:', error);
    }
}

function editGoal(memberName) {
    // Find the member data to get current goal
    const memberData = chartData.labels.map((label, index) => ({
        name: label,
        goal: chartData.goals[index]
    }));
    
    const member = memberData.find(m => m.name === memberName);
    const currentGoal = member ? member.goal : 0;
    
    document.getElementById('goalMember').value = memberName;
    document.getElementById('goalAmount').value = currentGoal;
    document.getElementById('goalModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeGoalModal() {
    document.getElementById('goalModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('goalForm').reset();
}

function showContributionModal() {
    document.getElementById('contributionModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeContributionModal() {
    document.getElementById('contributionModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('newContributionForm').reset();
}

function handleGoalSubmit(e) {
    e.preventDefault();
    
    const member = document.getElementById('goalMember').value;
    const amount = document.getElementById('goalAmount').value;
    
    showSnackbar(`Monthly goal updated for ${member}: ₵${amount}`, 'success');
    closeGoalModal();
}

function editContribution(id) {
    showSnackbar('Edit contribution feature coming soon', 'warning');
}

function showGoalsModal() {
    showSnackbar('Bulk goal setting feature coming soon', 'warning');
}

function exportContributions() {
    const data = {
        contributions: [],
        goals: {},
        exportDate: new Date().toISOString()
    };
    
    // Use actual chart data for goals
    if (typeof chartData !== 'undefined') {
        chartData.labels.forEach((label, index) => {
            data.goals[label] = chartData.goals[index];
        });
    }
    
    // Collect table data
    const rows = document.querySelectorAll('#contributionsTableBody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            data.contributions.push({
                member: cells[0].textContent.trim(),
                amount: cells[1].textContent.trim(),
                date: cells[2].textContent.trim(),
                note: cells[3].textContent.trim()
            });
        }
    });
    
    const dataStr = JSON.stringify(data, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `nkansah-family-contributions-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    showSnackbar('Contributions data exported successfully', 'success');
}

function applyFilters() {
    const member = document.getElementById('filterMember').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const amountRange = document.getElementById('filterAmount').value;

    const params = new URLSearchParams({
        member,
        dateFrom,
        dateTo,
        amountRange
    });

    window.location.href = `contribution.php?${params.toString()}`;
}

function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        showSnackbar('Signing out...', 'warning');
        setTimeout(() => {
            window.location.href = '../actions/signout';
        }, 1500);
    }
}

// Unified Snackbar functionality
function showSnackbar(message, type = 'default') {
    const snackbar = document.getElementById('snackbar');
    if (!snackbar) {
        console.error('Snackbar element not found');
        return;
    }
    
    snackbar.textContent = message;
    snackbar.className = 'show';

    if (type !== 'default') {
        snackbar.classList.add(type); // success, error, warning
    }

    setTimeout(() => {
        snackbar.className = snackbar.className.replace('show', '');
        snackbar.classList.remove('success', 'error', 'warning');
    }, 3000);
}

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    const goalModal = document.getElementById('goalModal');
    const contributionModal = document.getElementById('contributionModal');
    
    if (e.target === goalModal) {
        closeGoalModal();
    }
    if (e.target === contributionModal) {
        closeContributionModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeGoalModal();
        closeContributionModal();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        showContributionModal();
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        document.getElementById('sidebar').classList.remove('open');
    }
    
    // Resize chart if it exists
    if (window.memberChart) {
        window.memberChart.resize();
    }
});