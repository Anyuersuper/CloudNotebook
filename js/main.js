/**
 * 云笔记主要JavaScript功能
 * 整合了编辑器、预览和界面交互功能
 */

// 在DOM加载完成后初始化应用
document.addEventListener('DOMContentLoaded', function() {
    // 检查当前页面类型
    const isNotebookPage = document.querySelector('#editor') !== null || 
                           document.querySelector('#password-section') !== null;
    
    // 显示库的加载情况
    debugLibraries();
    
    if (isNotebookPage) {
        initNotebookPage();
    }
    
    // 初始化Markdown解析器
    initMarkdown();
    
    // 添加预览内容更新后的处理
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.target.id === 'preview') {
                handleImageLayout();
            }
        });
    });
    
    // 监控预览区域的变化
    const preview = document.getElementById('preview');
    if (preview) {
        observer.observe(preview, { childList: true, subtree: true });
        
        // 初始化时也处理一次
        handleImageLayout();
    }

    // 布局修复功能
    applyLayoutFixes();
    
    // 监听窗口大小变化，重新应用布局修复
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            applyLayoutFixes();
        }, 250);
    });
});

/**
 * 应用布局修复
 */
function applyLayoutFixes() {
    // 增加容器宽度
    const container = document.querySelector('.container');
    if (container) {
        container.style.maxWidth = '1500px';
    }
    
    // 改进左侧导航
    const leftSection = document.querySelector('.left-section');
    if (leftSection) {
        leftSection.style.width = '100%';
        leftSection.style.justifyContent = 'space-between';
    }
    
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.style.width = '160px';
        logo.style.flexShrink = '0';
    }
    
    const notebookTitle = document.querySelector('.notebook-title');
    if (notebookTitle) {
        notebookTitle.style.flexGrow = '1';
        notebookTitle.style.overflow = 'hidden';
        notebookTitle.style.textOverflow = 'ellipsis';
        notebookTitle.style.whiteSpace = 'nowrap';
    }
    
    // 调整编辑器布局
    const editorMain = document.querySelector('.editor-main');
    if (editorMain && window.innerWidth > 768) {
        editorMain.style.flexDirection = 'row';
        editorMain.style.height = 'auto';
        
        // 设置编辑区与预览区样式
        const editorInput = document.querySelector('.editor-input');
        const editorPreview = document.querySelector('.editor-preview');
        
        if (editorInput && editorPreview) {
            editorInput.style.height = 'auto';
            editorInput.style.minHeight = '600px';
            editorInput.style.flex = '1';
            
            editorPreview.style.height = 'auto';
            editorPreview.style.minHeight = '600px';
            editorPreview.style.flex = '1';
        }
    } else if (editorMain && window.innerWidth <= 768) {
        // 移动设备上设置垂直布局
        editorMain.style.height = '800px';
        
        const editorInput = document.querySelector('.editor-input');
        const editorPreview = document.querySelector('.editor-preview');
        
        if (editorInput) {
            editorInput.style.height = '400px';
        }
        
        if (editorPreview) {
            editorPreview.style.height = '400px';
        }
    }
}

/**
 * 复制文本功能
 */
function copyText(elementId) {
    const text = document.getElementById(elementId).textContent;
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // 显示复制成功提示
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '已复制';
    button.style.background = '#4a6bfa';
    setTimeout(function() {
        button.textContent = originalText;
        button.style.background = '#333';
    }, 2000);
}

// 调试函数，用于显示库的加载情况
function debugLibraries() {
    // 所有调试输出已移除
}

// 初始化笔记本页面功能
function initNotebookPage() {
    // 已认证状态下初始化编辑器
    if (typeof isAuthenticated !== 'undefined' && isAuthenticated) {
        initEditor();
    } else {
        // 未认证状态下初始化密码表单
        initPasswordForm();
    }
}

