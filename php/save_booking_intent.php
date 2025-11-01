<?php
/**
 * Sauvegarder l'intention de réservation avant connexion
 */
session_start();

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $_SESSION['booking_intent'] = [
        'room_type_id' => (int)$data['room_type_id'],
        'room_name' => $data['room_name'],
        'price' => (float)$data['price'],
        'check_in' => $data['check_in'],
        'check_out' => $data['check_out'],
        'guests' => (int)$data['guests'],
        'timestamp' => time()
    ];
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
