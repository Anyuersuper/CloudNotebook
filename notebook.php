<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入核心文件
require_once('system/core.php');

// 处理请求
$id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';

// 处理退出登录请求
if (isset($_GET['logout'])) {
    $handler = new NotebookHandler();
    $handler->handleLogout($id);
}

// 验证ID
if (empty($id)) {
    header('Location: index.php');
    exit;
}

// 创建数据库连接
$db = NotebookDB::getInstance();

// 检查笔记本是否存在
$is_new = !$db->notebookExists($id);
$content = '';

// 如果不是新记事本，获取内容
if (!$is_new) {
    $content = $db->getNotebookContent($id);
}

// 检查认证状态
$handler = new NotebookHandler();
$is_authenticated = $handler->checkAuth($id);

// 渲染笔记本页面
require_once('system/notebook_layout.php');
?> 
