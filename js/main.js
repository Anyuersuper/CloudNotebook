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
});

// 调试函数，用于显示库的加载情况
function debugLibraries() {
    console.group('库加载状态');
    console.log('window.markdownit:', typeof window.markdownit !== 'undefined' ? '已加载' : '未加载');
    console.log('window.md 实例:', window.md ? '已创建' : '未创建');
    console.log('window.hljs:', typeof window.hljs !== 'undefined' ? '已加载' : '未加载');
    
    if (window.md) {
        console.log('测试MD渲染:', window.md.render('# 测试') ? '可用' : '不可用');
    }
    
    if (window.hljs) {
        console.log('测试highlight.js:', window.hljs.highlight ? '可用' : '不可用');
    }
    
    try {
        // 尝试在控制台中预览标记语法
        const testMarkdown = '# 测试标题\n- 列表项1\n- 列表项2\n```js\nconsole.log("测试");\n```';
        if (window.md) {
            console.log('标记语法预览:', window.md.render(testMarkdown).substring(0, 100) + '...');
        }
    } catch (e) {
        console.error('测试渲染失败:', e);
    }
    
    console.groupEnd();
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
            // 获取DOM元素
            const editor = document.getElementById('editor');
            const preview = document.getElementById('preview');
            
            // 检查元素是否存在
            if (!editor || !preview) {
                console.error('找不到编辑器或预览区域元素');
                return;
            }
            
            // 检查markdown-it实例
            if (!window.md) {
                if (!initMarkdown()) {
                    console.error('无法更新预览，Markdown解析器未初始化');
                    preview.innerHTML = '<div class="markdown-error">Markdown解析器未初始化</div>';
                    return;
                }
            }
            
            // 获取内容
            const content = editor.value;
            
            // 渲染内容
            try {
                console.log('开始渲染Markdown内容...');
                const result = window.md.render(content);
                console.log('Markdown渲染完成，更新DOM');
                
                // 直接更新预览区域
                preview.innerHTML = result;
                
                // 应用图片布局处理
                handleImageLayout();
                
                // 应用缩放级别
                if (typeof applyZoom === 'function') {
                    applyZoom();
                }
                
                // 保存内容
                saveContent();
                
                console.log('预览更新完成');
            } catch (renderError) {
                console.error('Markdown渲染错误:', renderError);
                preview.innerHTML = '<div class="markdown-error">Markdown渲染失败: ' + renderError.message + '</div>';
            }
        } catch (error) {
            console.error('预览更新过程中出错:', error);
            const preview = document.getElementById('preview');
            if (preview) {
                preview.innerHTML = '<div class="markdown-error">更新预览失败: ' + error.message + '</div>';
            }
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
        saveStatus.textContent = message;
        saveStatus.style.color = isSuccess ? '#27ae60' : '#e74c3c';
    }
}

