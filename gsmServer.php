<?php
// ===========================================
// Gym Store Management Server
// ===========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database Configuration
$host = "localhost";
$user = "root";
$pass = "";
$db = "g14";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Detect 'action' parameter safely for both GET and POST
$action = null;
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $decoded = json_decode($rawInput, true);
        if (isset($decoded['action'])) {
            $action = $decoded['action'];
        }
        if (is_array($decoded)) $_POST = $decoded;
    }
}


// ===========================================
// GET REQUESTS
// ===========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Get all products
    if ($action === 'getProducts') {
        $sql = "SELECT * FROM products_tbl ORDER BY product_id";
        $result = $conn->query($sql);
        
        if ($result) {
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $products]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch products']);
        }
    }
    
    // Get sales history (with optional date filter)
    elseif ($action === 'getSalesHistory') {
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';
        
        if ($start && $end) {
            $stmt = $conn->prepare("SELECT * FROM sales_tbl WHERE DATE(sale_date) BETWEEN ? AND ? ORDER BY sale_date DESC");
            $stmt->bind_param("ss", $start, $end);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query("SELECT * FROM sales_tbl ORDER BY sale_date DESC");
        }
        
        if ($result) {
            $sales = [];
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
            
            if (count($sales) > 0) {
                echo json_encode(['status' => 'success', 'data' => $sales]);
            } else {
                echo json_encode(['status' => 'success', 'data' => [], 'message' => 'No sales found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch sales history']);
        }
    }
    
    // Get sale items (details)
    elseif ($action === 'getSaleItems') {
        $sale_id = $_GET['sale_id'] ?? null;
        
        if (!$sale_id) {
            echo json_encode(['status' => 'error', 'message' => 'Sale ID is required']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT si.*, p.product_name 
            FROM sales_items_tbl si 
            JOIN products_tbl p ON si.product_id = p.product_id 
            WHERE si.sale_id = ?
        ");
        $stmt->bind_param("i", $sale_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $items]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch sale items']);
        }
    }
    
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}

// ===========================================
// POST REQUESTS
// ===========================================
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Update product stock
    if ($action === 'updateStock') {
        $id = $input['id'] ?? null;
        $stock = $input['stock'] ?? null;
        
        if ($id === null || $stock === null) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            exit;
        }
        
        // Determine status based on stock
        if ($stock == 0) $status = "OUT OF STOCK";
        elseif ($stock <= 5) $status = "LOW STOCK";
        else $status = "IN STOCK";
        
        $stmt = $conn->prepare("UPDATE products_tbl SET stock = ?, status = ? WHERE product_id = ?");
        $stmt->bind_param("isi", $stock, $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stock updated']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update stock']);
        }
    }
    
    // Complete a sale
    elseif ($action === 'completeSale') {
        $cart = $input['cart'] ?? [];
        $subtotal = $input['subtotal'] ?? 0;
        $tax = $input['tax'] ?? 0;
        $total = $input['total'] ?? 0;
        
        if (empty($cart)) {
            echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
            exit;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert sale record
            $stmt = $conn->prepare("INSERT INTO sales_tbl (subtotal, tax, total, sale_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("ddd", $subtotal, $tax, $total);
            $stmt->execute();
            $sale_id = $conn->insert_id;
            
            // Insert sale items and update stock
            foreach ($cart as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                // Insert sale item
                $stmt = $conn->prepare("INSERT INTO sales_items_tbl (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $sale_id, $product_id, $quantity, $price);
                $stmt->execute();
                
                // Update product stock
                $stmt = $conn->prepare("UPDATE products_tbl SET stock = stock - ? WHERE product_id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
                
                // Update product status
                $stmt = $conn->prepare("SELECT stock FROM products_tbl WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $new_stock = $row['stock'];
                
                if ($new_stock == 0) $status = "OUT OF STOCK";
                elseif ($new_stock <= 5) $status = "LOW STOCK";
                else $status = "IN STOCK";
                
                $stmt = $conn->prepare("UPDATE products_tbl SET status = ? WHERE product_id = ?");
                $stmt->bind_param("si", $status, $product_id);
                $stmt->execute();
            }
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'sale_id' => $sale_id, 'message' => 'Sale completed successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
        }
    }
    
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}

else {
    echo json_encode(['status' => 'error', 'message' => 'Only GET and POST requests are allowed']);
}

$conn->close();