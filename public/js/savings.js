// Savings Page JavaScript
class SavingsManager {
    constructor() {
        this.goals = [
            {
                id: 'emergency',
                name: 'Emergency Fund',
                icon: '🚨',
                description: '6 months of expenses',
                currentAmount: 8500,
                targetAmount: 15000,
                monthlyTarget: 500,
                startDate: '2024-01-01',
                targetDate: '2025-12-31',
                frequency: 'monthly',
                status: 'active',
                category: 'emergency'
            },
            {
                id: 'vacation',
                name: 'Vacation Fund',
                icon: '🏖️',
                description: 'Dream trip to Dubai',
                currentAmount: 2200,
                targetAmount: 5000,
                monthlyTarget: 150,
                startDate: '2024-11-01',
                targetDate: '2025-08-31',
                frequency: 'bi-weekly',
                status: 'active',
                category: 'vacation'
            },
            {
                id: 'car',
                name: 'Car Fund',
                icon: '🚗',
                description: 'Down payment for new car',
                currentAmount: 800,
                targetAmount: 10000,
                monthlyTarget: 400,
                startDate: '2024-12-01',
                targetDate: '2026-12-31',
                frequency: 'monthly',
                status: 'active',
                category: 'car'
            },
            {
                id: 'laptop',
                name: 'MacBook Pro',
                icon: '💻',
                description: 'Completed Dec 2024',
                currentAmount: 4500,
                targetAmount: 4500,
                monthlyTarget: 600,
                startDate: '2024-05-01',
                targetDate: '2024-12-31',
                frequency: 'monthly',
                status: 'completed',
                category: 'laptop'
            }
        ];
        
        this.autoSaveSettings = {
            salaryAutoSave: {
                enabled: true,
                percentage: 20,
                amount: 700,
                distribution: {
                    emergency: 500,
                    vacation: 150,
                    car: 50
                }
            },
            roundUpSave: {
                enabled: true,
                roundUpTo: 5,
                maxPerTransaction: 10,
                destinationGoal: 'emergency',
                monthlyTotal: 23.50
            },
            weeklySave: {
                enabled: false,
                amount: 20,
                day: 'monday',
                destinationGoal: 'vacation'
            }
        };
        
        this.activities = [
            {
                id: 1,
                type: 'deposit',
                goalId: 'emergency',
                goalName: 'Emergency Fund',
                amount: 500,
                description: 'Auto-save from salary',
                date: new Date(),
                source: 'auto'
            },
            {
                id: 2,
                type: 'deposit',
                goalId: 'vacation',
                goalName: 'Vacation Fund',
                amount: 150,
                description: 'Manual deposit',
                date: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
                source: 'manual'
            },
            {
                id: 3,
                type: 'roundup',
                goalId: 'emergency',
                goalName: 'Round-up Savings',
                amount: 8.50,
                description: 'Weekly collection',
                date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
                source: 'roundup'
            }
        ];
        
        this.init();
    }
    
    init() {
        this.updateProgressCircles();
        this.setupEventListeners();
        this.setupFilterButtons();
        this.setupAutoSaveToggles();
        this.updateAutoSaveSettings();
        this.animateProgressBars();
    }
    
    updateProgressCircles() {
        const circles = document.querySelectorAll('.progress-circle');
        circles.forEach(circle => {
            const percentage = parseInt(circle.getAttribute('data-percentage'));
            const circumference = 2 * Math.PI * 35; // radius = 35 for 80px circle
            const strokeDasharray = `${(percentage / 100) * circumference} ${circumference}`;
            
            // Create animated progress
            setTimeout(() => {
                circle.style.background = `conic-gradient(var(--primary-color) 0% ${percentage}%, #f1f5f9 ${percentage}% 100%)`;
            }, 500);
        });
    }
    
    animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 300);
        });
    }
    
    setupEventListeners() {
        // Goal menu toggles
        document.querySelectorAll('.menu-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleGoalMenu(btn);
            });
        });
        
        // Close menus when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.goal-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        });
        
        // Auto-save sliders
        const salarySlider = document.getElementById('salaryPercentage');
        if (salarySlider) {
            salarySlider.addEventListener('input', (e) => {
                this.updateSalaryPercentage(e.target.value);
            });
        }
        
        // Modal form submissions
        const newGoalForm = document.querySelector('#newGoalModal .modal-form');
        if (newGoalForm) {
            newGoalForm.addEventListener('submit', (e) => this.createNewGoal(e));
        }
        
        const depositForm = document.querySelector('#depositModal .modal-form');
        if (depositForm) {
            depositForm.addEventListener('submit', (e) => this.addDeposit(e));
        }
        
        const autoSaveForm = document.querySelector('#autoSaveModal .modal-form');
        if (autoSaveForm) {
            autoSaveForm.addEventListener('submit', (e) => this.updateAutoSave(e));
        }
    }
    
    setupFilterButtons() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.filterGoals(btn.getAttribute('data-filter'));
            });
        });
    }
    
    filterGoals(filter) {
        const goalCards = document.querySelectorAll('.goal-card');
        goalCards.forEach(card => {
            const status = card.getAttribute('data-status');
            if (filter === 'all' || status === filter) {
                card.style.display = 'block';
                card.classList.remove('hidden');
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, 50);
            } else {
                card.classList.add('hidden');
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    }
    
    setupAutoSaveToggles() {
        const toggles = document.querySelectorAll('input[type="checkbox"]');
        toggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                this.handleAutoSaveToggle(e.target);
            });
        });
    }
    
    handleAutoSaveToggle(toggle) {
        const toggleId = toggle.id;
        const isEnabled = toggle.checked;
        
        // Update settings
        switch(toggleId) {
            case 'salaryAutoSave':
            case 'configSalaryAutoSave':
                this.autoSaveSettings.salaryAutoSave.enabled = isEnabled;
                break;
            case 'roundUpSave':
            case 'configRoundUpSave':
                this.autoSaveSettings.roundUpSave.enabled = isEnabled;
                break;
            case 'weeklySave':
            case 'configWeeklySave':
                this.autoSaveSettings.weeklySave.enabled = isEnabled;
                break;
        }
        
        this.updateAutoSaveSettings();
    }
}