<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入核心文件
require_once(__DIR__.'/core.php');

// 设置管理员密码
$admin_password = 'notebook';

// 只有在会话尚未启动时才启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录或正在尝试登录
$logged_in = false;
$message = '';

// 处理登出请求
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

// 处理登录请求
if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $logged_in = true;
    } else {
        $message = '密码错误！';
    }
} else {
    $logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// 处理删除请求
if ($logged_in && isset($_POST['delete_id'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['delete_id']);
    
    if (!empty($id)) {
        try {
            $db = NotebookDB::getInstance();
            
            if ($db->notebookExists($id)) {
                try {
                    // 添加删除记事本的方法
                    if ($db->deleteNotebook($id)) {
                        // 清除与该记事本相关的会话认证数据
                        if (isset($_SESSION['auth_' . $id])) {
                            unset($_SESSION['auth_' . $id]);
                        }
                        
                        $message = "记事本 {$id} 已成功删除！";
                    } else {
                        $message = "删除记事本 {$id} 失败！";
                    }
                } catch (Exception $e) {
                    $message = "删除时发生错误: " . $e->getMessage();
                }
            } else {
                $message = "记事本 {$id} 不存在！";
            }
        } catch (Exception $e) {
            $message = "发生错误: " . $e->getMessage();
        }
    } else {
        $message = "请输入有效的记事本ID!";
    }
    
    // 确保页面正确重定向，避免表单重复提交
    header("Location: admin.php" . (isset($_GET['page']) ? "?page=" . (int)$_GET['page'] : ""));
    exit;
}

// 处理归档码更新请求
if ($logged_in && isset($_POST['set_archive_code'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']);
    $archiveCode = trim($_POST['archive_code']);
    
    if (!empty($id)) {
        try {
            $db = NotebookDB::getInstance();
            
            if ($db->notebookExists($id)) {
                if ($db->setArchiveCode($id, $archiveCode)) {
                    $message = $archiveCode ? "记事本 {$id} 的归档码已更新！" : "记事本 {$id} 的归档码已清除！";
                } else {
                    $message = "更新记事本 {$id} 的归档码失败！";
                }
            } else {
                $message = "记事本 {$id} 不存在！";
            }
        } catch (Exception $e) {
            $message = "发生错误: " . $e->getMessage();
        }
    }
    
    // 确保页面正确重定向，避免表单重复提交
    header("Location: admin.php" . (isset($_GET['page']) ? "?page=" . (int)$_GET['page'] : ""));
    exit;
}

// 处理设置公开状态请求
if ($logged_in && isset($_POST['set_public'])) {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']);
    $isPublic = isset($_POST['ispublic']) ? 1 : 0;
    
    if (!empty($id)) {
        try {
            $db = NotebookDB::getInstance();
            
            if ($db->notebookExists($id)) {
                if ($db->setPublic($id, $isPublic)) {
                    $message = "记事本 {$id} 的公开状态已更新！";
                } else {
                    $message = "更新记事本 {$id} 的公开状态失败！";
                }
            } else {
                $message = "记事本 {$id} 不存在！";
            }
        } catch (Exception $e) {
            $message = "发生错误: " . $e->getMessage();
        }
    }
    
    // 确保页面正确重定向，避免表单重复提交
    header("Location: admin.php" . (isset($_GET['page']) ? "?page=" . (int)$_GET['page'] : ""));
    exit;
}

// 分页设置
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // 每页10条记录
$offset = ($page - 1) * $per_page;

// 获取记事本列表和总数
$notebooks = [];
$total_notebooks = 0;
if ($logged_in) {
    try {
        $db = NotebookDB::getInstance();
        $notebooks = $db->getNotebooksPaginated($offset, $per_page);
        $total_notebooks = $db->getNotebooksCount();
    } catch (Exception $e) {
        $message = "获取记事本列表失败: " . $e->getMessage();
    }
}

// 计算总页数
$total_pages = ceil($total_notebooks / $per_page);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云笔记 - 管理系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- 强制隐藏滚动条的内联样式 -->
    <style>
        /* 隐藏所有滚动条的关键样式 */
        ::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important;
        }
        * {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
    </style>
    
    <style>
        :root {
            --primary: #4a6bfa;
            --primary-dark: #3a56d4;
            --secondary: #6c63ff;
            --dark: #1a1e2e;
            --darker: #151824;
            --light: #f0f2f5;
            --gray: #6e7888;
            --success: #10b981;
            --danger: #ef4444;
            --border-radius: 8px;
            --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.2);
            --transition: all 0.2s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "SF Pro Display", "SF Pro Icons", "Helvetica Neue", "Microsoft YaHei", "Segoe UI", sans-serif;
            background-color: var(--dark);
            color: white;
            line-height: 1.5;
            font-size: 0.95em;
        }

        /* 背景几何元素 */
        .background {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            filter: blur(60px);
            transform: translateZ(0);
        }

        .shape-1 {
            background: var(--primary);
            width: 600px; height: 600px;
            top: -300px; right: -100px;
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            animation: float 10s ease-in-out infinite alternate;
        }

        .shape-2 {
            background: var(--secondary);
            width: 500px; height: 500px;
            bottom: -200px; left: -100px;
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation: float 12s ease-in-out infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(30px, 50px) rotate(10deg); }
        }

        .container {
            max-width: 80% !important;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 150px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8em;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .logo span {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .top-nav {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: var(--gray);
            background: none;
            box-shadow: none;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: var(--transition);
            padding: 8px 15px;
            border-radius: 8px;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: none;
            box-shadow: none;
        }

        .admin-title {
            font-size: 1.8em;
            font-weight: 800;
            text-align: center;
            background: linear-gradient(90deg, white, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap; /* 防止标题在小屏幕上换行 */
        }

        .card {
            background-color: var(--darker);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 15px;
        }

        .card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
        }

        .login-form {
            max-width: 400px;
            width: 100%;
            padding: 35px 30px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.6em;
            font-weight: 700;
            color: white;
        }

        .form-group { 
            margin-bottom: 25px; 
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--light);
            font-size: 1em;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(74, 107, 250, 0.3);
            outline: none;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: var(--border-radius);
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            flex: 1;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(90deg, var(--danger), #db2a2a);
            padding: 5px 10px;
            font-size: 0.9em;
        }

        .message {
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            background-color: rgba(239, 68, 68, 0.2);
            border-left: 3px solid var(--danger);
        }

        .message.success {
            background-color: rgba(16, 185, 129, 0.2);
            border-left: 3px solid var(--success);
        }

        .notebook-list { width: 100%; }

        .notebook-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            font-size: 0.9em;
            min-height: 400px; /* 确保表格有固定的最小高度 */
        }

        .notebook-table th, .notebook-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .notebook-table th {
            background-color: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            color: var(--light);
            font-size: 0.9em;
        }

        .notebook-table tr {
            transition: var(--transition);
            background-color: var(--darker);
        }

        .notebook-table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 15px;
            gap: 5px;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--light);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9em;
        }

        .pagination a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .pagination .current {
            background-color: var(--primary);
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .container {
                max-width: 100% !important;
                padding: 10px;
            }
            
            .login-container {
                min-height: calc(100vh - 120px);
            }
            
            .admin-title {
                font-size: 1.5em;
                padding: 8px 20px !important;
            }
            
            .admin-header {
                display: none;
            }
            
            .card {
                padding: 15px;
            }
            
            .login-form {
                width: 90%;
                max-width: 350px;
                padding: 25px 20px;
            }
            
            .card-title {
                font-size: 1.5em;
                margin-bottom: 20px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .btn-container {
                margin-top: 25px;
            }
            
            .btn {
                padding: 12px 16px;
            }
            
            .notebook-table {
                font-size: 0.85em;
            }
            
            .notebook-table th, .notebook-table td {
                padding: 6px;
            }
        }

        /* 添加开关按钮样式 */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        /* 归档码编辑模态框样式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: relative;
            background-color: var(--darker);
            margin: 15% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .modal-title {
            margin-bottom: 20px;
            color: var(--light);
            font-size: 1.2em;
            font-weight: 600;
        }

        .modal-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 1.2em;
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--light);
        }

        .archive-code-cell {
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .archive-code-cell:hover {
            color: var(--primary);
        }

        .archive-code-cell:hover::after {
            content: '点击编辑';
            position: absolute;
            font-size: 0.8em;
            color: var(--gray);
            margin-left: 5px;
            opacity: 0.7;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- 归档码编辑模态框 -->
    <div id="archiveCodeModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h3 class="modal-title">编辑归档码</h3>
            <form id="archiveCodeForm" method="post">
                <input type="hidden" id="modal-notebook-id" name="id">
                <input type="hidden" name="set_archive_code" value="1">
                <div class="form-group">
                    <label for="modal-archive-code" class="form-label">归档码：</label>
                    <input type="text" id="modal-archive-code" name="archive_code" class="form-input" placeholder="请输入归档码">
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn">保存</button>
                </div>
            </form>
        </div>
    </div>

    <div class="background">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="container">
        <header class="header">
            <a href="../index.php" class="logo">
                <i class="fas fa-book"></i>
                <span>云笔记</span>
            </a>
            <div class="top-nav">
                <?php if ($logged_in): ?>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i>首页
                </a>
                <a href="admin.php?logout=1" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>注销
                </a>
                <?php else: ?>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> 返回首页
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="admin-header" style="text-align: center; margin-bottom: 30px; display: none;">
            <!-- 隐藏独立的标题区域 -->
        </div>

        <?php if (!$logged_in): ?>
            <div class="login-container">
                <!-- 登录表单 -->
                <div class="card login-form">
                    <h2 class="card-title">管理员登录</h2>
                    
                    <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, '成功') !== false ? 'success' : ''; ?>" style="margin-bottom: 15px;">
                        <?php echo $message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="admin.php">
                        <div class="form-group">
                            <label for="password" class="form-label">管理密码：</label>
                            <input type="password" id="password" name="password" class="form-input" required autofocus>
                        </div>
                        <div class="btn-container">
                            <button type="submit" class="btn">登录</button>
                            <a href="../index.php" class="btn">返回首页</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, '成功') !== false ? 'success' : ''; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- 已登录状态 - 显示记事本列表 -->
            <div class="card notebook-list">
                <h2 class="card-title">后台管理</h2>
                
                <?php if (count($notebooks) > 0): ?>
                    <div class="record-info">
                        显示 <?php echo $total_notebooks; ?> 条记录中的 <?php echo min($offset + 1, $total_notebooks); ?> 到 <?php echo min($offset + $per_page, $total_notebooks); ?> 条
                    </div>
                    
                    <table class="notebook-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>创建时间</th>
                                <th>更新时间</th>
                                <th>总是需要密码</th>
                                <th>公开状态</th>
                                <th>归档码</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notebooks as $notebook): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($notebook['id']); ?></td>
                                    <td><?php echo htmlspecialchars($notebook['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($notebook['updated_at']); ?></td>
                                    <td><?php echo $notebook['always_require_password'] ? '是' : '否'; ?></td>
                                    <td>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('确定要' + (this.ispublic.checked ? '公开' : '取消公开') + '笔记本 <?php echo htmlspecialchars($notebook['id']); ?> 吗？');">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($notebook['id']); ?>">
                                            <input type="hidden" name="set_public" value="1">
                                            <label class="switch">
                                                <input type="checkbox" name="ispublic" <?php echo $db->isPublic($notebook['id']) ? 'checked' : ''; ?> onclick="if(confirm('确定要' + (this.checked ? '公开' : '取消公开') + '笔记本 <?php echo htmlspecialchars($notebook['id']); ?> 吗？')) { this.form.submit(); } else { return false; }">
                                                <span class="slider round"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td class="archive-code-cell" onclick="editArchiveCode('<?php echo htmlspecialchars($notebook['id']); ?>', '<?php echo htmlspecialchars((string)$db->getArchiveCode($notebook['id'])) ?>')"><?php echo htmlspecialchars((string)$db->getArchiveCode($notebook['id'])) ?: '未设置'; ?></td>
                                    <td>
                                        <form method="post" action="admin.php<?php echo isset($_GET['page']) ? '?page=' . (int)$_GET['page'] : ''; ?>" style="display:inline;" onsubmit="return confirm('确定要删除记事本 <?php echo htmlspecialchars($notebook['id']); ?> 吗？\n此操作不可恢复！');">
                                            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($notebook['id']); ?>">
                                            <button type="submit" class="btn btn-danger">删除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php 
                            // 如果记录少于10条，添加空行保持高度
                            $empty_rows = 10 - count($notebooks);
                            for ($i = 0; $i < $empty_rows && $empty_rows > 0; $i++): ?>
                                <tr style="height: 40px;">
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    
                    <!-- 分页导航 -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1">&laquo;</a>
                            <a href="?page=<?php echo $page - 1; ?>">&lt;</a>
                        <?php else: ?>
                            <span class="disabled">&laquo;</span>
                            <span class="disabled">&lt;</span>
                        <?php endif; ?>
                        
                        <?php
                        // 显示页码
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">&gt;</a>
                            <a href="?page=<?php echo $total_pages; ?>">&raquo;</a>
                        <?php else: ?>
                            <span class="disabled">&gt;</span>
                            <span class="disabled">&raquo;</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <p>暂无记事本记录</p>
                        <p>用户创建新记事本后将显示在此列表中</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // 归档码编辑功能
        const modal = document.getElementById('archiveCodeModal');
        const closeBtn = document.querySelector('.modal-close');
        const modalForm = document.getElementById('archiveCodeForm');
        const modalNotebookId = document.getElementById('modal-notebook-id');
        const modalArchiveCode = document.getElementById('modal-archive-code');

        function editArchiveCode(notebookId, currentArchiveCode) {
            modalNotebookId.value = notebookId;
            modalArchiveCode.value = currentArchiveCode || '';
            modal.style.display = 'block';
        }

        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        modalForm.onsubmit = function(e) {
            const archiveCode = modalArchiveCode.value.trim();
            if (archiveCode === '') {
                if (!confirm('确定要清除归档码吗？')) {
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        }

        // 添加渐入动画效果
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.card, .admin-title, .header');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html> 