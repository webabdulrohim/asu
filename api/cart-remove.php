<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
    exit;
}

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        
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
        unset($_SESSION['guest_cart'][$product_id]);
        
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