// 初始化Markdown解析器
function initMarkdown() {
    console.log('开始初始化Markdown解析器');
    console.log('检查markdownit是否已定义:', typeof markdownit);
    console.log('window.md 实例:', window.md ? '已创建' : '未创建');
    console.log('当前页面URL:', window.location.href);
    console.log('当前页面路径:', window.location.pathname);
    console.log('相对路径基础:', new URL('.', window.location.href).href);
    
    // 显示调试信息
    if (document.getElementById('debug-info')) {
        document.getElementById('debug-info').style.display = 'block';
        document.getElementById('js-path').textContent = window.location.pathname + ' => ' + new URL('js/', window.location.href).href;
        document.getElementById('current-url').textContent = window.location.href;
        document.getElementById('libs-status').textContent = 
            'markdown-it: ' + (typeof markdownit !== 'undefined' ? '已加载' : '未加载') + 
            ', md实例: ' + (window.md ? '已创建' : '未创建');
    }
    
    // 检查页面中是否存在markdown-it.min.js脚本标签
    var scripts = document.getElementsByTagName('script');
    var markdownItScriptExists = false;
    for (var i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.indexOf('markdown-it.min.js') !== -1) {
            markdownItScriptExists = true;
            console.log('找到markdown-it脚本标签:', scripts[i].src);
        }
    }
    console.log('页面中是否存在markdown-it脚本标签:', markdownItScriptExists);
    
    try {
        // 使用全局创建的markdown-it实例
        if (window.md) {
            console.log('使用全局预先创建的markdown-it实例');
            return true;
        } else {
            // 如果全局实例不存在，尝试创建新实例
            console.log('全局md实例不存在，尝试创建新实例');
            
            if (typeof markdownit !== 'undefined') {
                try {
                    console.log('markdownit函数可用，尝试创建实例');
                    window.md = markdownit({
                        html: true,
                        xhtmlOut: true,
                        breaks: true,
                        linkify: true,
                        typographer: true
                    });
                    console.log('成功创建新的markdown-it实例');
                    
                    // 配置解析器 - 以兼容方式添加自定义渲染功能
                    if (window.md && window.md.renderer) {
                        console.log('配置markdown-it渲染器');
                        
                        // 保存原始的图片渲染方法
                        const defaultRender = window.md.renderer.rules.image || 
                            function(tokens, idx, options, env, self) {
                                return self.renderToken(tokens, idx, options);
                            };
                        
                        // 自定义图片渲染规则，为徽章图片添加特殊类
                        window.md.renderer.rules.image = function(tokens, idx, options, env, self) {
                            const token = tokens[idx];
                            const srcIndex = token.attrIndex('src');
                            if (srcIndex >= 0) {
                                const src = token.attrs[srcIndex][1];
                                // 检查是否为徽章图片
                                if (src.includes('shields.io') || src.includes('badge')) {
                                    // 为徽章图片添加特殊类
                                    const classIndex = token.attrIndex('class');
                                    if (classIndex < 0) {
                                        token.attrPush(['class', 'badge-img']);
                                    } else {
                                        token.attrs[classIndex][1] += ' badge-img';
                                    }
                                }
                            }
                            // 调用原始方法渲染图片
                            return defaultRender(tokens, idx, options, env, self);
                        };
                        
                        // 配置代码高亮
                        window.md.set({
                            highlight: function (str, lang) {
                                console.log('代码高亮处理', lang);
                                if (lang && hljs && hljs.getLanguage(lang)) {
                                    try {
                                        return '<pre class="hljs"><code>' +
                                            hljs.highlight(str, { language: lang, ignoreIllegals: true }).value +
                                            '</code></pre>';
                                    } catch (__) {
                                        console.warn('代码高亮失败', __);
                                    }
                                }
                                return '<pre class="hljs"><code>' + window.md.utils.escapeHtml(str) + '</code></pre>';
                            }
                        });
                    }
                } catch (createError) {
                    console.error('创建markdownit实例时出错:', createError);
                    // 尝试重新加载markdown-it
                    loadMarkdownIt();
                    return false;
                }
            } else {
                console.error('markdownit未定义，库可能未正确加载');
                // 尝试重新加载markdown-it
                loadMarkdownIt();
                return false;
            }
        }
        
        console.log('Markdown解析器初始化成功');
        return true;
    } catch (error) {
        console.error('Markdown解析器初始化失败:', error);
        document.querySelector('.editor-preview').innerHTML = '<div class="markdown-error">加载解析器失败: ' + error.message + '</div>';
        
        // 尝试重新加载markdown-it
        loadMarkdownIt();
        return false;
    }
}

