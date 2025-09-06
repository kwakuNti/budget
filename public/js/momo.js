// MoMo Account Management JavaScript - FIXED VERSION
document.addEventListener('DOMContentLoaded', function() {
    
    initializeMoMoPage();
    setupEventListeners();
});

let selectedNetwork = null;

function initializeMoMoPage() {
    // Update UI with current data
    if (window.momoData && window.momoData.account) {
        updateAccountDisplay();
        updateStatsDisplay();
    }
    
    // Set today's date as default for any date inputs
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });

    // Initialize phone number formatting
    initializePhoneFormatting();
    
}

const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});

function setupEventListeners() {
    // Payment request form
    const paymentForm = document.getElementById('paymentRequestForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentRequest);
    }
    
    // Change number form
    const changeNumberForm = document.getElementById('changeNumberForm');
    if (changeNumberForm) {
        changeNumberForm.addEventListener('submit', handleChangeNumber);
    }
    
    // Setup MoMo form
    const setupForm = document.getElementById('setupMoMoForm');
    if (setupForm) {
        setupForm.addEventListener('submit', handleMoMoSetup);
    }
    
    // Network selection
    document.querySelectorAll('.network-option-modal').forEach(option => {
        option.addEventListener('click', handleNetworkSelection);
    });
    
    // Refresh balance button
    const refreshBalanceBtn = document.getElementById('refreshBalanceBtn');
    if (refreshBalanceBtn) {
        refreshBalanceBtn.addEventListener('click', refreshBalance);
    }
    
    // Load more requests button
    const loadMoreBtn = document.querySelector('.load-more button');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreRequests);
    }
    
    // FIXED: Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target);
        }
    });
    
    // Handle escape key to close modals
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
    
    // Handle network grid clicks
    const networkOptions = document.querySelectorAll('.network-option[data-network]');
    networkOptions.forEach(option => {
        option.addEventListener('click', function() {
            const network = this.dataset.network;
            
            if (!isNetworkAvailable(network)) {
                const networkName = this.querySelector('.network-name')?.textContent || network.toUpperCase();
                showSnackbar(`${networkName} will be available soon! We're working hard to bring you more options.`, 'info');
            } else {
                showNetworkModal();
            }
        });
    });
}

// FIXED: Proper sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

function updateAccountDisplay() {
    const account = window.momoData.account;
    if (!account) return;
    
    const currentNumber = document.getElementById('currentNumber');
    const momoBalance = document.getElementById('momoBalance');
    
    if (currentNumber) {
        currentNumber.textContent = formatPhoneNumber(account.phone_number);
    }
    
    if (momoBalance) {
        momoBalance.textContent = parseFloat(account.balance || 0).toFixed(2);
    }
}

function updateStatsDisplay() {
    const stats = window.momoData.stats;
    if (!stats) return;
    
    const totalRequests = document.getElementById('totalRequests');
    const totalReceived = document.getElementById('totalReceived');
    const pendingRequests = document.getElementById('pendingRequests');
    
    if (totalRequests) {
        totalRequests.textContent = stats.total_requests || 0;
    }
    
    if (totalReceived) {
        totalReceived.textContent = '₵' + (parseFloat(stats.total_received || 0)).toFixed(0);
    }
    
    if (pendingRequests) {
        pendingRequests.textContent = stats.pending_requests || 0;
    }
}

// Modal Functions
function showRequestModal() {
    const modal = document.getElementById('requestModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Load members if needed
        if (!window.momoData.familyMembers || window.momoData.familyMembers.length === 0) {
            loadMembersForRequest();
        } else {
            populateMemberCheckboxes(window.momoData.familyMembers);
        }
    }
}

function closeModal(modal) {
    if (!modal) return;
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset forms in the modal
    const forms = modal.querySelectorAll('form');
    forms.forEach(form => form.reset());
    
    // Reset global variables
    if (modal.id === 'networkModal') {
        selectedNetwork = null;
        const form = document.getElementById('networkSwitchForm');
        if (form) {
            form.style.display = 'none';
        }
    }
}

