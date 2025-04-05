<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置会话超时时间（单位：秒）
ini_set('session.gc_maxlifetime', 300); // 5分钟后会话过期
session_set_cookie_params(300); // 设置cookie生命周期为5分钟

// 只有在会话尚未启动时才启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 处理注销请求
if (isset($_GET['logout'])) {
    $id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';
    if (!empty($id)) {
        // 清除特定笔记本的认证状态
        if (isset($_SESSION['auth_' . $id])) {
            unset($_SESSION['auth_' . $id]);
        }
        // 确保会话数据被写入
        session_write_close();
        
        // 重定向回笔记本页面，并添加一个时间戳参数防止缓存
        header('Location: ../notebook.php?id=' . urlencode($id) . '&t=' . time());
        exit;
    }
}

// 引入密码函数兼容库和数据库工具
require_once(__DIR__.'/password_compat.php');
require_once(__DIR__.'/db.php');

$id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';

if (empty($id)) {
    header('Location: ../index.php');
    exit;
}

// 创建数据库连接
try {
    $db = NotebookDB::getInstance();
} catch (Exception $e) {
    die("无法连接到数据库: " . $e->getMessage());
}

// 检查笔记本是否存在
$is_new = !$db->notebookExists($id);
$content = '';

// 如果不是新记事本，但数据库中又找不到，说明该记事本可能已被删除
if (!$is_new) {
    // 从数据库获取内容
    $content = $db->getNotebookContent($id);
} else {
    // 清除可能存在的会话认证状态
    if (isset($_SESSION['auth_' . $id])) {
        unset($_SESSION['auth_' . $id]);
    }
}

// 检查是否已经认证
$is_authenticated = isset($_SESSION['auth_' . $id]) && $_SESSION['auth_' . $id] === true;

// 如果已认证，检查是否设置了总是需要密码
if ($is_authenticated && !$is_new) {
    try {
        $always_require_password = $db->getAlwaysRequirePassword($id);
        if ($always_require_password) {
            // 如果设置了总是需要密码，且是通过GET请求访问的，且不是刚刚验证过的请求，清除认证状态
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['logout']) && !isset($_GET['verified'])) {
                unset($_SESSION['auth_' . $id]);
                $is_authenticated = false;
            }
        }
    } catch (Exception $e) {
        // 忽略错误，默认不需要每次输入密码
        $always_require_password = false;
    }
} else {
    // 默认设置为false
    $always_require_password = false;
}

// 获取URL参数中的verified状态
$verified = isset($_GET['verified']) && $_GET['verified'] == '1';

