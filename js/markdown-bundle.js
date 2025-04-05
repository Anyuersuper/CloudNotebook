/**
 * Markdown处理模块 - 整合了所有Markdown解析相关功能
 * 包含初始化、回退处理和错误处理
 */

// 创建一个全局promise以跟踪Markdown解析器初始化状态
window.mdInitialized = new Promise((resolve) => {
    window.mdResolve = resolve;
});

// 初始化Markdown解析器
function initMarkdown() {
    // 如果已经存在解析器实例，则跳过
    if (window.md) {
        window.mdResolve(window.md);
        return;
    }
    
    // 尝试初始化markdown-it
    if (typeof markdownit !== 'undefined') {
        window.md = markdownit({
            html: true,
            breaks: true,
            linkify: true
        });
        window.mdResolve(window.md);
    } else {
        // 提供一个简单的回退解析器
        window.md = {
            render: function(text) {
                return '<div class="markdown-error">Markdown解析器未能加载，请刷新页面或检查网络连接</div>' +
                       '<pre>' + escapeHtml(text) + '</pre>';
            }
        };
        window.mdResolve(window.md);
        
        // 尝试动态加载markdown-it
        loadMarkdownIt();
    }
}

// 立即初始化 - 不等待DOMContentLoaded
initMarkdown();

// 确保DOM加载完成后也执行初始化 (确保兼容性)
window.addEventListener('DOMContentLoaded', function() {
    // 如果尚未初始化，再次尝试初始化
    if (!window.md) {
        initMarkdown();
    }
    
    // 初始化代码高亮
    if (typeof hljs !== 'undefined') {
        hljs.configure({
            tabReplace: '    ',
            languages: ['javascript', 'html', 'css', 'php', 'json', 'markdown', 'sql']
        });
    }
});

// 动态加载markdown-it库
function loadMarkdownIt() {
    const script = document.createElement('script');
    script.src = './js/markdown-it.min.js';
    script.onload = function() {
        initMarkdown();
    };
    script.onerror = function() {
        // 使用备用渲染器
    };
    document.head.appendChild(script);
}

// HTML转义辅助函数
function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
} 