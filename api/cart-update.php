<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$change = isset($input['change']) ? (int)$input['change'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
    exit;
}

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart_item) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ada di keranjang']);
            exit;
        }
        
        $new_quantity = $cart_item['quantity'] + $change;
        
        if ($new_quantity <= 0) {
            // Remove from cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
        } else {
            // Check stock
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product['stock'] < $new_quantity) {
                echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$new_quantity, $_SESSION['user_id'], $product_id]);
        }
        
        // Update cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $result['total'] ?? 0;
        $_SESSION['cart_count'] = $cart_count;
        
        echo json_encode(['success' => true, 'cart_count' => $cart_count]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
} else if (isset($_SESSION['guest_cart'])) {
    if (isset($_SESSION['guest_cart'][$product_id])) {
        $new_quantity = $_SESSION['guest_cart'][$product_id] + $change;
        
        if ($new_quantity <= 0) {
            unset($_SESSION['guest_cart'][$product_id]);
        } else {
            $_SESSION['guest_cart'][$product_id] = $new_quantity;
        }
        
        $cart_count = array_sum($_SESSION['guest_cart']);
        $_SESSION['cart_count'] = $cart_count;
        
        echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ada di keranjang']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
}
?>