// 初始化密码表单
function initPasswordForm() {
    const submitButton = document.getElementById('submit-password');
    const passwordInput = document.getElementById('password');
    const errorElement = document.getElementById('password-error');
    
    if (!submitButton) return;
    
    submitButton.addEventListener('click', function() {
        const password = passwordInput.value.trim();
        
        if (!password) {
            showError('请输入密码');
            return;
        }
        
        if (isNew) {
            // 创建新记事本
            const confirmPasswordInput = document.getElementById('confirm-password');
            const confirmPassword = confirmPasswordInput.value.trim();
            
            if (!confirmPassword) {
                showError('请确认密码');
                return;
            }
            
            if (password !== confirmPassword) {
                showError('两次输入的密码不一致');
                return;
            }
            
            createNote(password, confirmPassword);
        } else {
            // 验证现有记事本密码
            verifyPassword(password);
        }
    });
    
    // 回车提交表单
    passwordInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitButton.click();
        }
    });
    
    // 显示错误信息
    function showError(message) {
        errorElement.textContent = message;
    }
    
    // 验证密码
    function verifyPassword(password) {
        const formData = new FormData();
        formData.append('action', 'verify_password');
        formData.append('id', noteId);
        formData.append('password', password);
        
        fetch('./system/api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 处理验证成功后的页面刷新
                let redirectUrl = window.location.href;
                
                // 如果URL中没有verified参数，添加它
                if (!redirectUrl.includes('verified=')) {
                    redirectUrl += (redirectUrl.includes('?') ? '&' : '?') + 'verified=1';
                }
                
                // 使用新URL进行重定向
                window.location.href = redirectUrl;
            } else {
                showError(data.message || '密码验证失败');
            }
        })
        .catch(error => {
            showError('发生错误，请重试');
        });
    }
    
    // 创建新记事本
    function createNote(password, confirmPassword) {
        const formData = new FormData();
        formData.append('action', 'create_note');
        formData.append('id', noteId);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);
        
        fetch('./system/api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 刷新页面以显示编辑器
                window.location.reload();
            } else {
                showError(data.message || '创建记事本失败');
            }
        })
        .catch(error => {
            showError('发生错误，请重试');
        });
    }
}

// 初始化编辑器
function initEditor() {
    const editor = document.getElementById('editor');
    const preview = document.getElementById('preview');
    const saveButton = document.getElementById('save-button');
    const saveStatus = document.getElementById('save-status');
    
    if (!editor || !preview) return;
    
    // 初始渲染内容
    updatePreview();
    
    // 监听编辑器内容变化，更新预览
    editor.addEventListener('input', function() {
        updatePreview();
    });
    
    // 保存按钮点击事件
    saveButton.addEventListener('click', function() {
        saveNote();
    });
    
    // Ctrl+S 保存快捷键
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveNote();
        }
    });
    
    // 更新预览区域
    function updatePreview() {
        try {
            // 获取编辑器内容
            const editor = document.getElementById('editor');
            const preview = document.getElementById('preview');
            
            if (!editor || !preview) {
                // 找不到编辑器或预览区域，跳过
                return;
            }
            
            const content = editor.value;
            
            // 如果内容为空，显示提示信息
            if (!content.trim()) {
                preview.innerHTML = '<div class="empty-preview">开始编辑左侧区域，预览将显示在这里...</div>';
                return;
            }
            
            try {
                // 开始渲染Markdown内容
                const html = window.md.render(content);
                
                // Markdown渲染完成，更新DOM
                preview.innerHTML = html;
                
                // 为代码块添加复制按钮
                addCopyButtonsToCodeBlocks();
                
                // 处理图片布局
                handleImageLayout();
                
                // 特殊布局处理
                if (content.includes('# ') || content.includes('## ')) {
                    handleGitHubReadmeLayout();
                }
                
                // 预览更新完成
            } catch (err) {
                // Markdown渲染过程中出错
                preview.innerHTML = '<div class="error-preview">Markdown渲染错误: ' + err.message + '</div>';
            }
        } catch (err) {
            // 预览更新过程中出错
        }
    }
    
    // 保存笔记内容
    function saveNote() {
        const content = editor.value;
        
        if (!content.trim()) {
            showSaveStatus('内容不能为空', false);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'save_note');
        formData.append('id', noteId);
        formData.append('content', content);
        
        showSaveStatus('正在保存...', true);
        
        fetch('./system/api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSaveStatus('保存成功', true);
                
                // 3秒后清除状态
                setTimeout(function() {
                    saveStatus.textContent = '';
                }, 3000);
            } else {
                showSaveStatus(data.message || '保存失败', false);
            }
        })
        .catch(error => {
            showSaveStatus('发生错误，请重试', false);
        });
    }
    
    // 显示保存状态
    function showSaveStatus(message, isSuccess) {
        const saveStatus = document.getElementById('save-status');
        if (!saveStatus) return;
        
        saveStatus.textContent = message;
        
        // 根据状态设置不同的样式
        if (isSuccess) {
            saveStatus.style.color = '#27ae60';
            saveStatus.style.backgroundColor = 'rgba(39, 174, 96, 0.1)';
        } else {
            saveStatus.style.color = '#e74c3c';
            saveStatus.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
        }
        
        // 显示后自动淡出
        setTimeout(function() {
            saveStatus.style.opacity = '0.7';
        }, 2000);
        
        // 3秒后还原
        setTimeout(function() {
            saveStatus.style.opacity = '1';
            if (isSuccess) {
                saveStatus.textContent = '';
                saveStatus.style.backgroundColor = '';
            }
        }, 3000);
    }
}

