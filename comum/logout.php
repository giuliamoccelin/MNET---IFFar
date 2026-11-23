<?php
require_once __DIR__ . '/../includes/caminhos.php';
session_start();
session_destroy();
header('Location: ' . BASE_URL . '/index.php');