function closeRequestModal() {
    const modal = document.getElementById('requestModal');
    closeModal(modal);
}

function showNetworkModal() {
    const modal = document.getElementById('networkModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Hide the form initially
        const form = document.getElementById('networkSwitchForm');
        if (form) {
            form.style.display = 'none';
        }
    }
}

function closeNetworkModal() {
    const modal = document.getElementById('networkModal');
    closeModal(modal);
}

function showNumberModal() {
    const modal = document.getElementById('numberModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeNumberModal() {
    const modal = document.getElementById('numberModal');
    closeModal(modal);
}

function showSetupModal() {
    const modal = document.getElementById('setupModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeSetupModal() {
    const modal = document.getElementById('setupModal');
    closeModal(modal);
}

// Network Selection Handler
function handleNetworkSelection(event) {
    const networkOption = event.currentTarget;
    const network = networkOption.dataset.network;
    
    // Check if coming soon
    if (networkOption.classList.contains('coming-soon') || !isNetworkAvailable(network)) {
        const networkName = networkOption.querySelector('span')?.textContent || network.toUpperCase();
        showSnackbar(`${networkName} will be available soon! We're working hard to bring you more options.`, 'info');
        return;
    }
    
    selectedNetwork = network;
    
    // Show the form for available networks
    const form = document.getElementById('networkSwitchForm');
    if (form) {
        form.style.display = 'block';
    }
}

// Helper function to check network availability
function isNetworkAvailable(network) {
    const availableNetworks = ['mtn']; // Only MTN is available for now
    return availableNetworks.includes(network.toLowerCase());
}

// Load members for payment request
async function loadMembersForRequest() {
    try {
        showLoadingState(true);
        
        const response = await fetch('../actions/momo_operations.php?operation=get_members');
        const result = await response.json();
        
        if (result.success) {
            window.momoData.familyMembers = result.members;
            populateMemberCheckboxes(result.members);
        } else {
            showSnackbar(result.message || 'Failed to load members', 'error');
        }
    } catch (error) {
        console.error('Error loading members:', error);
        showSnackbar('Failed to load members. Please try again.', 'error');
    } finally {
        showLoadingState(false);
    }
}

// Populate member checkboxes with proper format
function populateMemberCheckboxes(members) {
    const memberCheckboxes = document.querySelector('.member-checkboxes');
    if (!memberCheckboxes) return;
    
    // Don't clear if already populated
    if (memberCheckboxes.children.length > 0) {
        return;
    }
    
    members.forEach(member => {
        const memberId = `${member.member_type}_${member.member_id}`;
        const displayName = member.display_name || member.full_name;
        
        const checkboxItem = document.createElement('label');
        checkboxItem.className = 'checkbox-item';
        
        checkboxItem.innerHTML = `
            <input type="checkbox" name="members" value="${memberId}" data-phone="${escapeHtml(member.phone_number)}">
            <span class="checkmark"></span>
            <div class="member-details">
                <span class="name">${escapeHtml(displayName)}</span>
                <span class="phone">${formatPhoneNumber(member.phone_number)}</span>
            </div>
        `;
        
        memberCheckboxes.appendChild(checkboxItem);
    });
}

// Payment Request Handler
async function handlePaymentRequest(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData();
    
    // Get selected members
    const selectedMembers = [];
    const memberCheckboxes = form.querySelectorAll('input[name="members"]:checked');
    memberCheckboxes.forEach(checkbox => {
        selectedMembers.push(checkbox.value);
    });
    
    if (selectedMembers.length === 0) {
        showSnackbar('Please select at least one member', 'error');
        return;
    }
    
    const amount = parseFloat(document.getElementById('requestAmount').value);
    const purpose = document.getElementById('requestPurpose').value.trim();
    const sendSMS = document.getElementById('sendSMS').checked;
    
    // Enhanced validation
    if (!amount || amount <= 0) {
        showSnackbar('Please enter a valid amount greater than 0', 'error');
        return;
    }
    
    if (amount > 10000) {
        showSnackbar('Amount cannot exceed ₵10,000', 'error');
        return;
    }
    
    if (!purpose) {
        showSnackbar('Please enter a purpose for the request', 'error');
        return;
    }
    
    if (purpose.length < 3) {
        showSnackbar('Purpose must be at least 3 characters long', 'error');
        return;
    }
    
    // Prepare form data
    formData.append('operation', 'send_payment_request');
    selectedMembers.forEach(member => {
        formData.append('members[]', member);
    });
    formData.append('amount', amount.toString());
    formData.append('purpose', purpose);
    formData.append('send_sms', sendSMS ? 'true' : 'false');
    
    try {
        showLoadingState(true);
        
        const response = await fetch('../actions/momo_operations.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar(result.message, 'success');
            closeRequestModal();
            
            // Refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showSnackbar(result.message, 'error');
        }
    } catch (error) {
        console.error('Error sending payment request:', error);
        showSnackbar('Failed to send payment request. Please check your connection and try again.', 'error');
    } finally {
        showLoadingState(false);
    }
}

// Refresh Balance
async function refreshBalance() {
    const refreshBtn = document.getElementById('refreshBalanceBtn');
    
    try {
        showLoadingState(true);
        
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.textContent = 'Refreshing...';
        }
        
        const response = await fetch('../actions/momo_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'operation=refresh_balance'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Update balance display
            const balanceElement = document.getElementById('momoBalance');
            if (balanceElement && result.balance !== undefined) {
                balanceElement.textContent = parseFloat(result.balance).toFixed(2);
            }
            
            // Update stats if provided
            if (result.stats) {
                window.momoData.stats = result.stats;
                updateStatsDisplay();
            }
            
            // Update account info if provided
            if (result.account) {
                window.momoData.account = result.account;
                updateAccountDisplay();
            }
            
            showSnackbar('Balance refreshed successfully', 'success');
        } else {
            showSnackbar(result.message, 'error');
        }
    } catch (error) {
        console.error('Error refreshing balance:', error);
        showSnackbar('Failed to refresh balance. Please check your connection and try again.', 'error');
    } finally {
        showLoadingState(false);
        
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.textContent = '🔄 Refresh Balance';
        }
    }
}

