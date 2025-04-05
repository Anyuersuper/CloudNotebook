<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入核心文件
require_once('system/core.php');
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云笔记 - 安全、简洁的在线记事本</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --border-radius: 12px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 15px 50px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "SF Pro Display", "SF Pro Icons", "Helvetica Neue", "Microsoft YaHei", "Segoe UI", sans-serif;
            background-color: var(--dark);
            color: white;
            line-height: 1.6;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 背景几何元素 */
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 60px;
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

        .nav-link, .admin-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: var(--transition);
            padding: 8px 15px;
            border-radius: 8px;
        }

        .nav-link:hover, .admin-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 80px;
            gap: 60px;
        }

        .hero-content {
            flex: 1;
        }

        .hero-title {
            font-size: 3.5em;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
            background: linear-gradient(90deg, white, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2em;
            color: var(--gray);
            margin-bottom: 40px;
        }

        .card {
            background-color: var(--darker);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            flex: 1;
        }

        .card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-5px);
        }

        .card-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--light);
            font-size: 1.1em;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 1em;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 107, 250, 0.3);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .btn {
            display: inline-block;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(74, 107, 250, 0.4);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(74, 107, 250, 0.6);
        }

        .btn-full {
            width: 100%;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .feature-card {
            background-color: var(--darker);
            border-radius: var(--border-radius);
            padding: 30px;
            transition: var(--transition);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-shadow);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .feature-icon {
            font-size: 2em;
            margin-bottom: 20px;
            width: 70px;
            height: 70px;
            line-height: 70px;
            text-align: center;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(74, 107, 250, 0.2), rgba(108, 99, 255, 0.2));
            margin: 0 auto 20px;
            color: var(--primary);
        }

        .feature-title {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: white;
        }

        .feature-text {
            color: var(--gray);
            font-size: 0.95em;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-text {
            color: var(--gray);
            font-size: 0.9em;
        }

        .footer-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .footer-link {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9em;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: white;
        }

        @media (max-width: 992px) {
            .hero {
                flex-direction: column;
                gap: 40px;
            }
            .hero-title {
                font-size: 2.8em;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            .header {
                flex-direction: column;
                gap: 20px;
                margin-bottom: 40px;
            }
            .hero-title {
                font-size: 2.2em;
            }
            .features {
                grid-template-columns: 1fr;
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
            <nav class="top-nav">
                <a href="#features" class="nav-link">功能特点</a>
                <a href="system/admin.php" class="nav-link">管理入口</a>
            </nav>
        </header>

        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">安全、简洁的在线笔记本</h1>
                <p class="hero-subtitle">随时随地记录您的想法，支持Markdown格式，密码保护确保您的数据安全。无需注册，即刻开始使用。</p>
            </div>
            <div class="card">
                <h2 class="card-title">开始使用</h2>
                <form id="noteForm" action="notebook.php" method="get">
                    <div class="form-group">
                        <label for="noteId" class="form-label">笔记本ID</label>
                        <input type="text" id="noteId" name="id" class="form-input" placeholder="输入笔记本ID，新ID将创建新笔记本" required>
                    </div>
                    <button type="submit" class="btn btn-full">进入笔记本</button>
                </form>
            </div>
        </section>

        <section id="features" class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3 class="feature-title">安全保密</h3>
                <p class="feature-text">所有笔记本均采用密码保护，数据加密存储，确保您的信息安全无忧。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-code"></i>
                </div>
                <h3 class="feature-title">Markdown支持</h3>
                <p class="feature-text">完整支持Markdown格式，让您的笔记排版更加美观，阅读更加舒适。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="feature-title">快速访问</h3>
                <p class="feature-text">简单的ID系统，无需记住复杂网址，随时随地轻松访问您的重要笔记。</p>
            </div>
        </section>

        <footer class="footer">
            <p class="footer-text">© <?php echo date('Y'); ?> 云笔记 - 安全、简洁、高效的在线记事工具 - By欲儿</p>
            <div class="footer-links">
                <a href="#" class="footer-link">使用条款</a>
                <a href="#" class="footer-link">隐私政策</a>
                <a href="#" class="footer-link">联系我们</a>
            </div>
        </footer>
    </div>

    <script src="js/main.js"></script>
</body>
</html> 