// 尝试动态加载markdown-it
function loadMarkdownIt() {
    console.log('尝试动态加载markdown-it.min.js');
    
    // 显示加载状态
    if (document.getElementById('load-errors')) {
        document.getElementById('load-errors').textContent = '正在尝试加载markdown-it.min.js...';
    }
    
    // 创建新的script元素
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.onload = function() {
        console.log('markdown-it.min.js加载成功，重新初始化...');
        if (document.getElementById('load-errors')) {
            document.getElementById('load-errors').textContent = 'markdown-it.min.js加载成功';
            document.getElementById('load-errors').style.color = 'green';
        }
        initMarkdown();
    };
    script.onerror = function(e) {
        console.error('动态加载markdown-it.min.js失败:', e);
        if (document.getElementById('load-errors')) {
            document.getElementById('load-errors').textContent = '本地加载失败，尝试CDN...';
        }
        
        // 尝试从CDN加载
        var cdnScript = document.createElement('script');
        cdnScript.type = 'text/javascript';
        cdnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/markdown-it/12.3.2/markdown-it.min.js';
        cdnScript.onload = function() {
            console.log('从CDN加载markdown-it成功，重新初始化...');
            if (document.getElementById('load-errors')) {
                document.getElementById('load-errors').textContent = '从CDN加载成功';
                document.getElementById('load-errors').style.color = 'green';
            }
            initMarkdown();
        };
        cdnScript.onerror = function(e) {
            console.error('从CDN加载markdown-it失败:', e);
            if (document.getElementById('load-errors')) {
                document.getElementById('load-errors').textContent = '所有加载尝试均失败';
                document.getElementById('load-errors').style.color = 'red';
            }
            
            document.querySelector('.editor-preview').innerHTML = 
                '<div class="markdown-error">无法加载Markdown解析器，请检查网络连接或联系管理员。</div>';
        };
        document.head.appendChild(cdnScript);
    };
    
    // 尝试不同的路径
    var baseUrl = new URL('.', window.location.href).href;
    var paths = [
        './js/markdown-it.min.js',
        baseUrl + 'js/markdown-it.min.js', // 基于当前URL的路径
        'js/markdown-it.min.js',
        window.location.origin + '/js/markdown-it.min.js',
        '../js/markdown-it.min.js'
    ];
    
    console.log('将尝试以下路径:', paths);
    
    function tryNextPath(index) {
        if (index >= paths.length) {
            console.error('所有路径都尝试失败');
            if (document.getElementById('load-errors')) {
                document.getElementById('load-errors').textContent = '所有本地路径尝试均失败';
            }
            // 最后一次尝试失败时的处理，触发onerror事件
            script.onerror();
            return;
        }
        
        console.log('尝试从路径加载:', paths[index]);
        if (document.getElementById('load-errors')) {
            document.getElementById('load-errors').textContent = '尝试路径: ' + paths[index];
        }
        
        script.src = paths[index];
        script.onerror = function() {
            console.log('路径加载失败:', paths[index]);
            tryNextPath(index + 1);
        };
        
        // 移除之前的脚本（如果存在）
        var existingScript = document.querySelector('script[src="' + paths[index] + '"]');
        if (existingScript) {
            existingScript.parentNode.removeChild(existingScript);
        }
        
        document.head.appendChild(script);
    }
    
    tryNextPath(0);
}