// Change Number Handler
async function handleChangeNumber(event) {
    event.preventDefault();
    
    const newNumber = document.getElementById('newPhoneNumber').value.trim();
    const pin = document.getElementById('confirmPin').value.trim();
    
    if (!newNumber || !pin) {
        showSnackbar('Please fill in all fields', 'error');
        return;
    }
    
    // FIXED: More lenient phone number validation
    const cleanNumber = cleanPhoneNumber(newNumber);
    if (!isValidGhanaianNumber(cleanNumber)) {
        showSnackbar('Please enter a valid Ghanaian phone number', 'error');
        return;
    }
    
    if (pin.length < 4) {
        showSnackbar('PIN must be at least 4 characters', 'error');
        return;
    }
    
    // Check if number is the same as current
    if (window.momoData.account && cleanNumber === window.momoData.account.phone_number) {
        showSnackbar('This is already your current phone number', 'error');
        return;
    }
    
    try {
        showLoadingState(true);
        
        const formData = new FormData();
        formData.append('operation', 'change_number');
        formData.append('phone_number', cleanNumber);
        formData.append('pin', pin);
        
        const response = await fetch('../actions/momo_operations.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar(result.message, 'success');
            closeNumberModal();
            
            // Update display
            const currentNumber = document.getElementById('currentNumber');
            if (currentNumber) {
                currentNumber.textContent = formatPhoneNumber(cleanNumber);
            }
            
            // Update stored data
            if (window.momoData.account) {
                window.momoData.account.phone_number = cleanNumber;
            }
        } else {
            showSnackbar(result.message, 'error');
        }
    } catch (error) {
        console.error('Error changing number:', error);
        showSnackbar('Failed to change number. Please check your connection and try again.', 'error');
    } finally {
        showLoadingState(false);
    }
}

