<?php
/**
 * AJAX endpoint for live book search autocomplete
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$search_term = '%' . $query . '%';

// Search for books matching title
$stmt = $conn->prepare('
    SELECT book_id, title, author, cover_image
    FROM books
    WHERE title LIKE ?
    ORDER BY 
        CASE 
            WHEN title LIKE ? THEN 1
            ELSE 2
        END,
        title
    LIMIT 8
');

$exact_term = $query . '%';
$stmt->bind_param('ss', $search_term, $exact_term);
$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = [
        'id' => $row['book_id'],
        'title' => $row['title'],
        'author' => $row['author'],
        'cover' => $row['cover_image'] ? '../' . $row['cover_image'] : '../assets/images/default-book.png'
    ];
}

$stmt->close();

echo json_encode($books);
?>
