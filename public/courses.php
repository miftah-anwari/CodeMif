<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\CourseController;

$controller = new CourseController();
$controller->index();