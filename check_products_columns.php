<?php
include_once 'config/db.php';
$result = $conn->query('DESCRIBE products');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