// Network Switch Confirmation
async function confirmNetworkSwitch() {
    if (!selectedNetwork) {
        showSnackbar('Please select a network first', 'error');
        return;
    }
    
    const newNumber = document.getElementById('switchPhoneNumber').value.trim();
    
    if (!newNumber) {
        showSnackbar('Please enter a phone number', 'error');
        return;
    }
    
    // FIXED: More lenient phone number validation
    const cleanNumber = cleanPhoneNumber(newNumber);
    if (!isValidGhanaianNumber(cleanNumber)) {
        showSnackbar('Please enter a valid Ghanaian phone number', 'error');
        return;
    }
    
    // Check if trying to switch to the same network and number
    if (window.momoData.account && 
        selectedNetwork === window.momoData.account.network && 
        cleanNumber === window.momoData.account.phone_number) {
        showSnackbar('You are already using this network and phone number', 'error');
        return;
    }
    
    try {
        showLoadingState(true);
        
        const formData = new FormData();
        formData.append('operation', 'switch_network');
        formData.append('network', selectedNetwork);
        formData.append('phone_number', cleanNumber);
        
        const response = await fetch('../actions/momo_operations.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar(result.message, 'success');
            closeNetworkModal();
            
            // Refresh page to show new network
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showSnackbar(result.message, 'error');
        }
    } catch (error) {
        console.error('Error switching network:', error);
        showSnackbar('Failed to switch network. Please check your connection and try again.', 'error');
    } finally {
        showLoadingState(false);
    }
}

// MoMo Setup Handler
async function handleMoMoSetup(event) {
    event.preventDefault();
    
    const phoneNumber = document.getElementById('setupPhoneNumber').value.trim();
    const accountName = document.getElementById('setupAccountName').value.trim();
    
    if (!phoneNumber || !accountName) {
        showSnackbar('Please fill in all fields', 'error');
        return;
    }
    
    // FIXED: More lenient phone number validation
    const cleanNumber = cleanPhoneNumber(phoneNumber);
    if (!isValidGhanaianNumber(cleanNumber)) {
        showSnackbar('Please enter a valid Ghanaian phone number', 'error');
        return;
    }
    
    if (accountName.length < 3) {
        showSnackbar('Account name must be at least 3 characters long', 'error');
        return;
    }
    
    if (accountName.length > 50) {
        showSnackbar('Account name cannot exceed 50 characters', 'error');
        return;
    }
    
    try {
        showLoadingState(true);
        
        const formData = new FormData();
        formData.append('operation', 'setup_momo');
        formData.append('phone_number', cleanNumber);
        formData.append('account_name', accountName);
        formData.append('network', 'mtn');
        
        const response = await fetch('../actions/momo_operations.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showSnackbar('MoMo account setup successfully!', 'success');
            closeSetupModal();
            
            // Update window data if account is returned
            if (result.account) {
                window.momoData.account = result.account;
            }
            
            // Refresh the page after a short delay to show new account
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showSnackbar(result.message, 'error');
        }
    } catch (error) {
        console.error('Error setting up MoMo:', error);
        showSnackbar('Failed to setup MoMo account. Please check your connection and try again.', 'error');
    } finally {
        showLoadingState(false);
    }
}

