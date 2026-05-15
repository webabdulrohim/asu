<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$cart_count = 0;

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $result['total'] ?? 0;
        $_SESSION['cart_count'] = $cart_count;
    } catch (PDOException $e) {
        $cart_count = 0;
    }
} else if (isset($_SESSION['guest_cart'])) {
    $cart_count = array_sum($_SESSION['guest_cart']);
}

echo json_encode(['count' => $cart_count]);
?>
