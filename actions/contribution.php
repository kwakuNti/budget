<?php
session_start();
require_once '../config/connection.php';
require_once '../includes/contribution_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_contribution') {
        $familyId = $_SESSION['family_id'] ?? null;
        $memberName = trim($_POST['member'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $note = trim($_POST['note'] ?? '');

        // Validation
        if (!$familyId) {
            echo json_encode(["success" => false, "message" => "Session expired. Please login again."]);
            exit;
        }

        if (!$memberName || $amount <= 0 || !$date) {
            echo json_encode(["success" => false, "message" => "Invalid input. Please fill all required fields."]);
            exit;
        }

        try {
            // Auto-create monthly cycle if needed
            autoCreateMonthlyCycleIfNeeded($conn, $familyId);

            // Get member ID using concatenated full name
            $stmt = $conn->prepare("SELECT id FROM family_members_only WHERE CONCAT(first_name, ' ', last_name) = ? AND family_id = ?");
            $stmt->bind_param("si", $memberName, $familyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();

            if (!$member) {
                echo json_encode(["success" => false, "message" => "Member '$memberName' not found in family."]);
                exit;
            }

            $memberId = $member['id'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert into family_contributions table
                $stmt = $conn->prepare("
                    INSERT INTO family_contributions (
                        family_id, 
                        member_only_id, 
                        contributor_type, 
                        amount, 
                        contribution_date, 
                        notes, 
                        created_at
                    ) VALUES (?, ?, 'member', ?, ?, ?, NOW())
                ");
                $stmt->bind_param("iidss", $familyId, $memberId, $amount, $date, $note);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert contribution: " . $conn->error);
                }

                // The trigger will handle:
                // 1. Update family pool
                // 2. Update member total_contributed
                // 3. Call UpdateMemberPerformance stored procedure
                // 4. Update monthly cycle performance tracking

                $conn->commit();

                // Get updated member info for response
                $stmt = $conn->prepare("
                    SELECT 
                        CONCAT(first_name, ' ', last_name) as full_name,
                        total_contributed,
                        monthly_contribution_goal,
                        current_month_contributed,
                        goal_met_this_month
                    FROM family_members_only 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $memberId);
                $stmt->execute();
                $memberInfo = $stmt->get_result()->fetch_assoc();

                $message = "₵" . number_format($amount, 2) . " contribution added successfully for " . $memberName . "!";
                
                // Add goal achievement message if applicable
                if ($memberInfo['goal_met_this_month']) {
                    $message .= " 🎉 Monthly goal achieved!";
                }

                echo json_encode([
                    "success" => true,
                    "message" => $message,
                    "memberInfo" => $memberInfo
                ]);

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }

        exit;
    }

    if ($action === 'update_goal') {
        $familyId = $_SESSION['family_id'] ?? null;
        $memberName = trim($_POST['member'] ?? '');
        $goalAmount = floatval($_POST['goal'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!$familyId || !$memberName || $goalAmount <= 0) {
            echo json_encode(["success" => false, "message" => "Invalid input."]);
            exit;
        }

        try {
            // Get member ID
            $stmt = $conn->prepare("SELECT id FROM family_members_only WHERE CONCAT(first_name, ' ', last_name) = ? AND family_id = ?");
            $stmt->bind_param("si", $memberName, $familyId);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();

            if (!$member) {
                echo json_encode(["success" => false, "message" => "Member not found."]);
                exit;
            }

            $memberId = $member['id'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update monthly goal
                $stmt = $conn->prepare("UPDATE family_members_only SET monthly_contribution_goal = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("dsi", $goalAmount, $description, $memberId);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update member goal");
                }

                // Update current cycle performance record if exists
                $stmt = $conn->prepare("
                    UPDATE member_monthly_performance mmp
                    JOIN monthly_cycles mc ON mmp.cycle_id = mc.id
                    SET mmp.target_amount = ?
                    WHERE mc.family_id = ? 
                    AND mc.is_current = TRUE 
                    AND mmp.member_only_id = ?
                    AND mmp.member_type = 'member'
                ");
                $stmt->bind_param("dii", $goalAmount, $familyId, $memberId);
                $stmt->execute();

                // Update cycle total target
                $stmt = $conn->prepare("
                    UPDATE monthly_cycles mc
                    SET mc.total_target = (
                        SELECT COALESCE(SUM(fm.monthly_contribution_goal), 0) + COALESCE(SUM(fmo.monthly_contribution_goal), 0)
                        FROM family_groups fg
                        LEFT JOIN family_members fm ON fg.id = fm.family_id AND fm.is_active = TRUE
                        LEFT JOIN family_members_only fmo ON fg.id = fmo.family_id AND fmo.is_active = TRUE
                        WHERE fg.id = ?
                    )
                    WHERE mc.family_id = ? AND mc.is_current = TRUE
                ");
                $stmt->bind_param("ii", $familyId, $familyId);
                $stmt->execute();

                $conn->commit();

                echo json_encode([
                    "success" => true,
                    "message" => "Goal updated for $memberName! Current cycle targets have been adjusted."
                ]);

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error updating goal: " . $e->getMessage()]);
        }

        exit;
    }
}

// Helper function to auto-create monthly cycle
function autoCreateMonthlyCycleIfNeeded($conn, $familyId) {
    // Check if current month cycle exists
    $currentMonth = date('Y-m');
    $stmt = $conn->prepare("
        SELECT id FROM monthly_cycles 
        WHERE family_id = ? AND cycle_month = ? AND is_current = TRUE
    ");
    $stmt->bind_param("is", $familyId, $currentMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new cycle for current month
        $year = date('Y');
        $month = date('n');
        
        $stmt = $conn->prepare("CALL CreateNewMonthlyCycle(?, ?, ?)");
        $stmt->bind_param("iii", $familyId, $year, $month);
        $stmt->execute();
    }
}

// Handle invalid requests
echo json_encode(["success" => false, "message" => "Invalid request method or action."]);
?>