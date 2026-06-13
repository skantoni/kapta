<?php
$admin = password_hash('admin123', PASSWORD_DEFAULT);
$demo = password_hash('demo123', PASSWORD_DEFAULT);
file_put_contents('hashes.txt', "$admin\n$demo");
