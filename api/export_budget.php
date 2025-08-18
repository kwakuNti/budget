<?php
require_once '../config/connection.php';
require_once '../includes/budget_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // Get export parameters
    $export_period = $input['export_period'] ?? 'current_month';
    $export_format = $input['export_format'] ?? 'csv';
    $from_date = $input['export_from_date'] ?? null;
    $to_date = $input['export_to_date'] ?? null;
    
    // Data inclusion options
    $include_categories = $input['include_categories'] ?? true;
    $include_expenses = $input['include_expenses'] ?? true;
    $include_variance = $input['include_variance'] ?? true;
    $include_allocation = $input['include_allocation'] ?? true;
    $include_summary = $input['include_summary'] ?? false;
    $include_charts = $input['include_charts'] ?? false;
    
    // Determine date range based on period
    list($start_date, $end_date) = getDateRange($export_period, $from_date, $to_date);
    
    // Get budget data
    $budget_data = getBudgetExportData($pdo, $user_id, $start_date, $end_date, [
        'include_categories' => $include_categories,
        'include_expenses' => $include_expenses,
        'include_variance' => $include_variance,
        'include_allocation' => $include_allocation,
        'include_summary' => $include_summary
    ]);
    
    // Generate export file
    $file_data = generateExportFile($budget_data, $export_format, [
        'period' => $export_period,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'include_charts' => $include_charts
    ]);
    
    // Return file data for download
    echo json_encode([
        'success' => true,
        'file_data' => base64_encode($file_data['content']),
        'filename' => $file_data['filename'],
        'mime_type' => $file_data['mime_type']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}

function getDateRange($period, $from_date = null, $to_date = null) {
    switch ($period) {
        case 'current_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('last month'));
            $end_date = date('Y-m-t', strtotime('last month'));
            break;
        case 'current_year':
            $start_date = date('Y-01-01');
            $end_date = date('Y-12-31');
            break;
        case 'last_year':
            $start_date = date('Y-01-01', strtotime('last year'));
            $end_date = date('Y-12-31', strtotime('last year'));
            break;
        case 'custom':
            $start_date = $from_date ?: date('Y-m-01');
            $end_date = $to_date ?: date('Y-m-t');
            break;
        default:
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
    }
    
    return [$start_date, $end_date];
}

function getBudgetExportData($pdo, $user_id, $start_date, $end_date, $options) {
    $data = [];
    
    // Get user allocation data
    if ($options['include_allocation']) {
        $allocation_query = "SELECT * FROM personal_budget_allocation WHERE user_id = ?";
        $stmt = $pdo->prepare($allocation_query);
        $stmt->execute([$user_id]);
        $data['allocation'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get budget categories
    if ($options['include_categories']) {
        $categories_query = "SELECT bc.*, pbc.budget_amount, pbc.budget_percentage 
                           FROM budget_categories bc 
                           LEFT JOIN personal_budget_categories pbc ON bc.category_id = pbc.category_id AND pbc.user_id = ?
                           WHERE bc.user_id = ? OR bc.user_id IS NULL
                           ORDER BY bc.category_type, bc.category_name";
        $stmt = $pdo->prepare($categories_query);
        $stmt->execute([$user_id, $user_id]);
        $data['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get expenses for the period
    if ($options['include_expenses']) {
        $expenses_query = "SELECT pe.*, bc.category_name, bc.category_type 
                          FROM personal_expenses pe
                          JOIN budget_categories bc ON pe.category_id = bc.category_id
                          WHERE pe.user_id = ? AND DATE(pe.expense_date) BETWEEN ? AND ?
                          ORDER BY pe.expense_date DESC, bc.category_type";
        $stmt = $pdo->prepare($expenses_query);
        $stmt->execute([$user_id, $start_date, $end_date]);
        $data['expenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Calculate variance if requested
    if ($options['include_variance'] && $options['include_categories'] && $options['include_expenses']) {
        $data['variance'] = calculateVariance($data['categories'], $data['expenses']);
    }
    
    // Generate summary if requested
    if ($options['include_summary']) {
        $data['summary'] = generateSummary($data);
    }
    
    return $data;
}

function calculateVariance($categories, $expenses) {
    $variance = [];
    
    // Group expenses by category
    $expense_totals = [];
    foreach ($expenses as $expense) {
        $category_id = $expense['category_id'];
        if (!isset($expense_totals[$category_id])) {
            $expense_totals[$category_id] = 0;
        }
        $expense_totals[$category_id] += $expense['amount'];
    }
    
    // Calculate variance for each category
    foreach ($categories as $category) {
        $category_id = $category['category_id'];
        $budgeted = $category['budget_amount'] ?? 0;
        $spent = $expense_totals[$category_id] ?? 0;
        $variance_amount = $budgeted - $spent;
        $variance_percent = $budgeted > 0 ? ($variance_amount / $budgeted) * 100 : 0;
        
        $variance[] = [
            'category_id' => $category_id,
            'category_name' => $category['category_name'],
            'category_type' => $category['category_type'],
            'budgeted' => $budgeted,
            'spent' => $spent,
            'variance_amount' => $variance_amount,
            'variance_percent' => $variance_percent,
            'status' => $variance_amount >= 0 ? 'under_budget' : 'over_budget'
        ];
    }
    
    return $variance;
}

function generateSummary($data) {
    $summary = [
        'total_budgeted' => 0,
        'total_spent' => 0,
        'total_variance' => 0,
        'categories_count' => 0,
        'expenses_count' => 0,
        'over_budget_categories' => 0,
        'allocation' => []
    ];
    
    if (isset($data['categories'])) {
        $summary['categories_count'] = count($data['categories']);
        foreach ($data['categories'] as $category) {
            $summary['total_budgeted'] += $category['budget_amount'] ?? 0;
        }
    }
    
    if (isset($data['expenses'])) {
        $summary['expenses_count'] = count($data['expenses']);
        foreach ($data['expenses'] as $expense) {
            $summary['total_spent'] += $expense['amount'];
        }
    }
    
    if (isset($data['variance'])) {
        foreach ($data['variance'] as $var) {
            $summary['total_variance'] += $var['variance_amount'];
            if ($var['status'] === 'over_budget') {
                $summary['over_budget_categories']++;
            }
        }
    }
    
    if (isset($data['allocation'])) {
        $summary['allocation'] = [
            'salary' => $data['allocation']['monthly_salary'] ?? 0,
            'needs' => $data['allocation']['needs_amount'] ?? 0,
            'wants' => $data['allocation']['wants_amount'] ?? 0,
            'savings' => $data['allocation']['savings_amount'] ?? 0
        ];
    }
    
    return $summary;
}

function generateExportFile($data, $format, $options) {
    switch ($format) {
        case 'csv':
            return generateCSV($data, $options);
        case 'json':
            return generateJSON($data, $options);
        case 'pdf':
            return generatePDF($data, $options);
        default:
            throw new Exception('Unsupported export format');
    }
}

function generateCSV($data, $options) {
    $csv_content = '';
    $filename = 'budget_export_' . date('Y-m-d') . '.csv';
    
    // Add header
    $csv_content .= "Budget Export Report\n";
    $csv_content .= "Generated: " . date('Y-m-d H:i:s') . "\n";
    $csv_content .= "Period: " . ($options['start_date'] ?? '') . " to " . ($options['end_date'] ?? '') . "\n\n";
    
    // Allocation section
    if (isset($data['allocation'])) {
        $csv_content .= "BUDGET ALLOCATION\n";
        $csv_content .= "Monthly Salary," . ($data['allocation']['monthly_salary'] ?? 0) . "\n";
        $csv_content .= "Needs (80%)," . ($data['allocation']['needs_amount'] ?? 0) . "\n";
        $csv_content .= "Wants (10%)," . ($data['allocation']['wants_amount'] ?? 0) . "\n";
        $csv_content .= "Savings (10%)," . ($data['allocation']['savings_amount'] ?? 0) . "\n\n";
    }
    
    // Categories section
    if (isset($data['categories'])) {
        $csv_content .= "BUDGET CATEGORIES\n";
        $csv_content .= "Category Name,Category Type,Budget Amount,Budget Percentage\n";
        foreach ($data['categories'] as $category) {
            $csv_content .= '"' . $category['category_name'] . '",';
            $csv_content .= '"' . $category['category_type'] . '",';
            $csv_content .= ($category['budget_amount'] ?? 0) . ',';
            $csv_content .= ($category['budget_percentage'] ?? 0) . "\n";
        }
        $csv_content .= "\n";
    }
    
    // Expenses section
    if (isset($data['expenses'])) {
        $csv_content .= "EXPENSES\n";
        $csv_content .= "Date,Category,Type,Description,Amount\n";
        foreach ($data['expenses'] as $expense) {
            $csv_content .= '"' . $expense['expense_date'] . '",';
            $csv_content .= '"' . $expense['category_name'] . '",';
            $csv_content .= '"' . $expense['category_type'] . '",';
            $csv_content .= '"' . ($expense['description'] ?? '') . '",';
            $csv_content .= $expense['amount'] . "\n";
        }
        $csv_content .= "\n";
    }
    
    // Variance section
    if (isset($data['variance'])) {
        $csv_content .= "VARIANCE ANALYSIS\n";
        $csv_content .= "Category,Budgeted,Spent,Variance,Variance %,Status\n";
        foreach ($data['variance'] as $var) {
            $csv_content .= '"' . $var['category_name'] . '",';
            $csv_content .= $var['budgeted'] . ',';
            $csv_content .= $var['spent'] . ',';
            $csv_content .= $var['variance_amount'] . ',';
            $csv_content .= number_format($var['variance_percent'], 2) . '%,';
            $csv_content .= '"' . $var['status'] . '"' . "\n";
        }
        $csv_content .= "\n";
    }
    
    // Summary section
    if (isset($data['summary'])) {
        $csv_content .= "SUMMARY\n";
        $csv_content .= "Total Budgeted," . $data['summary']['total_budgeted'] . "\n";
        $csv_content .= "Total Spent," . $data['summary']['total_spent'] . "\n";
        $csv_content .= "Total Variance," . $data['summary']['total_variance'] . "\n";
        $csv_content .= "Categories Count," . $data['summary']['categories_count'] . "\n";
        $csv_content .= "Expenses Count," . $data['summary']['expenses_count'] . "\n";
        $csv_content .= "Over Budget Categories," . $data['summary']['over_budget_categories'] . "\n";
    }
    
    return [
        'content' => $csv_content,
        'filename' => $filename,
        'mime_type' => 'text/csv'
    ];
}

function generateJSON($data, $options) {
    $filename = 'budget_export_' . date('Y-m-d') . '.json';
    
    $export_data = [
        'export_info' => [
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => $options['period'] ?? 'current_month',
            'start_date' => $options['start_date'] ?? '',
            'end_date' => $options['end_date'] ?? ''
        ],
        'data' => $data
    ];
    
    return [
        'content' => json_encode($export_data, JSON_PRETTY_PRINT),
        'filename' => $filename,
        'mime_type' => 'application/json'
    ];
}

function generatePDF($data, $options) {
    // Basic PDF generation (would require a PDF library like TCPDF or FPDF)
    // For now, return HTML that can be converted to PDF
    $filename = 'budget_export_' . date('Y-m-d') . '.html';
    
    $html_content = '<!DOCTYPE html>
<html>
<head>
    <title>Budget Export Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Budget Export Report</h1>
    <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>Period:</strong> ' . ($options['start_date'] ?? '') . ' to ' . ($options['end_date'] ?? '') . '</p>';
    
    // Allocation section
    if (isset($data['allocation'])) {
        $html_content .= '<h2>Budget Allocation</h2>
        <table>
            <tr><th>Item</th><th>Amount</th></tr>
            <tr><td>Monthly Salary</td><td>₵' . number_format($data['allocation']['monthly_salary'] ?? 0, 2) . '</td></tr>
            <tr><td>Needs (80%)</td><td>₵' . number_format($data['allocation']['needs_amount'] ?? 0, 2) . '</td></tr>
            <tr><td>Wants (10%)</td><td>₵' . number_format($data['allocation']['wants_amount'] ?? 0, 2) . '</td></tr>
            <tr><td>Savings (10%)</td><td>₵' . number_format($data['allocation']['savings_amount'] ?? 0, 2) . '</td></tr>
        </table>';
    }
    
    // Categories section
    if (isset($data['categories'])) {
        $html_content .= '<h2>Budget Categories</h2>
        <table>
            <tr><th>Category</th><th>Type</th><th>Budget Amount</th><th>Budget %</th></tr>';
        foreach ($data['categories'] as $category) {
            $html_content .= '<tr>
                <td>' . htmlspecialchars($category['category_name']) . '</td>
                <td>' . htmlspecialchars($category['category_type']) . '</td>
                <td>₵' . number_format($category['budget_amount'] ?? 0, 2) . '</td>
                <td>' . number_format($category['budget_percentage'] ?? 0, 2) . '%</td>
            </tr>';
        }
        $html_content .= '</table>';
    }
    
    // Summary section
    if (isset($data['summary'])) {
        $html_content .= '<h2>Summary</h2>
        <div class="summary">
            <p><strong>Total Budgeted:</strong> ₵' . number_format($data['summary']['total_budgeted'], 2) . '</p>
            <p><strong>Total Spent:</strong> ₵' . number_format($data['summary']['total_spent'], 2) . '</p>
            <p><strong>Total Variance:</strong> ₵' . number_format($data['summary']['total_variance'], 2) . '</p>
            <p><strong>Categories:</strong> ' . $data['summary']['categories_count'] . '</p>
            <p><strong>Expenses:</strong> ' . $data['summary']['expenses_count'] . '</p>
            <p><strong>Over Budget Categories:</strong> ' . $data['summary']['over_budget_categories'] . '</p>
        </div>';
    }
    
    $html_content .= '</body></html>';
    
    return [
        'content' => $html_content,
        'filename' => $filename,
        'mime_type' => 'text/html'
    ];
}
?>
