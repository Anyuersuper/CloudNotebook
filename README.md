# 📝 云笔记 (Cloud Notebook)

![PHP Version](https://img.shields.io/badge/PHP-5.6+-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

云笔记是一个简洁、安全、高效的在线Markdown笔记工具，支持实时预览、密码保护和管理功能。它专为需要随时记录和分享想法的用户设计，无需注册即可快速创建和访问笔记本。

## ✨ 主要功能

- 🔒 **密码保护**：每个笔记本都有独立密码，确保内容安全
- 📋 **Markdown支持**：支持完整的Markdown语法，包括标题、列表、代码块等
- 🖥️ **实时预览**：编辑时实时显示渲染后的Markdown效果
- 💾 **自动保存**：自动保存编辑内容，防止意外丢失
- 🌈 **代码高亮**：支持多种编程语言的代码语法高亮
- 📱 **响应式设计**：适配不同设备屏幕，包括手机和平板
- 🔍 **管理功能**：提供管理界面查看和管理所有笔记本
- 🌐 **公开分享**：支持将笔记本设置为公开，无需密码即可访问
- 🔐 **密码策略**：可设置"总是需要密码"，增强安全性
- 🎨 **现代界面**：采用现代化的开关控件和动画效果

## 🛠️ 技术栈

- **前端**：
  - HTML5, CSS3, JavaScript
  - Markdown-it (Markdown解析)
  - highlight.js (代码高亮)
  - Font Awesome (图标)

- **后端**：
  - PHP (支持5.6+)
  - SQLite (数据存储)
  - 原生会话管理

## 🚀 如何使用

### 创建和访问笔记本

1. 在主页输入笔记本ID
2. 首次访问时设置密码，创建新笔记本
3. 再次访问时输入密码进入笔记本
4. 使用Markdown语法编辑内容，右侧实时预览

### 公开/私密笔记访问

1. **私密笔记**：
   - 必须通过密码访问
   - 每次访问都需要输入密码（如果设置了"总是需要密码"）

2. **公开笔记**：
   - 可以通过链接直接访问，无需密码
   - 访问地址：`https://你的域名/notebook.php?id=笔记ID`
   - 在笔记本右上角设置中可以将笔记切换为公开/私密

### 管理入口使用

1. 点击首页底部的"管理入口"链接
2. 使用默认密码 `notebook` 登录 (强烈建议修改默认密码)
3. 在管理界面可以查看和删除所有笔记本

### 修改管理员密码

1. 打开 `system/admin.php` 文件
2. 找到第8行: `$admin_password = 'notebook';`
3. 将 `notebook` 修改为您想要设置的新密码
4. 保存文件

## 📦 安装说明

### 环境要求

- PHP 5.6 或更高版本
- SQLite 支持
- Web服务器 (Apache, Nginx等)

### 安装步骤

1. 下载或克隆项目代码
2. 将所有文件上传到您的网站根目录或子目录
3. 确保 `data` 目录可写 (权限设置为 755)
4. 访问网站地址，例如 `http://yourdomain.com/` 或 `http://yourdomain.com/cloudnote/`
5. 立即修改管理员默认密码 (见上文)

## 📁 项目结构

```
云笔记/
├── css/                  # 样式文件
│   └── app.css           # 主样式表
├── data/                 # 数据存储 (自动创建)
│   └── notebook.db       # SQLite数据库文件
├── js/                   # JavaScript文件
│   ├── highlight.min.js  # 代码高亮库
│   ├── main.js           # 主要应用逻辑
│   ├── markdown-bundle.js # Markdown处理集成
│   └── markdown-it.min.js # Markdown解析库
├── system/               # 后端系统文件
│   ├── admin.php         # 管理界面
│   ├── api.php           # API入口
│   ├── core.php          # 核心功能
│   └── notebook_layout.php # 笔记本界面
├── index.php             # 网站主页
└── notebook.php          # 笔记本入口
```

## 🔐 安全注意事项

- 立即修改默认管理员密码
- 定期备份 `data` 目录下的数据库文件
- 在生产环境中，设置合适的文件权限

## 📄 许可协议

MIT License

## 👨‍💻 关于作者

云笔记项目由欲儿开发，旨在提供一款简单易用的在线笔记工具。

---

💡 **提示**: 如果您有任何问题或建议，欢迎提交Issue或Pull Request。 