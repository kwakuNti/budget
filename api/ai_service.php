<?php
// ai_service.php - AI service for chat and predictive insights
class AIService {
    private $apiUrl;
    private $apiKey;
    private $model;
    
    public function __construct() {
        // Use local Ollama instance with the installed model
        $this->apiUrl = 'http://localhost:11434/api/generate';
        $this->model = 'llama3.2:1b'; // The lightweight model we just installed
        $this->apiKey = null; // Not needed for local Ollama
        
        // Alternative configurations for other services:
        // For OpenAI: $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
        // For Groq: $this->apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    }
    
    private function getConnection() {
        require_once __DIR__ . '/../config/connection.php';
        global $connection;
        return $connection;
    }
    
    public function generateChatResponse($userQuery, $financialData) {
        $context = $this->prepareFinancialContext($financialData);
        
        $prompt = "You are an expert personal financial advisor AI with complete access to this user's comprehensive financial data from ALL sections of their budget app.\n\n";
        $prompt .= $context . "\n\n";
        $prompt .= "USER QUESTION: \"" . $userQuery . "\"\n\n";
        $prompt .= "ULTRA-PERSONALIZED RESPONSE REQUIREMENTS:\n";
        $prompt .= "1. Reference SPECIFIC dollar amounts from their actual transactions and data\n";
        $prompt .= "2. Use their ACTUAL spending patterns, recent transactions, and goal progress\n";
        $prompt .= "3. Provide CONCRETE action steps with specific amounts and realistic timelines\n";
        $prompt .= "4. Reference their actual categories, goal names, and spending habits by name\n";
        $prompt .= "5. Give personalized advice that considers their unique monthly income and expenses\n";
        $prompt .= "6. Be encouraging but realistic about their current financial health score\n";
        $prompt .= "7. Keep response under 300 words but make it highly specific and actionable\n\n";
        $prompt .= "RESPONSE STYLE EXAMPLES:\n";
        $prompt .= "• 'Based on your recent \$XXX spending on [actual category] last month...'\n";
        $prompt .= "• 'Looking at your [specific goal name] with \$XXX saved toward your \$XXX target...'\n";
        $prompt .= "• 'Your current monthly surplus of \$XXX could be allocated to...'\n";
        $prompt .= "• 'Given your financial health score of XX%, I recommend focusing on...'\n\n";
        $prompt .= "Provide ultra-personalized financial advice that proves you understand their complete financial picture:";
        
        return $this->callAI($prompt);
    }
    
    public function generatePredictiveInsights($financialData) {
        $context = $this->prepareFinancialContext($financialData);
        
        $prompt = "You are a financial analyst AI with complete access to this user's comprehensive financial data from ALL sections of their budget app.\n\n";
        $prompt .= $context . "\n\n";
        $prompt .= "COMPREHENSIVE FINANCIAL ANALYSIS REQUIREMENTS:\n";
        $prompt .= "1. Use the EXACT numbers from their actual transactions, income, and spending data\n";
        $prompt .= "2. Calculate specific dollar amounts for all recommendations\n";
        $prompt .= "3. Reference their actual spending categories, goal names, and recent transaction patterns\n";
        $prompt .= "4. Predict realistic timelines for goals based on current saving rate and progress\n";
        $prompt .= "5. Identify the biggest opportunities using their specific spending data\n";
        $prompt .= "6. Use their actual monthly income and expense totals for calculations\n\n";
        $prompt .= "Generate 5 highly specific insights covering:\n";
        $prompt .= "• Spending optimization: Reference actual categories with highest spending and specific reduction amounts\n";
        $prompt .= "• Savings acceleration: Use their current savings rate and suggest specific monthly increases\n";
        $prompt .= "• Goal achievement predictions: Calculate exact timelines based on current progress and target dates\n";
        $prompt .= "• Budget adjustments: Reference their actual budget allocations vs spending patterns\n";
        $prompt .= "• Risk factors or opportunities: Based on their transaction history and trends\n\n";
        $prompt .= "FORMAT REQUIREMENTS:\n";
        $prompt .= "• Each insight should be 2-3 sentences with specific dollar amounts\n";
        $prompt .= "• Reference actual data points (dates, amounts, category names, goal names)\n";
        $prompt .= "• Include realistic action steps with specific targets\n";
        $prompt .= "• Make each insight unique and based on their personal financial profile\n\n";
        $prompt .= "Generate personalized insights that prove deep understanding of their complete financial situation:";
        
        return $this->callAI($prompt);
    }
    
    private function prepareFinancialContext($data) {
        $context = "=== USER'S COMPLETE FINANCIAL PROFILE (ALL APP DATA) ===\n\n";
        
        // User Profile Information
        if (isset($data['user_profile'])) {
            $profile = $data['user_profile'];
            $context .= "👤 USER PROFILE:\n";
            $context .= "• Name: {$profile['first_name']} {$profile['last_name']}\n";
            $context .= "• Member Since: " . date('F Y', strtotime($profile['created_at'])) . "\n";
            $context .= "• Account Type: " . ($profile['user_type'] ?? 'Standard') . "\n\n";
        }
        
        // Financial Health Overview
        if (isset($data['financial_health'])) {
            $health = $data['financial_health'];
            $context .= "💰 FINANCIAL HEALTH ANALYSIS:\n";
            $context .= "• Overall Score: " . ($health['health_score'] ?? 'N/A') . "/100\n";
            $context .= "• Monthly Income: $" . number_format($health['total_income'] ?? 0) . "\n";
            $context .= "• Monthly Expenses: $" . number_format($health['total_expenses'] ?? 0) . "\n";
            $context .= "• Net Savings: $" . number_format(($health['total_income'] ?? 0) - ($health['total_expenses'] ?? 0)) . "\n";
            $context .= "• Savings Rate: " . ($health['savings_rate'] ?? 0) . "% (Industry benchmark: 20%)\n";
            $context .= "• Emergency Fund: " . ($health['emergency_fund_months'] ?? 0) . " months coverage (Target: 3-6 months)\n\n";
        }
        
        // Recent Transaction History (Last 15 transactions for detailed context)
        if (!empty($data['recent_transactions'])) {
            $context .= "💳 RECENT TRANSACTION HISTORY (Last 15 transactions):\n";
            $recent = array_slice($data['recent_transactions'], 0, 15);
            foreach ($recent as $transaction) {
                $date = date('M j, Y', strtotime($transaction['expense_date']));
                $category = $transaction['category_name'] ?: 'Uncategorized';
                $context .= "• {$date}: -$" . number_format($transaction['amount'], 2) . " - {$transaction['description']} ({$category})\n";
            }
            $context .= "\n";
        }
        
        // Detailed Spending Patterns by Category
        if (isset($data['spending_patterns'])) {
            $spending = $data['spending_patterns'];
            $context .= "📊 DETAILED SPENDING BREAKDOWN:\n";
            $totalSpending = $spending['monthly_total'] ?? 0;
            
            if ($totalSpending > 0 && !empty($spending['by_category'])) {
                foreach ($spending['by_category'] as $category) {
                    $amount = $category['amount'];
                    $percentage = round(($amount / $totalSpending) * 100, 1);
                    $context .= "• {$category['category']}: $" . number_format($amount, 2) . " ({$percentage}% of total)\n";
                }
            }
            $context .= "• TOTAL MONTHLY SPENDING: $" . number_format($totalSpending, 2) . "\n\n";
        }
        
        // Monthly Spending Trends
        if (!empty($data['monthly_summaries'])) {
            $context .= "📈 MONTHLY SPENDING TRENDS (Last 6 months):\n";
            $recent_months = array_slice($data['monthly_summaries'], 0, 6);
            foreach ($recent_months as $month) {
                $month_name = date('M Y', strtotime($month['month'] . '-01'));
                $context .= "• {$month_name}: $" . number_format($month['total_spent'], 2) . 
                           " ({$month['transaction_count']} transactions, avg $" . number_format($month['avg_transaction'], 2) . ")\n";
            }
            $context .= "\n";
        }
        
        // Detailed Goals Status with Timeline Analysis
        if (!empty($data['detailed_goals'])) {
            $context .= "🎯 SAVINGS GOALS STATUS (Detailed Analysis):\n";
            foreach ($data['detailed_goals'] as $goal) {
                $progress = round($goal['progress_percentage'], 1);
                $remaining = $goal['target_amount'] - $goal['current_amount'];
                $days_left = $goal['days_remaining'];
                $target_date = date('M j, Y', strtotime($goal['target_date']));
                
                $context .= "• {$goal['goal_name']}:\n";
                $context .= "  - Progress: $" . number_format($goal['current_amount'], 2) . " / $" . number_format($goal['target_amount'], 2) . " ({$progress}% complete)\n";
                $context .= "  - Remaining: $" . number_format($remaining, 2) . "\n";
                $context .= "  - Target Date: {$target_date} ({$days_left} days remaining)\n";
                
                if ($days_left > 0) {
                    $daily_needed = $remaining / $days_left;
                    $weekly_needed = $daily_needed * 7;
                    $monthly_needed = $daily_needed * 30;
                    $context .= "  - Required Savings: $" . number_format($daily_needed, 2) . "/day, $" . number_format($weekly_needed, 2) . "/week, $" . number_format($monthly_needed, 2) . "/month\n";
                }
                $context .= "\n";
            }
        }
        
        // Budget Performance Analysis
        if (!empty($data['budget_allocations'])) {
            $context .= "� BUDGET ALLOCATIONS vs ACTUAL SPENDING:\n";
            foreach ($data['budget_allocations'] as $budget_item) {
                $period = date('M Y', strtotime($budget_item['period_start']));
                $context .= "• {$budget_item['category_name']} ({$period}): Budgeted $" . number_format($budget_item['budgeted_amount'], 2) . "\n";
            }
            $context .= "\n";
        }
        
        // Income Sources Analysis
        if (!empty($data['income_history'])) {
            $context .= "💼 INCOME SOURCES ANALYSIS:\n";
            $income_by_source = [];
            foreach ($data['income_history'] as $income) {
                if (!isset($income_by_source[$income['source']])) {
                    $income_by_source[$income['source']] = ['total' => 0, 'count' => 0, 'frequency' => $income['frequency']];
                }
                $income_by_source[$income['source']]['total'] += $income['amount'];
                $income_by_source[$income['source']]['count']++;
            }
            
            foreach ($income_by_source as $source => $data_item) {
                $avg_amount = $data_item['total'] / $data_item['count'];
                $context .= "• {$source}: $" . number_format($data_item['total'], 2) . " total ({$data_item['count']} payments, avg $" . number_format($avg_amount, 2) . " per {$data_item['frequency']})\n";
            }
            $context .= "\n";
        }
        
        // Financial Health Assessment with Personalized Recommendations
        $healthScore = $data['financial_health']['health_score'] ?? 50;
        $context .= "🏥 PERSONALIZED FINANCIAL HEALTH ASSESSMENT:\n";
        if ($healthScore >= 85) {
            $context .= "• EXCELLENT FINANCIAL HEALTH: You're in the top 15% of financial wellness!\n";
        } elseif ($healthScore >= 70) {
            $context .= "• GOOD FINANCIAL HEALTH: Solid foundation with room for optimization\n";
        } elseif ($healthScore >= 50) {
            $context .= "• FAIR FINANCIAL HEALTH: Several areas need focused attention\n";
        } else {
            $context .= "• NEEDS IMPROVEMENT: Requires immediate focus on financial fundamentals\n";
        }
        
        $context .= "\n=== END OF COMPREHENSIVE FINANCIAL PROFILE ===\n";
        return $context;
    }
    
    private function callAI($prompt) {
        try {
            // For Ollama local instance
            $data = [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                    'max_tokens' => 500
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                return $result['response'] ?? 'Unable to generate response';
            }
            
            // Fallback to rule-based response if AI service is unavailable
            return $this->generateFallbackResponse($prompt);
            
        } catch (Exception $e) {
            return $this->generateFallbackResponse($prompt);
        }
    }
    
    private function generateFallbackResponse($prompt) {
        // Simple rule-based fallback when AI service is unavailable
        if (strpos($prompt, 'predictive insights') !== false) {
            return $this->getFallbackInsights();
        } else {
            return "I'm currently experiencing technical difficulties. Please try again later, or consider reviewing your budget categories and spending patterns manually.";
        }
    }
    
    private function getFallbackInsights() {
        return "• Monitor your largest expense categories for potential savings opportunities\n" .
               "• Consider increasing your emergency fund if it's below 3 months of expenses\n" .
               "• Review subscription services and recurring payments monthly\n" .
               "• Set up automatic transfers to savings accounts to improve consistency\n" .
               "• Track seasonal spending patterns to better plan for upcoming expenses";
    }
}

// Helper function to get comprehensive financial data from ALL app sections
function getComprehensiveFinancialData($conn, $user_id) {
    $data = [];
    
    // Get all financial data components from insights_data.php
    include_once 'insights_data.php';
    
    $data['financial_health'] = getFinancialHealth($conn, $user_id);
    $data['spending_patterns'] = getSpendingPatterns($conn, $user_id);
    $data['goals'] = getGoalAnalytics($conn, $user_id);
    $data['budget_performance'] = getBudgetPerformance($conn, $user_id);
    $data['income_trends'] = getIncomeTrends($conn, $user_id);
    
    // Get additional data from other parts of the app
    
    // 1. Personal Expenses (detailed transaction history)
    $expenses_query = "
        SELECT 
            pe.amount,
            pe.expense_date,
            pe.description,
            bc.name as category_name,
            DATE_FORMAT(pe.expense_date, '%Y-%m') as month_year
        FROM personal_expenses pe
        LEFT JOIN budget_categories bc ON pe.category_id = bc.id
        WHERE pe.user_id = ?
        ORDER BY pe.expense_date DESC
        LIMIT 100
    ";
    $stmt = $conn->prepare($expenses_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['recent_transactions'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 2. Budget Data (from personal_budget table)
    $budget_query = "
        SELECT 
            pb.category_id,
            pb.amount as budgeted_amount,
            bc.name as category_name,
            pb.period_start,
            pb.period_end
        FROM personal_budget pb
        LEFT JOIN budget_categories bc ON pb.category_id = bc.id
        WHERE pb.user_id = ?
        ORDER BY pb.period_start DESC
        LIMIT 20
    ";
    $stmt = $conn->prepare($budget_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['budget_allocations'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 3. Savings Data (from personal_goals table)
    $savings_query = "
        SELECT 
            goal_name,
            target_amount,
            current_amount,
            target_date,
            created_at,
            DATEDIFF(target_date, CURDATE()) as days_remaining,
            (current_amount / target_amount * 100) as progress_percentage
        FROM personal_goals
        WHERE user_id = ?
        ORDER BY created_at DESC
    ";
    $stmt = $conn->prepare($savings_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['detailed_goals'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 4. Income Data (if available from personal_income table)
    $income_query = "
        SELECT 
            source,
            amount,
            frequency,
            date_received,
            DATE_FORMAT(date_received, '%Y-%m') as month_year
        FROM personal_income
        WHERE user_id = ?
        ORDER BY date_received DESC
        LIMIT 50
    ";
    if ($conn->query("SHOW TABLES LIKE 'personal_income'")->num_rows > 0) {
        $stmt = $conn->prepare($income_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $data['income_history'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // 5. Monthly Summaries
    $monthly_summary_query = "
        SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            COUNT(*) as transaction_count,
            SUM(amount) as total_spent,
            AVG(amount) as avg_transaction,
            MIN(amount) as min_transaction,
            MAX(amount) as max_transaction
        FROM personal_expenses
        WHERE user_id = ?
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ";
    $stmt = $conn->prepare($monthly_summary_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['monthly_summaries'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 6. User Profile Information
    $user_query = "
        SELECT 
            first_name,
            last_name,
            email,
            created_at,
            user_type
        FROM users
        WHERE id = ?
    ";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $data['user_profile'] = $stmt->get_result()->fetch_assoc();
    
    return $data;
}
?>
