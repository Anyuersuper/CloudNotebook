<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入密码函数兼容库和数据库工具
require_once(__DIR__.'/password_compat.php');
require_once(__DIR__.'/db.php');

// 只有在会话尚未启动时才启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 获取请求方法和操作类型
$action = isset($_POST['action']) ? $_POST['action'] : '';
$id = isset($_POST['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']) : '';

// 验证ID
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => '无效的ID']);
    exit;
}

// 获取数据库实例
$db = NotebookDB::getInstance();

// 根据操作类型处理请求
try {
    switch ($action) {
        case 'verify_password':
            verifyPassword($id, $db);
            break;
        case 'create_note':
            createNote($id, $db);
            break;
        case 'save_note':
            saveNote($id, $db);
            break;
        case 'update_settings':
            updateSettings($id, $db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => '无效的操作']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '处理请求时发生错误']);
}

// 验证密码
function verifyPassword($id, $db) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => '密码不能为空']);
        exit;
    }
    
    if (!$db->notebookExists($id)) {
        // 清除可能存在的认证状态
        if (isset($_SESSION['auth_' . $id])) {
            unset($_SESSION['auth_' . $id]);
        }
        echo json_encode(['success' => false, 'message' => '记事本不存在']);
        exit;
    }
    
    // 获取存储的密码哈希
    $password_hash = $db->getPasswordHash($id);
    
    if (empty($password_hash)) {
        echo json_encode(['success' => false, 'message' => '读取记事本失败']);
        exit;
    }
    
    // 验证密码
    if (password_verify($password, $password_hash)) {
        // 设置认证状态
        $_SESSION['auth_' . $id] = true;
        
        // 获取是否总是需要密码的设置
        $always_require_password = $db->getAlwaysRequirePassword($id);
        
        // 返回验证结果，并通知前端是否总是需要密码
        echo json_encode([
            'success' => true,
            'always_require_password' => $always_require_password
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '密码错误']);
    }
}

// 创建新记事本
function createNote($id, $db) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => '密码不能为空']);
        exit;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => '两次输入的密码不一致']);
        exit;
    }
    
    if ($db->notebookExists($id)) {
        echo json_encode(['success' => false, 'message' => '记事本ID已存在']);
        exit;
    }
    
    // 创建密码哈希
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // 创建笔记本
    $result = $db->createNotebook($id, $password_hash);
    
    if ($result) {
        $_SESSION['auth_' . $id] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '创建记事本失败']);
    }
}

// 保存记事本内容
function saveNote($id, $db) {
    // 检查认证
    if (!isset($_SESSION['auth_' . $id]) || $_SESSION['auth_' . $id] !== true) {
        echo json_encode(['success' => false, 'message' => '未授权的操作']);
        exit;
    }
    
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => '内容不能为空']);
        exit;
    }
    
    if (!$db->notebookExists($id)) {
        // 清除可能存在的认证状态
        if (isset($_SESSION['auth_' . $id])) {
            unset($_SESSION['auth_' . $id]);
        }
        echo json_encode(['success' => false, 'message' => '记事本不存在']);
        exit;
    }
    
    // 保存笔记本内容
    $result = $db->saveNotebook($id, $content);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '保存失败']);
    }
}

// 更新笔记本设置
function updateSettings($id, $db) {
    // 检查认证
    if (!isset($_SESSION['auth_' . $id]) || $_SESSION['auth_' . $id] !== true) {
        echo json_encode(['success' => false, 'message' => '未授权的操作']);
        exit;
    }
    
    if (!$db->notebookExists($id)) {
        // 清除可能存在的认证状态
        if (isset($_SESSION['auth_' . $id])) {
            unset($_SESSION['auth_' . $id]);
        }
        echo json_encode(['success' => false, 'message' => '记事本不存在']);
        exit;
    }
    
    // 获取设置值
    $always_require_password = isset($_POST['always_require_password']) ? (bool)$_POST['always_require_password'] : false;
    
    // 更新设置
    $result = $db->updateSettings($id, [
        'always_require_password' => $always_require_password
    ]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '更新设置失败']);
    }
} 