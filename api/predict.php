<?php
// Forecast (moving average + linear regression) and insight sentences.
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/analytics.php';
$uid = api_user();
try { json_out(['forecast' => forecast($uid), 'insights' => insights($uid)]); }
catch (Throwable $ex) { json_out(['error' => 'Could not calculate the forecast.'], 500); }
