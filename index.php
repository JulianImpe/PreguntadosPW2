<?php
session_start();

include("helper/ConfigFactory.php");

$configFactory = new ConfigFactory();
$router = $configFactory->get("router");

$router->executeController($_GET["controller"] ?? null, $_GET["method"] ?? null);