// 处理图片布局的函数
function handleImageLayout() {
    const preview = document.getElementById('preview');
    if (!preview) {
        console.error('找不到预览区域元素，无法处理图片布局');
        return;
    }
    
    // 安全查询器函数，避免不支持的CSS选择器语法
    function safeQuerySelector(element, selector) {
        try {
            return element.querySelectorAll(selector);
        } catch (e) {
            console.warn('不支持的选择器:', selector, e);
            return [];
        }
    }
    
    try {
        // 查找预览区域内的所有段落和图片
        const paragraphs = preview.querySelectorAll('p');
        
        paragraphs.forEach(p => {
            const images = p.querySelectorAll('img');
            
            // 没有图片的段落跳过
            if (images.length === 0) return;
            
            // 检查是否为徽章图片
            const hasBadges = Array.from(images).some(img => {
                const src = img.getAttribute('src') || '';
                return src.includes('shields.io') || src.includes('badge');
            });
            
            if (hasBadges) {
                // 处理徽章图片，给予它们特殊样式
                p.classList.add('github-badges');
                
                // 为徽章图片添加样式
                images.forEach(img => {
                    img.classList.add('badge-img');
                    // 设置徽章图片的样式
                    img.style.maxHeight = '25px';
                    img.style.margin = '0 4px';
                    img.style.verticalAlign = 'middle';
                });
            } else if (images.length > 1) {
                // 多张图片的处理
                p.classList.add('github-image-grid');
                
                // 创建图片网格容器
                const imageGrid = document.createElement('div');
                imageGrid.className = 'image-grid';
                imageGrid.style.display = 'flex';
                imageGrid.style.flexWrap = 'wrap';
                imageGrid.style.gap = '10px';
                imageGrid.style.alignItems = 'center';
                imageGrid.style.justifyContent = 'center';
                
                // 移动所有图片到网格容器
                images.forEach(img => {
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '400px';
                    img.style.objectFit = 'contain';
                    
                    // 为每个图片创建包装容器
                    const imgWrapper = document.createElement('div');
                    imgWrapper.className = 'img-wrapper';
                    imgWrapper.style.flex = '0 1 auto';
                    imgWrapper.style.display = 'flex';
                    imgWrapper.style.alignItems = 'center';
                    imgWrapper.style.justifyContent = 'center';
                    imgWrapper.style.margin = '5px';
                    imgWrapper.style.minWidth = '150px';
                    
                    // 创建可点击放大的功能
                    imgWrapper.onclick = function() {
                        const overlay = document.createElement('div');
                        overlay.className = 'img-overlay';
                        overlay.style.position = 'fixed';
                        overlay.style.top = '0';
                        overlay.style.left = '0';
                        overlay.style.width = '100%';
                        overlay.style.height = '100%';
                        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
                        overlay.style.zIndex = '1000';
                        overlay.style.display = 'flex';
                        overlay.style.justifyContent = 'center';
                        overlay.style.alignItems = 'center';
                        
                        const imgClone = img.cloneNode(true);
                        imgClone.style.maxWidth = '90%';
                        imgClone.style.maxHeight = '90%';
                        imgClone.style.objectFit = 'contain';
                        
                        overlay.appendChild(imgClone);
                        document.body.appendChild(overlay);
                        
                        overlay.onclick = function() {
                            document.body.removeChild(overlay);
                        };
                    };
                    
                    imgWrapper.appendChild(img.cloneNode(true));
                    imageGrid.appendChild(imgWrapper);
                });
                
                // 替换原本的段落内容
                p.innerHTML = '';
                p.appendChild(imageGrid);
            }
        });
        
        console.log('图片布局处理完成');
        
        // 处理GitHub风格的README布局
        handleGitHubReadmeLayout();
    } catch (error) {
        console.error('处理图片布局时出错:', error);
    }
}

