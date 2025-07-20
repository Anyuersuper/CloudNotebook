<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云笔记 - <?php echo htmlspecialchars($id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- 整合后的CSS样式表 -->
    <link rel="stylesheet" href="css/app.css">
    
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

        /* 工具栏样式 */
        .editor-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: var(--darker);
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }

        .toolbar-left, .toolbar-right {
            display: flex;
            gap: 10px;
        }

        .toolbar-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border: none;
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .toolbar-button:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .toolbar-button.active {
            background: var(--primary);
            color: white;
        }

        .toolbar-button i {
            font-size: 1em;
        }

        #save-button {
            background: var(--primary);
            color: white;
        }

        #save-button:hover {
            background: var(--primary-dark);
        }

        #public-toggle {
            position: relative;
            overflow: hidden;
        }

        #public-toggle.active {
            background: var(--success);
        }

        #logout-button {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        #logout-button:hover {
            background: rgba(231, 76, 60, 0.2);
        }

        /* 现代化按钮样式 */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(74, 107, 250, 0.9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(74, 107, 250, 0.3);
            margin-right: 15px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(74, 107, 250, 0.5);
        }

        .btn i {
            font-size: 1em;
        }

        #save-button {
            background: rgba(74, 107, 250, 0.9);
        }

        #public-toggle {
            background: rgba(55, 65, 81, 0.9);
            box-shadow: 0 0 20px rgba(55, 65, 81, 0.3);
        }

        #public-toggle.active {
            background: rgba(16, 185, 129, 0.9);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }

        #public-toggle:hover {
            box-shadow: 0 0 25px rgba(55, 65, 81, 0.5);
        }

        #public-toggle.active:hover {
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.5);
        }

        .editor-actions {
            display: flex;
            align-items: center;
            padding: 15px 0;
        }

        .editor-status {
            margin-left: 15px;
        }

        /* 设置按钮样式 */
        #settings-button {
            background: rgba(55, 65, 81, 0.9);
            box-shadow: 0 0 20px rgba(55, 65, 81, 0.3);
        }

        #settings-button:hover {
            box-shadow: 0 0 25px rgba(55, 65, 81, 0.5);
        }

        /* 复制公开链接按钮样式 */
        #copy-public-link {
            width: 100%;
            margin-bottom: 10px;
            background: rgba(55, 65, 81, 0.9);
        }

        #copy-public-link:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #copy-public-link:not(:disabled):hover {
            background: rgba(74, 107, 250, 0.9);
        }

        /* 归档码输入框样式 */
        .settings-label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
            font-weight: 500;
        }

        .settings-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            font-size: 0.95em;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }

        .settings-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
        }

        /* 退出按钮样式 */
        #logout-button {
            background: rgba(55, 65, 81, 0.9);
            box-shadow: 0 0 20px rgba(55, 65, 81, 0.3);
        }

        #logout-button:hover {
            background: rgba(239, 68, 68, 0.9);
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.5);
        }

        /* 设置下拉菜单样式 */
        .settings-dropdown {
            position: relative;
            display: inline-block;
        }

        .settings-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(28, 32, 51, 0.95);
            min-width: 200px;
            border-radius: 12px;
            padding: 15px;
            z-index: 1000;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
        }

        .settings-content::before {
            content: '';
            position: absolute;
            top: -8px;
            left: var(--triangle-left, 20px);
            right: var(--triangle-right, auto);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid rgba(28, 32, 51, 0.95);
        }

        .settings-content.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .settings-item {
            color: var(--light);
            font-size: 0.9em;
        }

        .settings-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .settings-description {
            color: var(--gray);
            font-size: 0.85em;
            margin: 5px 0 15px 0;
        }

        .settings-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 2px solid var(--gray);
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            position: relative;
            background: transparent;
        }

        .settings-item input[type="checkbox"]:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .settings-item input[type="checkbox"]:checked::before {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .mini-btn {
            padding: 6px 12px;
            font-size: 0.85em;
            width: 100%;
            margin-top: 10px;
        }

        .save-success {
            color: var(--success);
            font-size: 0.85em;
            margin-top: 10px;
            text-align: center;
        }

        .settings-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 15px 0;
        }

        .settings-item {
            margin-bottom: 10px;  /* 减小底部间距 */
            padding: 5px 0;      /* 减小内边距 */
        }

        .settings-item:last-child {
            margin-bottom: 10px;  /* 减小最后一项的底部间距 */
        }

        .settings-description {
            margin: 3px 0 8px 0;  /* 减小描述文字的上下间距 */
            line-height: 1.3;     /* 减小描述文字的行高 */
        }

        .settings-divider {
            margin: 8px 0;       /* 减小分隔线的上下间距 */
        }

        /* 开关样式 */
        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
            margin-right: 10px;
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
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
            box-shadow: 0 0 15px rgba(74, 107, 250, 0.5);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .settings-item label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .settings-item .label-text {
            font-weight: 500;
            color: var(--light);
        }
    </style>
    
    <!-- Markdown和高亮库 -->
    <script src="./js/highlight.min.js"></script>
    <script src="./js/markdown-it.min.js"></script>
    
    <!-- 整合后的Markdown处理模块 -->
    <script src="./js/markdown-bundle.js"></script>
