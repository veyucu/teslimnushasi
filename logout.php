<?php
/**
 * Çıkış İşlemi
 */
require_once 'includes/auth.php';

logout();
header('Location: index.php');
exit;
