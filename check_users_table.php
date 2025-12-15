<?php
require 'config/db.php';

echo "Checking users table structure:\n\n";

$result = $conn->query('DESCRIBE users');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . " | " . $row['Default'] . "\n";
}
?>