<?php
// session_start();
// echo json_encode([
//     'user' => $_SESSION['user'] ?? 'Guest',
//     'usertype' => $_SESSION['usertype'] ?? 'none'
// ]);



// session_start();
// header('Content-Type: application/json');

// if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

// echo json_encode([
//     'user' => $_SESSION['user'],
//     'usertype' => $_SESSION['usertype']
// ]);
// exit;

// move to includes directory if needed

session_start();
header('Content-Type: application/json');

echo json_encode([
    'user' => $_SESSION['user'] ?? 'Guest',
    'usertype' => $_SESSION['usertype'] ?? 'none'
]);
