<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>云笔记 - <?php echo htmlspecialchars($id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- 整合后的CSS样式表 -->
    <link rel="stylesheet" href="css/app.css">
    
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
                                let redirectUrl = `notebook.php?id=${encodeURIComponent(noteId)}&t=${Date.now()}`;
                                
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
                            saveStatus.style.color = 'var(--primary)';
                        } else {
                            saveStatus.textContent = '已保存';
                            saveStatus.style.color = 'var(--success)';
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
                        return;
                    }
                    
                    saveStatus.textContent = '正在保存...';
                    
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
                        } else {
                            saveStatus.textContent = '保存失败: ' + (data.message || '未知错误');
                            saveStatus.style.color = '#e74c3c';
                        }
                    })
                    .catch(error => {
                        saveStatus.textContent = '保存时发生错误';
                        saveStatus.style.color = '#e74c3c';
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