</head>
<body>
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
                    <div class="editor-actions">
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
                                        <span class="switch">
                                            <input type="checkbox" id="always-require-password" <?php echo $db->getAlwaysRequirePassword($id) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </span>
                                        <span class="label-text">总是要求密码</span>
                                    </label>
                                    <div class="settings-description">启用此选项后，每次访问笔记本都需要输入密码</div>
                                </div>
                                <div class="settings-divider"></div>
                                <div class="settings-item">
                                    <label>
                                        <span class="switch">
                                            <input type="checkbox" id="public-toggle" <?php echo $db->isPublic($id) ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </span>
                                        <span class="label-text">公开笔记本</span>
                                    </label>
                                    <div class="settings-description">启用后，其他人可以通过链接查看笔记本内容（无需密码）</div>
                                </div>
                                <div class="settings-divider"></div>
                                <div class="settings-item">
                                    <button id="copy-public-link" class="btn mini-btn" <?php echo !$db->isPublic($id) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-link"></i> 复制公开链接
                                    </button>
                                    <div class="settings-description">复制可分享的公开访问链接</div>
                                </div>
                                <div class="settings-divider"></div>
                                <div class="settings-item">
                                    <label for="archive-code" class="settings-label">归档码</label>
                                    <input type="text" id="archive-code" class="settings-input" placeholder="设置归档码以便于管理" value="<?php echo htmlspecialchars((string)$db->getArchiveCode($id)); ?>">
                                    <div class="settings-description">设置归档码后，可以通过它查找属于同一类别的笔记本</div>
                                </div>
                                <button id="save-settings" class="btn mini-btn">保存设置</button>
                            </div>
                        </div>
                    </div>
                    <div class="editor-status">
                        <div id="save-status" class="save-status"></div>
                    </div>
                </div>
                <div class="editor-main">
                    <table style="width:100%; height:100%; border-collapse:collapse; table-layout:fixed; background:#1c2033; border-radius:10px;">
                        <tr>
                            <td style="width:50%; padding:0; vertical-align:top; border-right:1px solid rgba(255, 255, 255, 0.1);">
                                <div style="height:100%; position:relative;">
                                    <textarea id="editor" spellcheck="false" style="width:100%; height:100%; padding:20px; border:none; resize:none; background:transparent; color:#f0f2f5; font-family:'Consolas','Monaco','Menlo',monospace; font-size:16px; line-height:1.7; outline:none; display:block; overflow:auto; scrollbar-width:none;"><?php echo htmlspecialchars($content); ?></textarea>
                                </div>
                            </td>
                            <td style="width:50%; padding:0; vertical-align:top; position:relative;">
                                <div id="preview" style="padding:20px; position:absolute; top:0; right:0; bottom:0; left:0; overflow:auto; color:#f0f2f5; font-size:16px; line-height:1.7; scrollbar-width:none;">
                                    <!-- 预览内容将在此显示 -->
                                </div>
                            </td>
                        </tr>
                    </table>
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
        
        // 页面加载设置
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
            
            // 如果已经登录认证且不是新记事本，立即渲染预览内容
            if (isAuthenticated && !isNew) {
                const editor = document.getElementById('editor');
                const preview = document.getElementById('preview');
                
                if (editor && preview) {
                    // 使用Promise等待Markdown解析器初始化完成
                    if (window.mdInitialized) {
                        window.mdInitialized.then(() => {
                            // 直接渲染初始内容
                            const content = editor.value;
                            preview.innerHTML = window.md.render(content);
                            
                            // 应用代码高亮
                            if (window.hljs) {
                                document.querySelectorAll('#preview pre code').forEach((block) => {
                                    hljs.highlightElement(block);
                                });
                            }
                        });
                    } else {
                        // 兼容没有promise的情况下的直接渲染
                        if (window.md) {
                            const content = editor.value;
                            preview.innerHTML = window.md.render(content);
                            
                            if (window.hljs) {
                                document.querySelectorAll('#preview pre code').forEach((block) => {
                                    hljs.highlightElement(block);
                                });
                            }
                        }
                    }
                }
            }
            
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
            
            // 设置菜单功能
            const settingsButton = document.getElementById('settings-button');
            const settingsMenu = document.getElementById('settings-menu');
            const saveSettingsButton = document.getElementById('save-settings');
            const alwaysRequirePasswordCheckbox = document.getElementById('always-require-password');
            const publicToggleCheckbox = document.getElementById('public-toggle');
            
            if (settingsButton && settingsMenu) {
                // 点击设置按钮显示/隐藏设置菜单
                settingsButton.addEventListener('click', function(e) {
                    // 阻止事件冒泡，防止点击设置后立即关闭
                    e.stopPropagation();
                    
                    // 计算设置菜单的位置，避免溢出
                    const buttonRect = settingsButton.getBoundingClientRect();
                    const windowWidth = window.innerWidth;
                    const menuWidth = 180; // 菜单宽度
                    
                    // 如果按钮右侧空间不足，则向左展开菜单
                    if (buttonRect.right + menuWidth > windowWidth) {
                        settingsMenu.style.right = '0';
                        settingsMenu.style.left = 'auto';
                        
                        // 调整三角形位置
                        document.documentElement.style.setProperty('--triangle-left', 'auto');
                        document.documentElement.style.setProperty('--triangle-right', '20px');
                    } else {
                        settingsMenu.style.left = '0';
                        settingsMenu.style.right = 'auto';
                        
                        // 重置三角形位置
                        document.documentElement.style.setProperty('--triangle-left', '20px');
                        document.documentElement.style.setProperty('--triangle-right', 'auto');
                    }
                    
                    settingsMenu.classList.toggle('show');
                });
                
                // 点击页面其他地方关闭设置菜单
                document.addEventListener('click', function(event) {
                    if (!event.target.matches('.settings-btn') && !settingsMenu.contains(event.target)) {
                        settingsMenu.classList.remove('show');
                    }
                });
                
                // 修改设置保存处理
                // 处理公开链接复制按钮
                const copyPublicLinkButton = document.getElementById('copy-public-link');
                if (copyPublicLinkButton) {
                    // 当公开状态改变时更新按钮状态
                    publicToggleCheckbox.addEventListener('change', function() {
                        copyPublicLinkButton.disabled = !this.checked;
                    });

                    copyPublicLinkButton.addEventListener('click', function() {
                        const publicUrl = window.location.origin + '/public.php?id=' + noteId;
                        navigator.clipboard.writeText(publicUrl).then(() => {
                            const originalText = this.innerHTML;
                            this.innerHTML = '<i class="fas fa-check"></i> 已复制';
                            setTimeout(() => {
                                this.innerHTML = originalText;
                            }, 2000);
                        }).catch(err => {
                            console.error('Failed to copy:', err);
                            alert('复制链接失败，请手动复制');
                        });
                    });
                }

                if (saveSettingsButton) {
                    saveSettingsButton.addEventListener('click', function() {
                        const alwaysRequirePassword = alwaysRequirePasswordCheckbox.checked;
                        const isPublic = publicToggleCheckbox.checked;
                        const archiveCode = document.getElementById('archive-code').value.trim();
                        
                        // 保存所有设置
                        Promise.all([
                            // 保存密码设置
                            fetch('./system/api.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=update_settings&id=' + noteId + '&always_require_password=' + (alwaysRequirePassword ? '1' : '0')
                            }),
                            // 保存公开设置
                            fetch('./system/api.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=update_public&id=' + noteId + '&ispublic=' + (isPublic ? '1' : '0')
                            }),
                            // 先检查归档码是否存在
                            fetch('./system/api.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=check_archive_code&id=' + noteId + '&archive_code=' + encodeURIComponent(archiveCode)
                            }).then(response => response.json())
                            .then(data => {
                                if (data.exists) {
                                    return new Promise((resolve, reject) => {
                                        if (confirm('该归档码已存在，确定要归档到该处吗？')) {
                                            // 用户确认后再设置归档码
                                            fetch('./system/api.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/x-www-form-urlencoded',
                                                },
                                                body: 'action=set_archive_code&id=' + noteId + '&archive_code=' + encodeURIComponent(archiveCode)
                                            }).then(resolve).catch(reject);
                                        } else {
                                            reject(new Error('用户取消设置归档码'));
                                        }
                                    });
                                } else {
                                    // 归档码不存在，直接设置
                                    return fetch('./system/api.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                        },
                                        body: 'action=set_archive_code&id=' + noteId + '&archive_code=' + encodeURIComponent(archiveCode)
                                    });
                                }
                            })
                        ])
                        .then(responses => Promise.all(responses.map(r => r.json())))
                        .then(results => {
                            if (results.every(r => r.success)) {
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
                                alert('保存设置失败: ' + (results.find(r => !r.success)?.message || '未知错误'));
                            }
                        })
                        .catch(error => {
                            alert('保存设置时发生错误');
                            console.error(error);
                        });
                    });
                }
            }
            
            // 密码验证表单处理
            if (!isAuthenticated) {
                const passwordInput = document.getElementById('password');
                const confirmPasswordInput = document.getElementById('confirm-password');
                const submitButton = document.getElementById('submit-password');
                const errorDisplay = document.getElementById('password-error');
                
                if (submitButton && passwordInput) {
                    submitButton.addEventListener('click', function() {
                        const password = passwordInput.value;
                        
                        if (!password) {
                            errorDisplay.textContent = '请输入密码';
                            return;
                        }
                        
                        if (isNew && confirmPasswordInput) {
                            const confirmPassword = confirmPasswordInput.value;
                            if (password !== confirmPassword) {
                                errorDisplay.textContent = '两次输入的密码不一致';
                                return;
                            }
                        }
                        
                        // 准备表单数据
                        const formData = new FormData();
                        formData.append('id', noteId);
                        formData.append('password', password);
                        
                        if (isNew) {
                            formData.append('action', 'create_note');
                            formData.append('confirm_password', confirmPasswordInput.value);
                        } else {
                            formData.append('action', 'verify_password');
                        }
                        
                        // 显示加载状态
                        errorDisplay.textContent = '处理中...';
                        submitButton.disabled = true;
                        
                        // 提交到API
                        fetch('./system/api.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // 成功处理
                                let redirectUrl = `notebook.php?id=${encodeURIComponent(noteId)}`;
                                
                                // 如果设置了总是需要密码，且验证成功，添加verified参数
                                if (!isNew && data.always_require_password) {
                                    redirectUrl += '&verified=1';
                                }
                                
                                window.location.href = redirectUrl;
                            } else {
                                // 显示错误
                                errorDisplay.textContent = data.message || '验证失败';
                                submitButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            errorDisplay.textContent = '请求处理过程中发生错误';
                            submitButton.disabled = false;
                            console.error('API请求错误:', error);
                        });
                    });
                    
                    // 回车键提交
                    passwordInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            submitButton.click();
                        }
                    });
                    
                    if (confirmPasswordInput) {
                        confirmPasswordInput.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                submitButton.click();
                            }
                        });
                    }
                }
            }
            
            // 编辑器处理
            if (isAuthenticated) {
                const editor = document.getElementById('editor');
                const preview = document.getElementById('preview');
                const saveButton = document.getElementById('save-button');
                const saveStatus = document.getElementById('save-status');
                
                let lastSavedContent = editor.value;
                let debounceTimeout;
                
                // 添加Tab缩进功能
                editor.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        
                        // 获取当前光标位置和文本内容
                        const start = editor.selectionStart;
                        const end = editor.selectionEnd;
                        const value = editor.value;
                        
                        // 计算缩进
                        const indent = '    '; // 使用4个空格作为缩进
                        
                        // 如果选中了文本，对选中部分进行缩进
                        if (start !== end) {
                            const selectedText = value.substring(start, end);
                            const lines = selectedText.split('\n');
                            const newLines = lines.map(line => indent + line);
                            const newText = newLines.join('\n');
                            
                            // 替换选中的文本
                            editor.value = value.substring(0, start) + newText + value.substring(end);
                            
                            // 更新选择范围
                            editor.selectionStart = start;
                            editor.selectionEnd = start + newText.length;
                        } else {
                            // 在光标位置插入缩进
                            editor.value = value.substring(0, start) + indent + value.substring(end);
                            
                            // 更新光标位置
                            editor.selectionStart = start + indent.length;
                            editor.selectionEnd = start + indent.length;
                        }
                        
                        // 触发input事件以更新预览
                        editor.dispatchEvent(new Event('input'));
                    }
                });
                
                // 更新预览
                function updatePreview() {
                    if (editor && preview && window.md) {
                        const content = editor.value;
                        preview.innerHTML = window.md.render(content);
                        
                        // 应用代码高亮
                        if (window.hljs) {
                            document.querySelectorAll('#preview pre code').forEach((block) => {
                                hljs.highlightElement(block);
                            });
                        }
                        
                        // 如果编辑器内容发生变化，更新状态
                        if (content !== lastSavedContent) {
                            saveStatus.textContent = '未保存的更改';
                            saveStatus.style.color = '#f39c12';
                            saveStatus.style.backgroundColor = 'rgba(243, 156, 18, 0.1)';
                        } else {
                            saveStatus.textContent = '已保存';
                            saveStatus.style.color = '#27ae60';
                            saveStatus.style.backgroundColor = 'rgba(39, 174, 96, 0.1)';
                        }
                    }
                }
                
                // 初始化时立即更新预览
                updatePreview();
                
                // 输入时更新预览（带防抖）
                editor.addEventListener('input', function() {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(updatePreview, 300);
                });
                
                // 自动保存
                editor.addEventListener('input', function() {
                    clearTimeout(window.autoSaveTimeout);
                    window.autoSaveTimeout = setTimeout(saveContent, 5000);
                });
                
                // 使用Ctrl+S保存
                editor.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.key === 's') {
                        e.preventDefault();
                        saveContent();
                    }
                });
                
                // 保存内容到服务器
                function saveContent() {
                    if (!editor) return;
                    
                    const content = editor.value;
                    if (content === lastSavedContent) {
                        saveStatus.textContent = '没有需要保存的更改';
                        saveStatus.style.color = '#6e7888';
                        saveStatus.style.backgroundColor = 'rgba(110, 120, 136, 0.1)';
                        return;
                    }
                    
                    saveStatus.textContent = '正在保存...';
                    saveStatus.style.color = '#3498db';
                    saveStatus.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
                    
                    const formData = new FormData();
                    formData.append('action', 'save_note');
                    formData.append('id', noteId);
                    formData.append('content', content);
                    
                    fetch('./system/api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            lastSavedContent = content;
                            saveStatus.textContent = '保存成功 ' + new Date().toLocaleTimeString();
                            saveStatus.style.color = '#2ecc71';
                            saveStatus.style.backgroundColor = 'rgba(46, 204, 113, 0.1)';
                        } else {
                            saveStatus.textContent = '保存失败: ' + (data.message || '未知错误');
                            saveStatus.style.color = '#e74c3c';
                            saveStatus.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
                        }
                    })
                    .catch(error => {
                        saveStatus.textContent = '保存时发生错误';
                        saveStatus.style.color = '#e74c3c';
                        saveStatus.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
                        console.error('保存错误:', error);
                    });
                }
                
                // 保存按钮
                if (saveButton) {
                    saveButton.addEventListener('click', saveContent);
                }
            }
        });
    </script>
</body>
</html>