// Load More Requests
async function loadMoreRequests() {
    try {
        showLoadingState(true);
        
        const currentItems = document.querySelectorAll('.request-item').length;
        const response = await fetch(`../actions/momo_operations.php?operation=get_recent_requests&limit=10&offset=${currentItems}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.requests && result.requests.length > 0) {
            const requestsList = document.getElementById('requestsList');
            
            result.requests.forEach(request => {
                const requestElement = createRequestElement(request);
                requestsList.appendChild(requestElement);
            });
            
            // Hide load more button if no more requests
            if (result.requests.length < 10) {
                const loadMoreBtn = document.querySelector('.load-more');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'none';
                }
            }
        } else {
            const loadMoreBtn = document.querySelector('.load-more');
            if (loadMoreBtn) {
                loadMoreBtn.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading more requests:', error);
        showSnackbar('Failed to load more requests. Please check your connection.', 'error');
    } finally {
        showLoadingState(false);
    }
}

// Create Request Element
function createRequestElement(request) {
    const requestDate = new Date(request.requested_at);
    const now = new Date();
    const timeAgo = getTimeAgo(requestDate, now);
    
    const statusClass = request.status.toLowerCase();
    const statusText = request.status.charAt(0).toUpperCase() + request.status.slice(1);
    
    const requestItem = document.createElement('div');
    requestItem.className = 'request-item';
    requestItem.innerHTML = `
        <div class="request-member">
            <div class="member-avatar">${request.recipient_name.charAt(0).toUpperCase()}</div>
            <div class="member-info">
                <div class="member-name">${escapeHtml(request.recipient_name)}</div>
                <div class="member-phone">${formatPhoneNumber(request.recipient_phone)}</div>
            </div>
        </div>
        
        <div class="request-details">
            <div class="request-amount">₵${parseFloat(request.amount).toFixed(2)}</div>
            <div class="request-purpose">${escapeHtml(request.purpose)}</div>
        </div>
        
        <div class="request-status">
            <span class="status-badge ${statusClass}">${statusText}</span>
            <div class="request-time">${timeAgo}</div>
        </div>
    `;
    
    return requestItem;
}

// Helper Functions
function showLoadingState(loading) {
    const buttons = document.querySelectorAll('button:not(.no-loading)');
    buttons.forEach(button => {
        if (loading) {
            if (!button.disabled) {
                button.disabled = true;
                if (button.textContent && !button.dataset.originalText) {
                    button.dataset.originalText = button.textContent;
                    button.innerHTML = '<span class="loading-spinner">⏳</span> Loading...';
                }
            }
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
                delete button.dataset.originalText;
            }
        }
    });
}

function showSnackbar(message, type = 'info') {
    const snackbar = document.getElementById('snackbar');
    if (!snackbar) {
        console.warn('Snackbar element not found');
        return;
    }
    
    snackbar.textContent = message;
    snackbar.className = `snackbar show ${type}`;
    
    // Clear any existing timeout
    if (snackbar.timeout) {
        clearTimeout(snackbar.timeout);
    }
    
    snackbar.timeout = setTimeout(() => {
        snackbar.classList.remove('show');
    }, 4000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatPhoneNumber(phone) {
    if (!phone) return '';
    
    // Format +233XXXXXXXXX to +233 XX XXX XXXX
    if (phone.startsWith('+233') && phone.length === 13) {
        return `+233 ${phone.slice(4, 6)} ${phone.slice(6, 9)} ${phone.slice(9)}`;
    }
    return phone;
}

// FIXED: Enhanced phone number utilities with more lenient validation
function cleanPhoneNumber(phone) {
    if (!phone) return '';
    
    // Remove all non-digit characters
    let cleaned = phone.replace(/\D/g, '');
    
    // Handle different formats
    if (cleaned.startsWith('233') && cleaned.length === 12) {
        return '+' + cleaned;
    } else if (cleaned.startsWith('0') && cleaned.length === 10) {
        return '+233' + cleaned.slice(1);
    } else if (cleaned.length === 9) {
        return '+233' + cleaned;
    } else if (cleaned.length === 10 && !cleaned.startsWith('0')) {
        return '+233' + cleaned;
    }
    
    // If already has +233 prefix, keep as is
    if (phone.startsWith('+233') && phone.length === 13) {
        return phone;
    }
    
    // Default case - prefix with +233
    return '+233' + cleaned;
}
// FIXED: More lenient Ghanaian number validation
function isValidGhanaianNumber(phone) {
    if (!phone) return false;

    const cleaned = cleanPhoneNumber(phone);
    
    // Must be in +233XXXXXXXXX format
    if (!cleaned.startsWith('+233')) return false;
    if (cleaned.length !== 13) return false;

    const numberPart = cleaned.slice(4);
    return /^\d{9}$/.test(numberPart);
}


function getTimeAgo(date, now) {
    const diff = Math.floor((now - date) / 1000); // difference in seconds
    
    if (diff < 60) {
        return 'Just now';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return minutes + ' minute' + (minutes !== 1 ? 's' : '') + ' ago';
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return hours + ' hour' + (hours !== 1 ? 's' : '') + ' ago';
    } else if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        return days + ' day' + (days !== 1 ? 's' : '') + ' ago';
    } else {
        return date.toLocaleDateString();
    }
}

// Sign Out Function
function signOut() {
    if (confirm('Are you sure you want to sign out?')) {
        fetch('../actions/auth_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'operation=logout'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.href = '../login';
            } else {
                showSnackbar('Failed to sign out. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Sign out error:', error);
            // Force redirect even if there's an error
            window.location.href = '../views/login.php';
        });
    }
}

// Phone number formatting for inputs
function formatPhoneInput(input) {
    let value = input.value.replace(/\D/g, ''); // Remove non-digits
    
    if (value.startsWith('233')) {
        value = '+' + value;
    } else if (value.startsWith('0')) {
        value = '+233' + value.slice(1);
    } else if (value.length > 0 && !value.startsWith('+233')) {
        value = '+233' + value;
    }
    
    // Limit to 13 characters (+233XXXXXXXXX)
    if (value.length > 13) {
        value = value.slice(0, 13);
    }
    
    input.value = value;
}

// Initialize phone number formatting
function initializePhoneFormatting() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneInput(this);
        });
        
        input.addEventListener('blur', function() {
            // Validate on blur
            const phoneRegex = /^\+233[0-9]{9}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.setCustomValidity('Please enter a valid Ghanaian phone number');
                this.classList.add('error');
            } else {
                this.setCustomValidity('');
                this.classList.remove('error');
            }
        });
    });
}

// Auto-refresh stats every 30 seconds (only if account exists)
setInterval(async function() {
    if (window.momoData && window.momoData.account) {
        try {
            const response = await fetch('../actions/momo_operations.php?operation=get_momo_stats');
            const result = await response.json();
            
            if (result.success) {
                window.momoData.stats = result.stats;
                if (result.account) {
                    window.momoData.account = result.account;
                    updateAccountDisplay();
                }
                updateStatsDisplay();
            }
        } catch (error) {
        }
    }
}, 30000); // 30 seconds

// Handle responsive sidebar


// Add error handling for network requests
window.addEventListener('online', function() {
    showSnackbar('Connection restored', 'success');
});

window.addEventListener('offline', function() {
    showSnackbar('You are offline. Some features may not work.', 'warning');
});

// Additional utility functions for better UX

// Validate form before submission
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// Format currency input
function formatCurrencyInput(input) {
    let value = input.value.replace(/[^\d.]/g, ''); // Remove non-numeric characters except decimal
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Limit to 2 decimal places
    if (parts[1] && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }
    
    // Convert to number and back to limit precision
    const numValue = parseFloat(value);
    if (!isNaN(numValue)) {
        input.value = numValue.toString();
    } else {
        input.value = '';
    }
}

// Initialize currency formatting for amount inputs
function initializeCurrencyFormatting() {
    const amountInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    amountInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatCurrencyInput(this);
        });
        
        input.addEventListener('blur', function() {
            // Format to 2 decimal places on blur
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });
}

// Handle form submission loading states
function handleFormSubmissionState(formId, isSubmitting) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const submitButton = form.querySelector('button[type="submit"]');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    if (isSubmitting) {
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.dataset.originalText = submitButton.textContent;
            submitButton.innerHTML = '<span class="loading-spinner">⏳</span> Processing...';
        }
        
        inputs.forEach(input => {
            input.disabled = true;
        });
    } else {
        if (submitButton) {
            submitButton.disabled = false;
            if (submitButton.dataset.originalText) {
                submitButton.textContent = submitButton.dataset.originalText;
                delete submitButton.dataset.originalText;
            }
        }
        
        inputs.forEach(input => {
            input.disabled = false;
        });
    }
}

// Check for updates periodically
async function checkForUpdates() {
    try {
        const response = await fetch('../actions/momo_operations.php?operation=get_recent_requests&limit=1');
        const result = await response.json();
        
        if (result.success && result.requests && result.requests.length > 0) {
            const latestRequest = result.requests[0];
            const currentLatest = document.querySelector('.request-item');
            
            if (currentLatest) {
                const currentId = currentLatest.dataset.requestId;
                if (currentId && latestRequest.id !== currentId) {
                    // New request found, show notification
                    showSnackbar('New payment request activity detected. Refresh to see updates.', 'info');
                }
            }
        }
    } catch (error) {
    }
}

// Run update check every 2 minutes
setInterval(checkForUpdates, 120000);

// Enhanced error handling for AJAX requests
function handleAjaxError(error, operation = 'operation') {
    console.error(`Error during ${operation}:`, error);
    
    if (error.name === 'TypeError' && error.message.includes('fetch')) {
        showSnackbar('Network error. Please check your internet connection.', 'error');
    } else if (error.message.includes('404')) {
        showSnackbar('Service not found. Please contact support.', 'error');
    } else if (error.message.includes('500')) {
        showSnackbar('Server error. Please try again later.', 'error');
    } else {
        showSnackbar(`Failed to complete ${operation}. Please try again.`, 'error');
    }
}

// Improved member selection handling
function handleMemberSelection() {
    const memberCheckboxes = document.querySelectorAll('input[name="members"]');
    const selectAllBtn = document.getElementById('selectAllMembers');
    const clearAllBtn = document.getElementById('clearAllMembers');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            memberCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    }
    
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            memberCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    }
    
    // Update selection count
    function updateSelectionCount() {
        const selectedCount = document.querySelectorAll('input[name="members"]:checked').length;
        const countDisplay = document.getElementById('selectedCount');
        if (countDisplay) {
            countDisplay.textContent = `${selectedCount} member(s) selected`;
        }
    }
    
    memberCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionCount);
    });
    
    updateSelectionCount();
}

// Initialize member selection handling when modal opens
function initializeMemberSelection() {
    setTimeout(handleMemberSelection, 100); // Small delay to ensure DOM is ready
}

// Add to the showRequestModal function
const originalShowRequestModal = showRequestModal;
showRequestModal = function() {
    originalShowRequestModal();
    initializeMemberSelection();
};

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    // Ctrl/Cmd + R to refresh balance
    if ((event.ctrlKey || event.metaKey) && event.key === 'r' && window.momoData?.account) {
        event.preventDefault();
        refreshBalance();
    }
    
    // Ctrl/Cmd + N to open new payment request
    if ((event.ctrlKey || event.metaKey) && event.key === 'n') {
        event.preventDefault();
        showRequestModal();
    }
});

// Initialize additional features when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeCurrencyFormatting();
    
    // Add tooltips for better UX
    addTooltips();
    
    // Initialize smooth scrolling
    initializeSmoothScrolling();
});

// Add tooltips for better user experience
function addTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            this.tooltipElement = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltipElement) {
                document.body.removeChild(this.tooltipElement);
                this.tooltipElement = null;
            }
        });
    });
}

// Initialize smooth scrolling for internal links
function initializeSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Performance optimization: Debounce function for search/filter operations
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add search functionality for member selection
function initializeMemberSearch() {
    const searchInput = document.getElementById('memberSearch');
    if (!searchInput) return;
    
    const debouncedSearch = debounce(function(searchTerm) {
        const memberItems = document.querySelectorAll('.checkbox-item');
        
        memberItems.forEach(item => {
            const memberName = item.querySelector('.name')?.textContent.toLowerCase() || '';
            const memberPhone = item.querySelector('.phone')?.textContent.toLowerCase() || '';
            
            if (memberName.includes(searchTerm) || memberPhone.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }, 300);
    
    searchInput.addEventListener('input', function() {
        debouncedSearch(this.value.toLowerCase());
    });
}