// 处理GitHub风格的README布局
function handleGitHubReadmeLayout() {
    const preview = document.getElementById('preview');
    if (!preview) {
        console.error('找不到预览区域元素，无法处理README布局');
        return;
    }
    
    try {
        // 处理div[align="center"]元素
        const centeredDivs = preview.querySelectorAll('div[align="center"]');
        centeredDivs.forEach(div => {
            div.style.textAlign = 'center';
            div.style.margin = '20px auto';
            div.style.maxWidth = '800px';
            div.style.display = 'block';
            
            // 如果div中包含徽章图片，特别处理
            const badges = div.querySelectorAll('img[src*="shields.io"], img[src*="badge"]');
            if (badges.length > 0) {
                // 检查是否已经有徽章容器
                let badgeContainer = div.querySelector('.github-badges-container');
                
                // 如果没有容器，创建一个
                if (!badgeContainer) {
                    badgeContainer = document.createElement('div');
                    badgeContainer.className = 'github-badges-container';
                    badgeContainer.style.display = 'flex';
                    badgeContainer.style.flexWrap = 'wrap';
                    badgeContainer.style.justifyContent = 'center';
                    badgeContainer.style.gap = '6px';
                    badgeContainer.style.margin = '15px auto';
                    badgeContainer.style.maxWidth = '600px';
                    
                    // 收集所有徽章并移动到容器中
                    badges.forEach(badge => {
                        badge.classList.add('badge-img');
                        badge.style.maxHeight = '25px';
                        badge.style.margin = '0 2px';
                        badgeContainer.appendChild(badge.cloneNode(true));
                    });
                    
                    // 查找所有包含徽章的段落
                    const badgeParagraphs = [];
                    badges.forEach(badge => {
                        const parent = badge.parentNode;
                        if (parent && parent.tagName === 'P') {
                            // 避免重复添加
                            if (!badgeParagraphs.includes(parent)) {
                                badgeParagraphs.push(parent);
                            }
                        }
                    });
                    
                    // 替换第一个段落内容或添加到div
                    if (badgeParagraphs.length > 0) {
                        badgeParagraphs[0].innerHTML = '';
                        badgeParagraphs[0].appendChild(badgeContainer);
                        
                        // 移除其他可能为空的段落
                        for (let i = 1; i < badgeParagraphs.length; i++) {
                            if (badgeParagraphs[i].childNodes.length === 0 || 
                                (badgeParagraphs[i].childNodes.length === 1 && badgeParagraphs[i].textContent.trim() === '')) {
                                if (badgeParagraphs[i].parentNode) {
                                    badgeParagraphs[i].parentNode.removeChild(badgeParagraphs[i]);
                                }
                            }
                        }
                    } else {
                        // 如果没有在段落中找到徽章，直接添加到div
                        div.appendChild(badgeContainer);
                    }
                }
            }
            
            // 确保div内的段落也居中
            const paragraphs = div.querySelectorAll('p');
            paragraphs.forEach(p => {
                p.style.textAlign = 'center';
                p.style.margin = '10px auto';
            });
        });
        
        // 处理README标题部分
        const h1 = preview.querySelector('h1');
        if (h1) {
            h1.style.borderBottom = '1px solid #30363d';
            h1.style.marginBottom = '20px';
            h1.style.paddingBottom = '10px';
        }
        
        // 处理导航链接区块，常见于GitHub README
        const allParagraphs = preview.querySelectorAll('p');
        allParagraphs.forEach(p => {
            // 检查段落中是否有多个锚链接
            const anchors = p.querySelectorAll('a[href^="#"]');
            if (anchors.length >= 2) {
                p.style.textAlign = 'center';
                p.style.margin = '25px auto';
                p.style.padding = '10px 0';
                p.style.borderBottom = '1px solid rgba(48, 54, 61, 0.5)';
                p.style.borderTop = '1px solid rgba(48, 54, 61, 0.5)';
            }
            
            // 处理非居中div内的徽章图片
            const badges = p.querySelectorAll('img.badge-img');
            if (badges.length > 0 && !p.closest('div[align="center"]')) {
                p.classList.add('github-badges');
            }
        });
        
        // 增强代码块的显示
        const codeBlocks = preview.querySelectorAll('pre code');
        codeBlocks.forEach(code => {
            const pre = code.parentNode;
            if (!pre) return;
            
            pre.style.position = 'relative';
            
            // 添加语言标记，如果存在
            const langMatch = code.className.match(/language-([a-z]+)/);
            if (langMatch && langMatch[1]) {
                const langLabel = document.createElement('div');
                langLabel.className = 'code-language';
                langLabel.textContent = langMatch[1];
                langLabel.style.position = 'absolute';
                langLabel.style.top = '0';
                langLabel.style.right = '0';
                langLabel.style.padding = '2px 8px';
                langLabel.style.fontSize = '12px';
                langLabel.style.color = '#7d8590';
                langLabel.style.borderBottomLeftRadius = '6px';
                langLabel.style.backgroundColor = 'rgba(27, 31, 35, 0.5)';
                pre.insertBefore(langLabel, pre.firstChild);
            }
        });
        
        console.log('GitHub风格README布局处理完成');
    } catch (error) {
        console.error('处理GitHub风格README布局时出错:', error);
    }
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

// 应用缩放级别到预览元素
function applyZoom() {
    const preview = document.getElementById('preview');
    const zoomLevelDisplay = document.getElementById('zoom-level');
    
    if (!preview) return;
    
    // 从localStorage获取保存的缩放级别
    let zoomLevel = 100;
    try {
        const savedZoom = localStorage.getItem(`zoom_${noteId}`);
        if (savedZoom) {
            zoomLevel = parseInt(savedZoom);
        }
    } catch (e) {
        console.error('读取缩放级别时出错:', e);
    }
    
    // 应用缩放
    preview.style.transform = `scale(${zoomLevel / 100})`;
    preview.style.transformOrigin = 'top left';
    
    // 更新显示
    if (zoomLevelDisplay) {
        zoomLevelDisplay.textContent = `${zoomLevel}%`;
    }
    
    return zoomLevel;
}

function reloadMarkdownIt() {
    var errorDisplay = document.getElementById('load-errors');
    if (errorDisplay) {
        errorDisplay.textContent = '正在重新加载...';
    }
    
    try {
        // 获取当前页面的基础路径
        var baseUrl = '';
        if (window.location.pathname.includes('notebook.php')) {
            // 如果是在notebook.php中，需要正确处理相对路径
            baseUrl = new URL('.', window.location.href).href;
        }
        console.log('重新加载使用的基础URL:', baseUrl);
        
        // 移除已存在的脚本标签
        var existingScripts = document.querySelectorAll('script[src*="markdown-it"]');
        existingScripts.forEach(function(script) {
            console.log('移除现有脚本:', script.src);
            script.parentNode.removeChild(script);
        });
        
        // 尝试不同的路径
        var scriptPaths = [
            baseUrl + 'js/markdown-it.min.js',
            'js/markdown-it.min.js',
            '/js/markdown-it.min.js',
            '../js/markdown-it.min.js',
            './js/markdown-it.min.js'
        ];
        
        if (errorDisplay) {
            errorDisplay.textContent = '将尝试路径: ' + scriptPaths.join(', ');
        }
        
        function tryNextPath(index) {
            if (index >= scriptPaths.length) {
                console.error('所有本地路径加载尝试均失败，尝试CDN');
                if (errorDisplay) {
                    errorDisplay.textContent = '本地加载失败，尝试CDN...';
                }
                
                // 尝试从CDN加载
                var cdnScript = document.createElement('script');
                cdnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/markdown-it/12.3.2/markdown-it.min.js';
                cdnScript.onload = function() {
                    console.log('从CDN加载成功');
                    if (errorDisplay) {
                        errorDisplay.textContent = '从CDN加载成功';
                        errorDisplay.style.color = 'green';
                    }
                    // 更新脚本标签信息
                    updateScriptTags();
                    // 重新初始化
                    if (typeof initMarkdown === 'function') {
                        initMarkdown();
                    }
                };
                cdnScript.onerror = function() {
                    console.error('CDN加载也失败');
                    if (errorDisplay) {
                        errorDisplay.textContent = 'CDN加载也失败，请检查网络';
                        errorDisplay.style.color = 'red';
                    }
                };
                document.head.appendChild(cdnScript);
                return;
            }
            
            console.log('尝试路径:', scriptPaths[index]);
            if (errorDisplay) {
                errorDisplay.textContent = '尝试: ' + scriptPaths[index];
            }
            
            var script = document.createElement('script');
            script.src = scriptPaths[index];
            script.onload = function() {
                console.log('成功加载，路径:', scriptPaths[index]);
                if (errorDisplay) {
                    errorDisplay.textContent = '加载成功: ' + scriptPaths[index];
                    errorDisplay.style.color = 'green';
                }
                // 更新脚本标签信息
                updateScriptTags();
                // 重新初始化
                if (typeof initMarkdown === 'function') {
                    initMarkdown();
                }
            };
            script.onerror = function() {
                console.error('路径加载失败:', scriptPaths[index]);
                tryNextPath(index + 1);
            };
            document.head.appendChild(script);
        }
        
        // 开始尝试第一个路径
        tryNextPath(0);
        
    } catch (error) {
        console.error('重载出错:', error);
        if (errorDisplay) {
            errorDisplay.textContent = '重载出错: ' + error.message;
            errorDisplay.style.color = 'red';
        }
    }
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