// 初始化Markdown解析器
function initMarkdown() {
    // 检查是否已经初始化
    if (window.md) {
        return window.md;
    }

    // 在页面中查找markdown-it脚本标签
    let markdownItScriptExists = false;
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.includes('markdown-it')) {
            markdownItScriptExists = true;
            break;
        }
    }

    // 尝试使用已加载的markdown-it
    if (window.md) {
        // 使用全局预先创建的markdown-it实例
        return window.md;
    }

    // 尝试创建新实例
    if (typeof markdownit !== 'undefined') {
        // markdownit函数可用，创建实例
        try {
            window.md = markdownit({
                html: true,
                linkify: true,
                typographer: true,
                highlight: function (str, lang) {
                    if (lang && window.hljs && window.hljs.getLanguage(lang)) {
                        try {
                            return '<pre class="hljs"><code>' +
                                window.hljs.highlight(str, { language: lang, ignoreIllegals: true }).value +
                                '</code></pre>';
                        } catch (__) {}
                    }
                    return '<pre class="hljs"><code>' + window.md.utils.escapeHtml(str) + '</code></pre>';
                }
            });

            // 配置markdown-it渲染器
            if (window.md) {
                // 添加自定义渲染规则
                // 这里可以添加更多自定义配置
                
                return window.md;
            }
        } catch (e) {
            // 创建实例失败，继续尝试加载
        }
    }

    // 如果以上尝试都失败，尝试动态加载markdown-it
    loadMarkdownIt();
    return null;
}

// 尝试动态加载markdown-it
function loadMarkdownIt() {
    // 尝试动态加载markdown-it.min.js
    
    const script = document.createElement('script');
    script.src = './js/markdown-it.min.js';
    script.onload = function() {
        // markdown-it.min.js加载成功，重新初始化
        initMarkdown();
        
        // 如果有编辑器，更新预览
        const editor = document.getElementById('editor');
        if (editor) {
            const event = new Event('input');
            editor.dispatchEvent(event);
        }
    };
    
    script.onerror = function() {
        // 从CDN加载
        const cdnScript = document.createElement('script');
        cdnScript.src = 'https://cdn.jsdelivr.net/npm/markdown-it@12/dist/markdown-it.min.js';
        cdnScript.onload = function() {
            // 从CDN加载markdown-it成功，重新初始化
            initMarkdown();
            
            // 如果有编辑器，更新预览
            const editor = document.getElementById('editor');
            if (editor) {
                const event = new Event('input');
                editor.dispatchEvent(event);
            }
        };
        
        cdnScript.onerror = function() {
            // 尝试从多个路径加载markdown-it
            const paths = [
                './js/lib/markdown-it.min.js',
                '../js/markdown-it.min.js',
                '../js/lib/markdown-it.min.js',
                '/js/markdown-it.min.js',
                '/js/lib/markdown-it.min.js'
            ];
            tryNextPath(0);
        };
        
        document.head.appendChild(cdnScript);
    };
    
    document.head.appendChild(script);
    
    function tryNextPath(index) {
        if (index >= paths.length) {
            return; // 所有路径都尝试过了
        }
        
        const currentScript = document.createElement('script');
        currentScript.src = paths[index];
        
        currentScript.onload = function() {
            // 成功加载，初始化
            initMarkdown();
            
            // 如果有编辑器，更新预览
            const editor = document.getElementById('editor');
            if (editor) {
                const event = new Event('input');
                editor.dispatchEvent(event);
            }
        };
        
        currentScript.onerror = function() {
            // 路径加载失败，尝试下一个
            tryNextPath(index + 1);
        };
        
        document.head.appendChild(currentScript);
    }
}

// 处理图片布局的函数
function handleImageLayout() {
    // 处理图片布局
    const preview = document.getElementById('preview');
    if (!preview) return;
    
    // 安全的选择器函数，防止querySelector抛出错误
    function safeQuerySelector(element, selector) {
        try {
            return element.querySelector(selector);
        } catch (e) {
            return null;
        }
    }
    
    // 为图片添加可点击放大功能和居中显示
    const images = preview.querySelectorAll('img');
    images.forEach(function(img) {
        // 确保图片居中
        img.style.display = 'block';
        img.style.margin = '20px auto';
        img.style.maxWidth = '100%';
        
        // 已经处理过的图片跳过
        if (img.dataset.processed) return;
        
        // 为图片添加点击放大功能
        img.addEventListener('click', function() {
            // 创建一个遮罩层
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            overlay.style.display = 'flex';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';
            overlay.style.zIndex = '9999';
            overlay.style.cursor = 'zoom-out';
            
            // 创建放大的图片
            const enlargedImg = document.createElement('img');
            enlargedImg.src = img.src;
            enlargedImg.style.maxWidth = '90%';
            enlargedImg.style.maxHeight = '90%';
            enlargedImg.style.objectFit = 'contain';
            enlargedImg.style.transition = 'transform 0.3s ease';
            
            // 添加到遮罩层
            overlay.appendChild(enlargedImg);
            document.body.appendChild(overlay);
            
            // 点击关闭
            overlay.addEventListener('click', function() {
                document.body.removeChild(overlay);
            });
        });
        
        // 标记为已处理
        img.dataset.processed = 'true';
    });
    
    // 处理GitHub风格的README布局
    handleGitHubReadmeLayout();
    
    // 图片布局处理完成
}

