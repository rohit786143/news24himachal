<?php
$schemaFile = __DIR__ . '/../schema.sql';
$lines = file($schemaFile);
$clean = array_slice($lines, 0, 298);
file_put_contents($schemaFile, implode('', $clean) . "\n-- =======================================================\n-- News articles are seeded from seeds.php\n-- =======================================================\n");
echo "schema.sql cleaned successfully!\n";
