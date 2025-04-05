<?php
// 引入核心文件
require_once(__DIR__.'/core.php');

// 创建API处理实例并处理请求
$api = new NotebookAPI();
$api->handleRequest();
?> 