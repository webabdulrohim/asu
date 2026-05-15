<?php
require_once '../includes/config.php';

/**
 * Tripay Payment Gateway Integration
 * Handles payment transaction creation and callback
 */
class TripayPayment {
    private $apiKey;
    private $privateKey;
    private $merchantCode;
    private $baseUrl;
    
    public function __construct() {
        $this->apiKey = TRIPAY_API_KEY;
        $this->privateKey = TRIPAY_PRIVATE_KEY;
        $this->merchantCode = TRIPAY_MERCHANT_CODE;
        $this->baseUrl = TRIPAY_MODE === 'sandbox' ? TRIPAY_SANDBOX_URL : TRIPAY_PRODUCTION_URL;
    }
    
    /**
     * Create payment transaction
     */
    public function createTransaction($order) {
        $merchantRef = $order['order_code'];
        $amount = $order['grand_total'];
        
        $data = [
            'method'         => $this->getPaymentMethod(),
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'],
            'order_items'    => $order['items'],
            'expired_time'   => time() + (24 * 60 * 60), // 24 hours
            'signature'      => hash_hmac('sha256', $this->merchantCode.$merchantRef.$amount, $this->privateKey),
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $this->baseUrl . 'transaction/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            return false;
        }
    }
    
    /**
     * Get payment channels list
     */
    public function getPaymentChannels($amount = null) {
        $url = $this->baseUrl . 'payment-channel/list?amount=' . ($amount ?? 0);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            return false;
        }
    }
    
    /**
     * Verify callback signature
     */
    public function verifyCallback($callbackData) {
        $json = json_encode($callbackData);
        $signature = hash_hmac('sha256', $json, $this->privateKey);
        
        return $signature === $_SERVER['HTTP_X_TRIPAY_SIGNATURE'] ?? '';
    }
    
    /**
     * Get transaction status
     */
    public function getTransactionStatus($merchantRef) {
        $url = $this->baseUrl . 'transaction/details?merchant_ref=' . $merchantRef;
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            return false;
        }
    }
    
    private function getPaymentMethod() {
        // Can be customized based on requirements
        return 'CHANNEL';
    }
}

// Handle Tripay Callback
if (isset($_POST['merchant_ref']) && isset($_SERVER['HTTP_X_TRIPAY_SIGNATURE'])) {
    header('Content-Type: application/json');
    
    $callbackData = $_POST;
    $tripay = new TripayPayment();
    
    // Verify callback signature
    if (!$tripay->verifyCallback($callbackData)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
    
    $merchantRef = $callbackData['merchant_ref'];
    $status = $callbackData['status'];
    
    try {
        // Update order status based on payment status
        $orderStatus = 'pending';
        $paymentStatus = 'unpaid';
        
        switch ($status) {
            case 'PAID':
                $orderStatus = 'paid';
                $paymentStatus = 'paid';
                break;
            case 'FAILED':
                $orderStatus = 'cancelled';
                $paymentStatus = 'failed';
                break;
            case 'EXPIRED':
                $orderStatus = 'cancelled';
                $paymentStatus = 'failed';
                break;
        }
        
        // Update order in database
        $stmt = $pdo->prepare("UPDATE orders SET 
                              status = ?, 
                              payment_status = ?, 
                              paid_at = CASE WHEN ? = 'paid' THEN NOW() ELSE paid_at END,
                              updated_at = NOW() 
                              WHERE order_code = ?");
        $stmt->execute([$orderStatus, $paymentStatus, $paymentStatus, $merchantRef]);
        
        // If payment is successful, update product stock
        if ($paymentStatus === 'paid') {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
            $stmt->execute([$merchantRef]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($items as $item) {
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                    
                    $stmt = $pdo->prepare("UPDATE products SET sold_count = sold_count + ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Order updated']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    
    exit;
}
?>
