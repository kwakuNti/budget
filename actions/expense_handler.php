<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/expense_functions.php';

// Check if user is logged in
if (!isset($_SESSION['family_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$family_id = $_SESSION['family_id'];

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_expense':
        addExpenseHandler($conn, $family_id);
        break;
        
    case 'update_expense':
        updateExpenseHandler($conn, $family_id);
        break;
        
    case 'delete_expense':
        deleteExpenseHandler($conn, $family_id);
        break;
        
    case 'get_expense':
        getExpenseHandler($conn, $family_id);
        break;
        
    case 'export':
        exportExpensesHandler($conn, $family_id);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addExpenseHandler($conn, $family_id) {
    try {
        $expense_type = $_POST['expense_type'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $description = $_POST['description'] ?? '';
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $payment_method = $_POST['payment_method'] ?? 'momo';
        $notes = $_POST['notes'] ?? '';
        
        // Validation
        if (empty($expense_type) || $amount <= 0 || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            return;
        }
        
        // For now, we'll use a default paid_by value (you can modify this based on your user system)
        $paid_by = 1; // This should be the actual family member ID
        
        $result = addExpense($conn, $family_id, $expense_type, $amount, $description, $expense_date, $paid_by, 'user', $payment_method);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add expense']);
        }
        
    } catch (Exception $e) {
        error_log('Add expense error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while adding the expense']);
    }
}

function updateExpenseHandler($conn, $family_id) {
    try {
        $expense_id = intval($_POST['expense_id'] ?? 0);
        $expense_type = $_POST['expense_type'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $description = $_POST['description'] ?? '';
        $expense_date = $_POST['expense_date'] ?? '';
        
        // Validation
        if ($expense_id <= 0 || empty($expense_type) || $amount <= 0 || empty($description) || empty($expense_date)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
            return;
        }
        
        $result = updateExpense($conn, $expense_id, $family_id, $expense_type, $amount, $description, $expense_date);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update expense']);
        }
        
    } catch (Exception $e) {
        error_log('Update expense error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating the expense']);
    }
}

function deleteExpenseHandler($conn, $family_id) {
    try {
        $expense_id = intval($_POST['expense_id'] ?? 0);
        
        if ($expense_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
            return;
        }
        
        $result = deleteExpense($conn, $expense_id, $family_id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete expense']);
        }
        
    } catch (Exception $e) {
        error_log('Delete expense error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the expense']);
    }
}

function getExpenseHandler($conn, $family_id) {
    try {
        $expense_id = intval($_GET['expense_id'] ?? 0);
        
        if ($expense_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
            return;
        }
        
        $expense = getExpenseById($conn, $expense_id, $family_id);
        
        if ($expense) {
            echo json_encode(['success' => true, 'expense' => $expense]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Expense not found']);
        }
        
    } catch (Exception $e) {
        error_log('Get expense error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving the expense']);
    }
}

function exportExpensesHandler($conn, $family_id) {
    try {
        // Get all expenses for the family
        $expenses = getAllExpenses($conn, $family_id, 1000); // Get up to 1000 expenses
        $stats = getExpenseStats($conn, $family_id);
        
        // Create CSV content
        $csvContent = "Date,Category,Description,Amount,Added By\n";
        
        foreach ($expenses as $expense) {
            $csvContent .= sprintf(
                "%s,%s,\"%s\",%.2f,\"%s\"\n",
                $expense['expense_date'],
                ucfirst($expense['category']),
                str_replace('"', '""', $expense['description']), // Escape quotes
                $expense['amount'],
                str_replace('"', '""', $expense['added_by'])
            );
        }
        
        // Add summary at the end
        $csvContent .= "\n\nSUMMARY\n";
        $csvContent .= "Total Expenses,₵" . number_format($stats['total_expenses'], 2) . "\n";
        $csvContent .= "This Month,₵" . number_format($stats['this_month_expenses'], 2) . "\n";
        $csvContent .= "Monthly Average,₵" . number_format($stats['average_monthly'], 2) . "\n";
        $csvContent .= "Top Category," . $stats['top_category'] . "\n";
        $csvContent .= "Export Date," . date('Y-m-d H:i:s') . "\n";
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="nkansah-family-expenses-' . date('Y-m-d') . '.csv"');
        header('Content-Length: ' . strlen($csvContent));
        
        echo $csvContent;
        
    } catch (Exception $e) {
        error_log('Export expenses error: ' . $e->getMessage());
        http_response_code(500);
        echo 'An error occurred while exporting expenses.';
    }
}
?>