<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入核心文件
require_once('system/core.php');

// 初始化变量
$notebooks = [];
$message = '';
$has_searched = false;

// 处理归档码查询
if (isset($_GET['archive_code']) && !empty($_GET['archive_code'])) {
    $archive_code = trim($_GET['archive_code']);
    $has_searched = true;
    
    try {
        $db = NotebookDB::getInstance();
        $notebooks = $db->getNotebooksByArchiveCode($archive_code);
    } catch (Exception $e) {
        $message = "查询失败: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云笔记 - 归档码查询</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --triangle-left: 20px;
            --triangle-right: auto;
            --primary: #4a6bfa;
            --primary-dark: #3a56d4;
            --primary-light: rgba(74, 107, 250, 0.1);
            --secondary: #6c63ff;
            --dark: #1a1e2e;
            --darker: #151824;
            --light: #f0f2f5;
            --gray: #6e7888;
            --success: #10b981;
            --danger: #ef4444;
            --border-radius: 12px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 15px 50px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
        }
        
        body {
            background: var(--dark);
            color: var(--light);
            font-family: "SF Pro Display", "SF Pro Icons", "Helvetica Neue", "Microsoft YaHei", "Segoe UI", sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 80% !important;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
            height: auto;
            min-height: calc(100vh - 60px);
        }

        /* 背景动画 */
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
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
            width: 600px;
            height: 600px;
            top: -300px;
            right: -100px;
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            animation: float 10s ease-in-out infinite alternate;
        }

        .shape-2 {
            background: var(--secondary);
            width: 500px;
            height: 500px;
            bottom: -200px;
            left: -100px;
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation: float 12s ease-in-out infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(30px, 50px) rotate(10deg); }
        }

        /* 导航栏样式 */
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

        .logo i {
            color: white;
        }

        .logo span {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .notebook-title {
            color: var(--light);
            font-size: 1.25rem;
            font-weight: 500;
            margin: 0;
            position: relative;
            padding-left: 1.5rem;
        }

        .notebook-title:before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 2px;
            height: 1rem;
            background: var(--primary);
            border-radius: 2px;
        }

        .nav-links {
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: none;
            box-shadow: none;
        }

        .nav-link i {
            font-size: 1em;
        }

        .archive-card {
            background: var(--darker);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin: 2rem auto;
            transition: var(--transition);
            max-width: 800px;
        }

        .archive-card:hover {
            box-shadow: var(--card-shadow-hover);
        }

        .archive-title {
            display: none;
        }

        .archive-form {
            display: flex;
            gap: 1rem;
        }

        .archive-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            font-size: 1rem;
            outline: none;
            background: var(--dark);
            color: var(--light);
            transition: var(--transition);
        }

        .archive-input:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .archive-button {
            background: var(--primary);
            color: var(--light);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .archive-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .notebook-card {
            display: block;
            text-decoration: none;
            background: var(--darker);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .notebook-card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
            border-color: var(--primary);
            background: rgba(74, 107, 250, 0.05);
        }

        .admin-title {
            font-size: 1.8em;
            font-weight: 800;
            text-align: center;
            background: linear-gradient(90deg, white, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap;
        }

        .notebook-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .notebook-id {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notebook-id i {
            color: var(--primary);
        }

        .notebook-code {
            font-size: 0.875rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--dark);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
        }

        .notebook-meta {
            color: var(--gray);
            font-size: 0.875rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item i {
            color: var(--primary);
            opacity: 0.8;
        }

        .empty-message {
            text-align: center;
            padding: 2rem;
            background: var(--darker);
            border-radius: var(--border-radius);
            color: var(--gray);
            border: 2px dashed rgba(255, 255, 255, 0.1);
        }

        .message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @media (max-width: 640px) {
            .archive-form {
                flex-direction: column;
            }
            
            .archive-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- 背景动画 -->
    <div class="background">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="container">
        <header class="header">
            <a href="index.php" class="logo">
                <i class="fas fa-book"></i>
                <span>云笔记</span>
            </a>
            <div class="top-nav">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> 返回首页
                </a>
            </div>
        </header>
        
        <div class="admin-title" style="margin-bottom: 30px;">
            归档码查询
        </div>

        <div class="archive-card">
            <div class="archive-title">归档码查询</div>
            <form method="get" class="archive-form">
                <input type="text" 
                       name="archive_code" 
                       class="archive-input" 
                       placeholder="请输入笔记本的归档码" 
                       value="<?php echo isset($_GET['archive_code']) ? htmlspecialchars($_GET['archive_code']) : ''; ?>" 
                       required>
                <button type="submit" class="archive-button">查找笔记本</button>
            </form>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($has_searched): ?>
            <?php if (!empty($notebooks)): ?>
                <?php foreach ($notebooks as $notebook): ?>
                    <a href="notebook.php?id=<?php echo urlencode($notebook['id']); ?>" class="notebook-card">
                        <div class="notebook-header">
                            <div class="notebook-id">
                                <i class="fas fa-book"></i>
                                <?php echo htmlspecialchars($notebook['id']); ?>
                            </div>
                            <div class="notebook-code">
                                <i class="fas fa-key"></i>
                                <?php echo htmlspecialchars($_GET['archive_code']); ?>
                            </div>
                        </div>
                        <div class="notebook-meta">
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                创建于 <?php echo date('Y年m月d日', strtotime($notebook['created_at'])); ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-pen"></i>
                                更新于 <?php echo date('Y年m月d日', strtotime($notebook['updated_at'])); ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-message">
                    未找到使用该归档码的笔记本
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
