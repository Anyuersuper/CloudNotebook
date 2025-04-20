<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入核心文件
require_once('system/core.php');

// 获取笔记本ID
$id = isset($_GET['id']) ? trim($_GET['id']) : '';

// 如果没有提供ID，重定向到首页
if (empty($id)) {
    header('Location: index.php');
    exit;
}

// 获取数据库实例
$db = NotebookDB::getInstance();

// 检查笔记本是否存在
if (!$db->notebookExists($id)) {
    die('笔记本不存在');
}

// 检查笔记本是否公开
if (!$db->isPublic($id)) {
    die('该笔记本未设置为公开');
}

// 获取笔记本内容
$content = $db->getNotebookContent($id);
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($id); ?> - 云笔记</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
    <script src="./js/highlight.min.js"></script>
    <script src="./js/markdown-it.min.js"></script>
    <script src="./js/markdown-bundle.js"></script>
    <script src="./js/main.js"></script>
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
            --border-radius: 12px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 15px 50px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #0f1117;
            color: #f0f2f5;
            margin: 0;
            font-family: "SF Pro Display", "SF Pro Icons", "Helvetica Neue", "Microsoft YaHei", "Segoe UI", sans-serif;
            min-height: 100vh;
        }

        .container {
            max-width: 80% !important;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
            z-index: 1;
            height: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8em;
            font-weight: 700;
            color: white;
            text-decoration: none;
            width: auto;
            flex-shrink: 0;
        }

        .logo i {
            color: var(--primary);
        }

        .logo span {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .back-link {
            color: #6e7888;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            transition: color 0.3s;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .back-link:hover {
            color: #4a6bfa;
            background: rgba(74, 107, 250, 0.1);
        }

        .content-card {
            background: #1c2033;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 200px);
        }

        .markdown-content {
            color: #f0f2f5;
            font-size: 16px;
            line-height: 1.7;
            padding: 20px;
        }

        .markdown-content h1 {
            font-size: 28px;
            color: #f0f2f5;
            margin: 0 0 24px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .markdown-content h2 {
            font-size: 24px;
            color: #f0f2f5;
            margin: 32px 0 16px;
        }

        .markdown-content h3 {
            font-size: 20px;
            color: #f0f2f5;
            margin: 24px 0 16px;
        }

        .markdown-content p {
            margin: 0 0 16px 0;
            color: #c9d1d9;
        }

        .markdown-content pre {
            background: #282c34;
            padding: 16px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 16px 0;
        }

        .markdown-content code {
            font-family: 'SF Mono', Consolas, Monaco, monospace;
            font-size: 14px;
            color: #e6e6e6;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .markdown-content pre code {
            background: transparent;
            padding: 0;
            color: #e6e6e6;
        }

        .markdown-content blockquote {
            border-left: 4px solid #4a6bfa;
            margin: 16px 0;
            padding: 8px 16px;
            background: rgba(74, 107, 250, 0.1);
            color: #c9d1d9;
        }

        .markdown-content ul,
        .markdown-content ol {
            margin: 16px 0;
            padding-left: 24px;
            color: #c9d1d9;
        }

        .markdown-content li {
            margin: 8px 0;
        }

        .markdown-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 16px 0;
            display: block;
        }

        .markdown-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            color: #c9d1d9;
        }

        .markdown-content th,
        .markdown-content td {
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            text-align: left;
        }

        .markdown-content th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            color: #f0f2f5;
        }

        .markdown-content hr {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 24px 0;
        }

        .background {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            background: linear-gradient(135deg, #0f1117 0%, #1c2033 100%);
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
        }

        .shape-1 {
            top: -200px;
            right: -200px;
            width: 600px;
            height: 600px;
            background: #4a6bfa;
        }

        .shape-2 {
            bottom: -200px;
            left: -200px;
            width: 500px;
            height: 500px;
            background: #6c63ff;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .content-card {
                padding: 20px;
                min-height: calc(100vh - 150px);
            }

            .markdown-content {
                padding: 15px;
            }

            .markdown-content h1 {
                font-size: 24px;
            }

            .markdown-content h2 {
                font-size: 20px;
            }

            .markdown-content h3 {
                font-size: 18px;
            }

            .logo span {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
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
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                返回首页
            </a>
        </header>

        <div class="content-card">
            <div class="markdown-content" id="content">
                <?php echo htmlspecialchars($content); ?>
            </div>
        </div>
    </div>

    <script>
        // 等待Markdown解析器初始化完成
        window.mdInitialized.then(() => {
            const content = document.getElementById('content');
            const markdown = content.textContent.trim();
            
            // 确保 markdown-it 正确初始化
            if (typeof markdownit !== 'undefined') {
                window.md = markdownit({
                    html: true,
                    breaks: true,
                    linkify: true,
                    typographer: true
                });
            }
            
            // 渲染 Markdown
            content.innerHTML = window.md.render(markdown);
            
            // 代码高亮
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
            });

            // 添加渐入动画
            const elements = document.querySelectorAll('.content-card, .notebook-title');
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