// 处理GitHub风格的README布局
function handleGitHubReadmeLayout() {
    // 处理GitHub风格的README布局
    const preview = document.getElementById('preview');
    if (!preview) return;
    
    // ... existing code ...
    
    // GitHub风格README布局处理完成
}

// 自动保存内容函数
function saveContent() {
    // 自动保存功能，仅在当前有编辑器内容且处于编辑状态时才保存
    const editor = document.getElementById('editor');
    const saveStatus = document.getElementById('save-status');
    
    if (!editor || !saveStatus) return;
    
    // 更新保存状态提示
    saveStatus.textContent = '编辑中...';
    saveStatus.style.color = '#f39c12';
    
    // 清除之前的定时器
    if (window.autoSaveTimeout) {
        clearTimeout(window.autoSaveTimeout);
    }
    
    // 设置新的定时器，3秒后自动保存
    window.autoSaveTimeout = setTimeout(function() {
        const content = editor.value;
        
        // 空内容不保存
        if (!content.trim()) {
            saveStatus.textContent = '';
            return;
        }
        
        // 存储到localStorage作为备份
        try {
            localStorage.setItem('notebook_backup_' + noteId, content);
            saveStatus.textContent = '内容已备份';
            saveStatus.style.color = '#27ae60';
            
            // 3秒后清除状态
            setTimeout(function() {
                saveStatus.textContent = '';
            }, 3000);
        } catch (e) {
            console.error('备份内容到localStorage失败:', e);
        }
    }, 3000);
}

function reloadMarkdownIt() {
    // 重新加载Markdown解析器
    
    // 移除现有的markdown-it脚本
    const scripts = document.getElementsByTagName('script');
    const markdownItScripts = [];
    
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src && (scripts[i].src.includes('markdown-it') || scripts[i].src.includes('highlight'))) {
            markdownItScripts.push(scripts[i]);
        }
    }
    
    // 使用基础URL
    const baseUrl = new URL('.', window.location.href).href;
    
    // 移除旧脚本
    markdownItScripts.forEach(function(script) {
        script.parentNode.removeChild(script);
    });
    
    // 清除全局实例
    window.md = null;
    
    // 创建新脚本元素
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/markdown-it@12/dist/markdown-it.min.js';
    script.onload = function() {
        // 加载高亮插件
        const highlightScript = document.createElement('script');
        highlightScript.src = 'https://cdn.jsdelivr.net/npm/highlight.js@11/lib/highlight.min.js';
        highlightScript.onload = function() {
            // 从CDN加载成功
            initMarkdown();
            
            // 如果有编辑器，更新预览
            const editor = document.getElementById('editor');
            if (editor) {
                const event = new Event('input');
                editor.dispatchEvent(event);
            }
        };
        document.head.appendChild(highlightScript);
    };
    
    script.onerror = function() {
        // 尝试多个路径
        const scriptPaths = [
            baseUrl + 'js/markdown-it.min.js',
            baseUrl + 'system/js/markdown-it.min.js',
            '/js/markdown-it.min.js',
            '/system/js/markdown-it.min.js'
        ];
        
        function tryNextPath(index) {
            if (index >= scriptPaths.length) {
                return; // 所有路径都尝试过了
            }
            
            const currentScript = document.createElement('script');
            currentScript.src = scriptPaths[index];
            
            currentScript.onload = function() {
                // 成功加载，路径
                initMarkdown();
                
                // 如果有编辑器，更新预览
                const editor = document.getElementById('editor');
                if (editor) {
                    const event = new Event('input');
                    editor.dispatchEvent(event);
                }
            };
            
            currentScript.onerror = function() {
                tryNextPath(index + 1);
            };
            
            document.head.appendChild(currentScript);
        }
        
        tryNextPath(0);
    };
    
    document.head.appendChild(script);
}

// 更新脚本标签信息
function updateScriptTags() {
    var scriptTagsInfo = document.getElementById('script-tags');
    if (scriptTagsInfo) {
        var scripts = document.getElementsByTagName('script');
        var markdownScripts = [];
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src && scripts[i].src.indexOf('markdown-it') !== -1) {
                markdownScripts.push(scripts[i].src);
            }
        }
        scriptTagsInfo.textContent = markdownScripts.length > 0 ? markdownScripts.join(', ') : '未找到markdown-it脚本';
    }
} 