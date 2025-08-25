<?php
// Directly test the PHP file to see all output
echo "=== DIRECT PHP INCLUDE TEST ===\n\n";

// Set up environment
session_start();
$_SESSION['user_id'] = 2;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock the input stream
$_POST = []; // Clear any POST data
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode(['step_name' => 'setup_income']);

// Override php://input for testing
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPHPInputStream");

class MockPHPInputStream {
    private $position = 0;
    private $data = '{"step_name":"setup_income"}';
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof() {
        return $this->position >= strlen($this->data);
    }
    
    public function stream_stat() {
        return array();
    }
}

echo "About to include complete_step.php...\n";

// Capture all output
ob_start();
try {
    include 'public/walkthrough/complete_step.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "Raw output from PHP file:\n";
echo "Length: " . strlen($output) . " characters\n";
echo "Output: " . $output . "\n";

// Restore original stream wrapper
stream_wrapper_restore("php");
?>