// 处理"总是需要密码"的情况 - 当通过GET访问且未退出登录且未从验证页面重定向时
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $is_authenticated && $always_require_password && !$verified) {
    // 清除身份验证状态，强制重新输入密码
    if (isset($_SESSION['auth_' . $id])) {
        $_SESSION['auth_' . $id] = false;
        unset($_SESSION['auth_' . $id]);
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云笔记 - <?php echo htmlspecialchars($id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="js/github-dark.css">
    
    <!-- 调试脚本，检查路径问题 -->
    <script>
        console.log('当前页面URL:', window.location.href);
        console.log('当前页面路径:', window.location.pathname);
        console.log('脚本加载基础路径:', new URL('.', window.location.href).href);
    </script>
    
    <!-- 先加载库文件，确保在DOM加载前就准备好 -->
    <script src="./js/highlight.min.js" onerror="console.error('highlight.min.js 加载失败')"></script>
    <script src="./js/markdown-it.min.js" onerror="console.error('markdown-it.min.js 加载失败，错误原因：', event)"></script>
    <script src="./js/markdown-fix.js" onerror="console.error('markdown-fix.js 加载失败')"></script>
    
    <!-- 尝试使用绝对路径加载 -->
    <script>
        if (typeof markdownit === 'undefined') {
            console.log('尝试使用绝对路径加载markdown-it');
            var basePath = window.location.origin + '/';
            var scriptPaths = [
                './js/markdown-it.min.js',
                basePath + 'js/markdown-it.min.js',
                '../js/markdown-it.min.js',
                'js/markdown-it.min.js'
            ];
            
            function loadScript(index) {
                if (index >= scriptPaths.length) {
                    console.error('所有路径都尝试失败，将尝试CDN');
                    // 尝试从CDN加载
                    var cdnScript = document.createElement('script');
                    cdnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/markdown-it/12.3.2/markdown-it.min.js';
                    cdnScript.onerror = function() {
                        console.error('CDN加载也失败');
                    };
                    document.head.appendChild(cdnScript);
                    return;
                }
                
                console.log('尝试路径:', scriptPaths[index]);
                var script = document.createElement('script');
                script.src = scriptPaths[index];
                script.onload = function() {
                    console.log('成功加载markdown-it，路径:', scriptPaths[index]);
                };
                script.onerror = function() {
                    console.error('路径加载失败:', scriptPaths[index]);
                    loadScript(index + 1);
                };
                document.head.appendChild(script);
            }
            
            loadScript(0);
        }
    </script>
    
    <!-- 测试和重新加载功能 -->
    <script>
        function showDebugInfo() {
            document.getElementById('debug-info').style.display = 'block';
        }
        
        function testMarkdownIt() {
            var testResult = document.getElementById('script-test');
            try {
                if (typeof markdownit === 'undefined') {
                    testResult.textContent = 'markdownit未定义';
                    testResult.style.color = 'red';
                } else if (!window.md) {
                    testResult.textContent = 'window.md实例未创建';
                    testResult.style.color = 'orange';
                    // 尝试创建实例
                    window.md = markdownit();
                    testResult.textContent += '，已尝试创建';
                } else {
                    var testMd = '# 测试标题\n- 列表项1\n- 列表项2';
                    var result = window.md.render(testMd);
                    testResult.innerHTML = '渲染成功: <span style="color:#4a6bfa">√</span>';
                    console.log('测试渲染结果:', result);
                }
            } catch (error) {
                testResult.textContent = '测试失败: ' + error.message;
                testResult.style.color = 'red';
                console.error('测试MarkdownIt失败:', error);
            }
        }
        
        function reloadMarkdownIt() {
            var errorDisplay = document.getElementById('load-errors');
            errorDisplay.textContent = '正在重新加载...';
            
            try {
                // 移除已存在的脚本标签
                var existingScripts = document.querySelectorAll('script[src*="markdown-it"]');
                existingScripts.forEach(function(script) {
                    script.parentNode.removeChild(script);
                });
                
                // 创建新的脚本标签
                var script = document.createElement('script');
                script.src = './js/markdown-it.min.js';
                script.onload = function() {
                    errorDisplay.textContent = '重新加载成功';
                    errorDisplay.style.color = 'green';
                    // 重新初始化
                    if (typeof initMarkdown === 'function') {
                        initMarkdown();
                    }
                };
                script.onerror = function(e) {
                    errorDisplay.textContent = '重新加载失败';
                    errorDisplay.style.color = 'red';
                    
                    // 尝试从CDN加载
                    var cdnScript = document.createElement('script');
                    cdnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/markdown-it/12.3.2/markdown-it.min.js';
                    cdnScript.onload = function() {
                        errorDisplay.textContent = '从CDN加载成功';
                        errorDisplay.style.color = 'green';
                        if (typeof initMarkdown === 'function') {
                            initMarkdown();
                        }
                    };
                    cdnScript.onerror = function() {
                        errorDisplay.textContent = 'CDN加载也失败，请检查网络';
                        errorDisplay.style.color = 'red';
                    };
                    document.head.appendChild(cdnScript);
                };
                document.head.appendChild(script);
            } catch (error) {
                errorDisplay.textContent = '重载出错: ' + error.message;
                errorDisplay.style.color = 'red';
            }
        }
        
        // 在DOM加载后设置当前URL
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('current-url').textContent = window.location.href;
            
            // 默认显示调试面板
            showDebugInfo();
            
            // 自动测试markdownit（延迟1秒以确保脚本加载完毕）
            setTimeout(testMarkdownIt, 1000);
        });
    </script>
    
    <!-- 立即执行初始化函数 -->
    <script>
        // 立即执行初始化函数
        (function() {
            // 检查库是否加载成功
            var markdownitLoaded = typeof markdownit !== 'undefined';
            var highlightLoaded = typeof hljs !== 'undefined';
            
            console.log('库加载检查 - markdownit:', markdownitLoaded ? '成功' : '失败');
            console.log('库加载检查 - highlight.js:', highlightLoaded ? '成功' : '失败');
            
            // 如果markdown-it加载成功，创建全局实例
            if (markdownitLoaded) {
                try {
                    console.log('创建markdown-it实例');
                    window.md = markdownit({
                        html: true,
                        xhtmlOut: true,
                        breaks: false,
                        linkify: true,
                        typographer: true
                    });
                    console.log('markdown-it实例创建成功');
                } catch (e) {
                    console.error('创建markdown-it实例失败:', e);
                }
            }
            
            // 在DOM加载后显示调试信息
            window.addEventListener('DOMContentLoaded', function() {
                try {
                    const debugInfo = document.getElementById('debug-info');
                    const jsPath = document.getElementById('js-path');
                    const libsStatus = document.getElementById('libs-status');
                    
                    if (debugInfo && jsPath && libsStatus) {
                        debugInfo.style.display = 'block';
                        jsPath.textContent = window.location.pathname + ' => js/';
                        libsStatus.textContent = 
                            'markdown-it: ' + (markdownitLoaded ? '已加载' : '未加载') + 
                            ', highlight.js: ' + (highlightLoaded ? '已加载' : '未加载') +
                            ', md实例: ' + (window.md ? '已创建' : '未创建');
                    }
                    
                    // 初始化highlight.js配置
                    if (highlightLoaded) {
                        hljs.configure({
                            languages: ['javascript', 'php', 'python', 'css', 'html', 'xml', 'json', 'bash', 'markdown']
                        });
                        console.log('highlight.js配置成功');
                    }
                } catch (error) {
                    console.error('初始化调试信息时出错:', error);
                }
            });
        })();
    </script>
    
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

        /* 头部导航区样式 */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .left-section {
            display: flex;
            align-items: center;
            gap: 20px;
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

        .notebook-title {
            font-size: 1.5em;
            font-weight: 700;
            background: linear-gradient(90deg, white, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .back-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: var(--transition);
            padding: 8px 15px;
            border-radius: 8px;
        }

        .back-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* 卡片样式 */
        .card {
            background-color: var(--darker);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 30px;
        }

        .card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-5px);
        }

        .card-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }

        /* 密码输入部分 */
        .password-section {
            max-width: 450px;
            margin: 0 auto;
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

        .btn {
            display: inline-block;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 12px 20px;
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

        .error-message {
            color: var(--danger);
            margin-top: 15px;
            font-size: 0.95em;
        }

        /* 按钮居中显示 */
        .center-btn {
            display: block;
            width: 100%;
            max-width: 220px;
            margin: 0 auto;
            text-align: center;
        }

        /* 编辑器部分 */
        .editor-section {
            height: calc(100vh - 170px);
            min-height: 500px;
        }

        .editor-container {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .editor-status {
            flex: 1;
        }

        .editor-actions {
            display: flex;
            gap: 15px;
        }

        /* 预览区域缩放控制 */
        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 15px;
        }

        .zoom-button {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .zoom-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .zoom-level {
            font-size: 0.9em;
            color: var(--gray);
            min-width: 40px;
            text-align: center;
        }

        .save-status {
            font-size: 0.95em;
            color: var(--gray);
        }

        .editor-main {
            display: flex;
            flex: 1;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            background-color: var(--darker);
        }

        .editor-input {
            flex: 1;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        #editor {
            width: 100%;
            height: 100%;
            border: none;
            resize: none;
            padding: 20px;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 15px;
            line-height: 1.6;
            outline: none;
            background-color: rgba(255, 255, 255, 0.03);
            color: white;
        }

        .editor-preview {
            flex: 1;
            padding: 16px;
            overflow: auto;
            background-color: rgba(255, 255, 255, 0.03);
            word-wrap: break-word;
            scroll-behavior: smooth;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }

        /* 确保预览区滚动时的平滑效果 */
        .editor-preview::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .editor-preview::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .editor-preview::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .editor-preview::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        #preview {
            line-height: 1.7;
            color: var(--light);
            min-width: 100%;
            transition: transform 0.2s ease;
            transform-origin: top left;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            font-size: 15px;
            padding: 0 8px;
        }

        /* GitHub风格的预览容器样式 */
        .editor-preview {
            flex: 1;
            padding: 16px;
            overflow: auto;
            background-color: rgba(255, 255, 255, 0.03);
            word-wrap: break-word;
            scroll-behavior: smooth;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }

        /* GitHub风格的标题样式 */
        #preview h1, #preview h2, #preview h3, #preview h4, #preview h5, #preview h6 {
            color: white;
            margin-top: 24px;
            margin-bottom: 16px;
            line-height: 1.25;
            font-weight: 600;
        }

        #preview h1 {
            font-size: 2em;
            padding-bottom: 0.3em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #preview h2 {
            font-size: 1.5em;
            padding-bottom: 0.3em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #preview h3 {
            font-size: 1.25em;
        }

        #preview h4 {
            font-size: 1em;
        }

        #preview h5 {
            font-size: 0.875em;
        }

        #preview h6 {
            font-size: 0.85em;
            color: var(--gray);
        }

        /* GitHub风格的段落和文本样式 */
        #preview p {
            margin-top: 0;
            margin-bottom: 16px;
        }

        #preview a {
            color: #58a6ff;
            text-decoration: none;
        }

        #preview a:hover {
            text-decoration: underline;
        }

        /* GitHub风格的列表样式 */
        #preview ul, #preview ol {
            padding-left: 2em;
            margin-top: 0;
            margin-bottom: 16px;
        }

        #preview li {
            margin-bottom: 4px;
        }

        #preview li + li {
            margin-top: 0.25em;
        }

        /* GitHub风格的代码样式 */
        #preview code {
            background-color: rgba(110, 118, 129, 0.2);
            padding: 0.2em 0.4em;
            margin: 0;
            border-radius: 6px;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.85em;
        }

        #preview pre {
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 6px;
            padding: 16px;
            overflow: auto;
            margin: 16px 0;
            line-height: 1.45;
        }

        #preview pre code {
            background-color: transparent;
            padding: 0;
            margin: 0;
            border-radius: 0;
            font-size: 0.85em;
            white-space: pre;
        }

        /* GitHub风格的引用块样式 */
        #preview blockquote {
            border-left: 4px solid #30363d;
            padding: 0 1em;
            margin: 0 0 16px 0;
            color: var(--gray);
            background-color: rgba(110, 118, 129, 0.1);
            border-radius: 0 6px 6px 0;
            padding: 10px 16px;
        }

        #preview blockquote > :first-child {
            margin-top: 0;
        }

        #preview blockquote > :last-child {
            margin-bottom: 0;
        }

        /* GitHub风格的表格样式 */
        #preview table {
            border-collapse: collapse;
            width: 100%;
            margin: 16px 0;
            display: block;
            overflow-x: auto;
        }

        #preview table th, #preview table td {
            border: 1px solid #30363d;
            padding: 6px 13px;
        }

        #preview table th {
            background-color: rgba(110, 118, 129, 0.1);
            font-weight: 600;
        }

        #preview table tr {
            background-color: transparent;
            border-top: 1px solid #30363d;
        }

        #preview table tr:nth-child(2n) {
            background-color: rgba(110, 118, 129, 0.1);
        }

        /* GitHub风格的水平线样式 */
        #preview hr {
            height: 0.25em;
            padding: 0;
            margin: 24px 0;
            background-color: #30363d;
            border: 0;
        }

        /* GitHub风格的图片样式 */
        #preview img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 10px 0;
            display: block;
        }

        /* GitHub风格的任务列表样式 */
        #preview input[type="checkbox"] {
            margin-right: 0.5em;
            vertical-align: middle;
        }

        /* GitHub风格的强调和加粗样式 */
        #preview strong {
            font-weight: 600;
        }

        #preview em {
            font-style: italic;
        }

        /* GitHub风格的删除线样式 */
        #preview del {
            text-decoration: line-through;
        }

        /* 设置菜单样式 */
        .settings-dropdown {
            position: relative;
        }

        .settings-btn {
            background: linear-gradient(90deg, var(--gray), #566074);
        }

        .settings-content {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            background-color: var(--darker);
            min-width: 250px;
            box-shadow: var(--card-shadow);
            padding: 15px;
            z-index: 10;
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .settings-item {
            margin-bottom: 15px;
            color: var(--light);
        }

        .settings-description {
            font-size: 0.85em;
            color: var(--gray);
            margin: 8px 0 15px 0;
        }

        .mini-btn {
            padding: 8px 15px;
            font-size: 0.9em;
        }

        .show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            .notebook-title {
                font-size: 1.1em;
            }
            .header {
                flex-direction: column;
                gap: 20px;
                margin-bottom: 30px;
            }
            .left-section {
                width: 100%;
                justify-content: space-between;
            }
            .card {
                padding: 25px;
            }
            .editor-main {
                flex-direction: column;
            }
            .editor-input {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            .editor-section {
                height: auto;
            }
            .editor-main {
                height: 600px;
            }
            .editor-input, .editor-preview {
                height: 300px;
            }
            .editor-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .editor-actions {
                width: 100%;
                justify-content: space-between;
            }
            .zoom-controls {
                margin-bottom: 10px;
            }
        }

        /* GitHub风格的徽章样式 */
        .github-badges {
            text-align: center;
            margin: 15px auto;
            padding: 8px 12px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 6px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .badge-img {
            display: inline-block;
            margin: 0 4px;
            vertical-align: middle;
            max-height: 26px;
            border-radius: 4px;
            transition: transform 0.2s ease;
        }
        
        .badge-img:hover {
            transform: translateY(-2px);
        }

        .github-badges-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin: 15px auto;
            padding: 8px 12px;
            background-color: rgba(255, 255, 255, 0.03);
            border-radius: 6px;
            max-width: 600px;
        }

        /* 处理div[align="center"]内的徽章容器 */
        div[align="center"] .github-badges-container {
            background-color: transparent;
            padding: 5px;
            margin: 10px auto;
        }
        
        /* 处理div[align="center"]内的所有内容 */
        div[align="center"] {
            text-align: center;
            margin: 20px auto;
            display: block;
        }
        
        div[align="center"] > p {
            text-align: center;
            margin: 10px auto;
        }
        
        /* 确保div[align="center"]内的图片正确居中 */
        div[align="center"] img {
            display: inline-block;
            margin: 5px;
        }

        /* GitHub风格的图片网格 */
        .image-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin: 20px auto;
        }

        .img-wrapper {
            cursor: zoom-in;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            overflow: hidden;
            transition: all 0.2s ease;
            max-width: 100%;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .img-wrapper:hover {
            transform: scale(1.02);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* 图片悬停放大效果 */
        .img-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: zoom-out;
        }
        
        /* 优化单张图片显示 */
        #preview p img:only-child:not(.badge-img) {
            display: block;
            margin: 20px auto;
            max-width: 100%;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* GitHub风格的README布局增强 */
        .readme-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #30363d;
        }

        .readme-navigation {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
            padding: 10px 0;
            border-top: 1px solid rgba(48, 54, 61, 0.5);
            border-bottom: 1px solid rgba(48, 54, 61, 0.5);
        }

        .readme-navigation a {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.05);
            transition: background-color 0.2s ease;
        }

        .readme-navigation a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        /* 代码块语言标签 */
        .code-language {
            position: absolute;
            top: 0;
            right: 0;
            padding: 2px 8px;
            font-size: 12px;
            color: #7d8590;
            background-color: rgba(27, 31, 35, 0.5);
            border-bottom-left-radius: 6px;
        }

        /* 增强代码块显示 */
        #preview pre {
            position: relative;
            border: 1px solid #30363d;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        /* 支持HTML alignment属性 */
        [align="center"] {
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        [align="right"] {
            text-align: right;
        }

        [align="left"] {
            text-align: left;
        }

        /* 增强任务列表显示 */
        #preview ul.contains-task-list {
            list-style-type: none;
            padding-left: 0;
        }

        #preview ul.contains-task-list li {
            position: relative;
            padding-left: 2em;
            margin-bottom: 8px;
        }

        #preview ul.contains-task-list input[type="checkbox"] {
            position: absolute;
            left: 0;
            top: 0.3em;
            margin: 0;
        }

        /* GitHub风格的复选框样式增强 */
        .task-list-item {
            list-style-type: none;
            margin-left: -2em;
        }

        .task-list-item input[type="checkbox"] {
            margin-right: 8px;
            vertical-align: middle;
            position: relative;
            top: -1px;
        }

        /* GitHub风格的笔记和警告容器 */
        .markdown-alert {
            padding: 10px 16px;
            margin: 16px 0;
            border-left: 4px solid;
            border-radius: 0 6px 6px 0;
        }

        .markdown-alert.note {
            background-color: rgba(33, 131, 188, 0.1);
            border-left-color: #2183BC;
        }

        .markdown-alert.tip, .markdown-alert.hint {
            background-color: rgba(16, 185, 129, 0.1);
            border-left-color: #10B981;
        }

        .markdown-alert.warning, .markdown-alert.caution, .markdown-alert.attention {
            background-color: rgba(234, 179, 8, 0.1);
            border-left-color: #EAB308;
        }

        .markdown-alert.danger, .markdown-alert.error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left-color: #EF4444;
        }

        /* GitHub风格的表格增强 */
        #preview table {
            margin: 16px 0;
            font-size: 0.95em;
            overflow-x: auto;
            border-collapse: collapse;
            width: 100%;
            display: block;
            overflow-x: auto;
        }

        #preview table th, #preview table td {
            padding: 8px 12px;
            border: 1px solid #30363d;
        }

        #preview table th {
            font-weight: 600;
            background-color: rgba(110, 118, 129, 0.1);
        }

        #preview table tr {
            background-color: transparent;
            border-top: 1px solid #30363d;
        }

        #preview table tr:nth-child(2n) {
            background-color: rgba(110, 118, 129, 0.1);
        }

        /* 特殊语法高亮容器 */
        .hljs {
            background-color: rgba(0, 0, 0, 0.2) !important;
            padding: 1em !important;
            border-radius: 6px !important;
            font-size: 0.9em !important;
        }

        /* 键盘按键样式 */
        #preview kbd {
            display: inline-block;
            padding: 3px 5px;
            font-size: 0.85em;
            line-height: 1;
            color: #c9d1d9;
            vertical-align: middle;
            background-color: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            box-shadow: inset 0 -1px 0 #30363d;
            font-family: SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
        }

        /* 错误提示样式 */
        .markdown-error {
            color: #ef4444;
            padding: 15px;
            margin: 10px 0;
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- 调试信息区域 -->
    <div id="debug-info" style="position: fixed; bottom: 10px; right: 10px; background: rgba(0, 0, 0, 0.8); color: #fff; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-width: 400px; max-height: 300px; overflow: auto; z-index: 9999; display: none;">
        <h4 style="margin: 0 0 5px 0; color: #4a6bfa;">调试信息</h4>
        <p><strong>PHP路径:</strong> <span id="php-path"><?php echo dirname($_SERVER['PHP_SELF']); ?></span> <button onclick="copyText('php-path')" style="background: #333; color: white; border: none; padding: 1px 4px; border-radius: 2px; font-size: 10px; cursor: pointer; margin-left: 5px;">复制</button></p>
        <p><strong>绝对URL:</strong> <span id="absolute-url"><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?></span> <button onclick="copyText('absolute-url')" style="background: #333; color: white; border: none; padding: 1px 4px; border-radius: 2px; font-size: 10px; cursor: pointer; margin-left: 5px;">复制</button></p>
        <p><strong>JS路径:</strong> <span id="js-path">未检测</span> <button onclick="copyText('js-path')" style="background: #333; color: white; border: none; padding: 1px 4px; border-radius: 2px; font-size: 10px; cursor: pointer; margin-left: 5px;">复制</button></p>
        <p><strong>库状态:</strong> <span id="libs-status">未检测</span></p>
        <p><strong>当前URL:</strong> <span id="current-url">未检测</span> <button onclick="copyText('current-url')" style="background: #333; color: white; border: none; padding: 1px 4px; border-radius: 2px; font-size: 10px; cursor: pointer; margin-left: 5px;">复制</button></p>
        <p><strong>脚本测试:</strong> <span id="script-test">未测试</span></p>
        <p><strong>加载错误:</strong> <span id="load-errors">无</span></p>
        <p><strong>脚本标签:</strong> <span id="script-tags">未检测</span></p>
        <div style="margin-top: 10px; display: flex; gap: 5px;">
            <button onclick="testMarkdownIt()" style="background: #4a6bfa; color: white; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer;">测试MarkdownIt</button>
            <button onclick="reloadMarkdownIt()" style="background: #6c63ff; color: white; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer;">重新加载</button>
            <button onclick="document.getElementById('debug-info').style.display='none'" style="background: #555; color: white; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer;">关闭</button>
        </div>
    </div>

    <div class="background">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="container">
        <header class="header">
            <div class="left-section">
                <a href="index.php" class="logo">
                    <i class="fas fa-book"></i>
                    <span>云笔记</span>
                </a>
                <h1 class="notebook-title"><?php echo htmlspecialchars($id); ?> 笔记本</h1>
            </div>
            <div class="nav-links">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> 返回首页
                </a>
                <?php if ($is_authenticated): ?>
                <a href="notebook.php?id=<?php echo urlencode($id); ?>&logout=1" class="back-link">
                    <i class="fas fa-sign-out-alt"></i> 退出登录
                </a>
                <?php endif; ?>
            </div>
        </header>

        <?php if (!$is_authenticated): ?>
        <div id="password-section" class="card password-section">
            <h2 class="card-title"><?php echo $is_new ? '创建新笔记本' : '输入密码'; ?></h2>
            <div class="form-container">
                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" id="password" class="form-input" required>
                </div>
                <?php if ($is_new): ?>
                <div class="form-group">
                    <label for="confirm-password" class="form-label">确认密码</label>
                    <input type="password" id="confirm-password" class="form-input" required>
                </div>
                <?php endif; ?>
                <button id="submit-password" class="btn center-btn"><?php echo $is_new ? '创建笔记本' : '验证密码'; ?></button>
                <div id="password-error" class="error-message"></div>
            </div>
        </div>
        <?php else: ?>
        <div id="editor-section" class="editor-section">
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-status">
                        <div id="save-status" class="save-status"></div>
                    </div>
                    <div class="editor-actions">
                        <div class="zoom-controls">
                            <button id="zoom-out" class="zoom-button" title="缩小">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <div id="zoom-level" class="zoom-level">100%</div>
                            <button id="zoom-in" class="zoom-button" title="放大">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button id="zoom-reset" class="zoom-button" title="重置缩放">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                        <button id="save-button" class="btn">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <div class="settings-dropdown">
                            <button id="settings-button" class="btn settings-btn">
                                <i class="fas fa-cog"></i> 设置
                            </button>
                            <div id="settings-menu" class="settings-content">
                                <div class="settings-item">
                                    <label>
                                        <input type="checkbox" id="always-require-password" <?php echo $db->getAlwaysRequirePassword($id) ? 'checked' : ''; ?>>
                                        总是要求密码
                                    </label>
                                    <div class="settings-description">启用此选项后，每次访问笔记本都需要输入密码</div>
                                    <button id="save-settings" class="btn mini-btn">保存设置</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="editor-main">
                    <div class="editor-input">
                        <textarea id="editor" spellcheck="false"><?php echo htmlspecialchars($content); ?></textarea>
                    </div>
                    <div class="editor-preview" id="preview">
                        <!-- 预览内容将在此显示 -->
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // 传递PHP变量到JavaScript
        var noteId = "<?php echo $id; ?>";
        var isNew = <?php echo $is_new ? 'true' : 'false'; ?>;
        var isAuthenticated = <?php echo $is_authenticated ? 'true' : 'false'; ?>;
        var useDatabase = true; // 指示使用数据库模式
        
        // 设置菜单功能
        document.addEventListener('DOMContentLoaded', function() {
            // 添加渐入动画效果
            const elements = document.querySelectorAll('.card, .notebook-title, .header, .editor-main');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // 处理退出登录按钮点击
            const logoutLink = document.querySelector('a[href*="logout=1"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    if(confirm('确定要退出笔记本吗？未保存的内容将丢失。')) {
                        window.location.href = this.getAttribute('href');
                    }
                    e.preventDefault();
                });
            }
            
            const settingsButton = document.getElementById('settings-button');
            const settingsMenu = document.getElementById('settings-menu');
            const saveSettingsButton = document.getElementById('save-settings');
            const alwaysRequirePasswordCheckbox = document.getElementById('always-require-password');
            
            if (settingsButton && settingsMenu) {
                // 点击设置按钮显示/隐藏设置菜单
                settingsButton.addEventListener('click', function() {
                    settingsMenu.classList.toggle('show');
                });
                
                // 点击页面其他地方关闭设置菜单
                window.addEventListener('click', function(event) {
                    if (!event.target.matches('.settings-btn') && !settingsMenu.contains(event.target)) {
                        if (settingsMenu.classList.contains('show')) {
                            settingsMenu.classList.remove('show');
                        }
                    }
                });
                
                // 保存设置
                if (saveSettingsButton && alwaysRequirePasswordCheckbox) {
                    saveSettingsButton.addEventListener('click', function() {
                        const alwaysRequirePassword = alwaysRequirePasswordCheckbox.checked;
                        
                        const formData = new FormData();
                        formData.append('action', 'update_settings');
                        formData.append('id', noteId);
                        formData.append('always_require_password', alwaysRequirePassword ? '1' : '0');
                        
                        fetch('./system/api.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const saveStatus = document.createElement('div');
                                saveStatus.textContent = '设置已保存';
                                saveStatus.style.color = 'var(--success)';
                                saveStatus.style.marginTop = '10px';
                                
                                const existingStatus = settingsMenu.querySelector('.save-success');
                                if (existingStatus) {
                                    existingStatus.remove();
                                }
                                
                                saveStatus.className = 'save-success';
                                settingsMenu.appendChild(saveStatus);
                                
                                setTimeout(() => {
                                    saveStatus.remove();
                                    settingsMenu.classList.remove('show');
                                }, 1500);
                            } else {
                                alert('保存设置失败: ' + (data.message || '未知错误'));
                            }
                        })
                        .catch(error => {
                            alert('保存设置时发生错误');
                            console.error(error);
                        });
                    });
                }
            }
            
            // 预览区域缩放功能
            if (isAuthenticated) {
                const preview = document.getElementById('preview');
                const zoomIn = document.getElementById('zoom-in');
                const zoomOut = document.getElementById('zoom-out');
                const zoomReset = document.getElementById('zoom-reset');
                const zoomLevelDisplay = document.getElementById('zoom-level');
                
                if (preview && zoomIn && zoomOut && zoomReset && zoomLevelDisplay) {
                    // 缩放设置
                    const MIN_ZOOM = 50;
                    const MAX_ZOOM = 200;
                    const STEP = 10;
                    let zoomLevel = 100;
                    
                    // 应用缩放
                    function applyZoom() {
                        preview.style.transform = `scale(${zoomLevel / 100})`;
                        preview.style.transformOrigin = 'top left';
                        zoomLevelDisplay.textContent = `${zoomLevel}%`;
                        
                        // 保存缩放级别到localStorage
                        localStorage.setItem(`zoom_${noteId}`, zoomLevel);
                        
                        return zoomLevel;
                    }
                    
                    // 全局暴露缩放功能，以便其他脚本可以使用
                    window.applyZoom = applyZoom;
                    
                    // 载入保存的缩放级别
                    try {
                        const savedZoom = localStorage.getItem(`zoom_${noteId}`);
                        if (savedZoom) {
                            zoomLevel = parseInt(savedZoom);
                            applyZoom();
                        }
                    } catch (e) {
                        console.error('读取缩放级别时出错:', e);
                    }
                    
                    // 放大
                    zoomIn.addEventListener('click', function() {
                        if (zoomLevel < MAX_ZOOM) {
                            zoomLevel += STEP;
                            applyZoom();
                        }
                    });
                    
                    // 缩小
                    zoomOut.addEventListener('click', function() {
                        if (zoomLevel > MIN_ZOOM) {
                            zoomLevel -= STEP;
                            applyZoom();
                        }
                    });
                    
                    // 重置
                    zoomReset.addEventListener('click', function() {
                        zoomLevel = 100;
                        applyZoom();
                    });
                }
            }
        });
    </script>
    <script src="./js/md-fallback.js"></script>
    <script src="./js/main.js"></script>
    <script>
        // 复制文本功能
        function copyText(elementId) {
            var text = document.getElementById(elementId).textContent;
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // 显示复制成功提示
            var button = event.target;
            var originalText = button.textContent;
            button.textContent = '已复制';
            button.style.background = '#4a6bfa';
            setTimeout(function() {
                button.textContent = originalText;
                button.style.background = '#333';
            }, 2000);
        }
        
        // 在页面加载时检查和显示脚本标签
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var scriptTags = document.getElementById('script-tags');
                if (scriptTags) {
                    var scripts = document.getElementsByTagName('script');
                    var markdownScripts = [];
                    for (var i = 0; i < scripts.length; i++) {
                        if (scripts[i].src && scripts[i].src.indexOf('markdown-it') !== -1) {
                            markdownScripts.push(scripts[i].src);
                        }
                    }
                    scriptTags.textContent = markdownScripts.length > 0 ? markdownScripts.join(', ') : '未找到markdown-it脚本';
                }
                
                // 默认显示调试面板
                document.getElementById('debug-info').style.display = 'block';
            }, 500);
        });
    </script>
</body>
</html>
