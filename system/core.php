<?php
/**
 * 云笔记系统核心文件
 * 整合了数据库操作、API处理和密码验证功能
 */

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置会话超时时间（单位：秒）
ini_set('session.gc_maxlifetime', 86400); // 1天后会话过期
session_set_cookie_params(86400); // 设置cookie生命周期为1天分钟

// 只有在会话尚未启动时才启动会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * ====================================================
 * 密码兼容库 - 为低版本PHP提供密码哈希功能
 * ====================================================
 */
if (!function_exists('password_hash')) {
    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * 密码哈希创建函数
     * 使用crypt()函数实现BCRYPT算法
     */
    function password_hash($password, $algo, array $options = array()) {
        if (!is_string($password)) {
            trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
            return null;
        }

        if (!is_int($algo)) {
            trigger_error("password_hash(): Argument #2 is not a valid password algorithm", E_USER_WARNING);
            return null;
        }

        $cost = isset($options['cost']) ? (int) $options['cost'] : 10;
        if ($cost < 4 || $cost > 31) {
            $cost = 10;
        }

        // 创建盐值
        $salt = "";
        for ($i = 0; $i < 22; $i++) {
            $salt .= substr('./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', mt_rand(0, 63), 1);
        }

        // 格式化盐值为Blowfish格式
        $salt = sprintf('$2y$%02d$%s', $cost, $salt);
        $hash = crypt($password, $salt);

        if (strlen($hash) < 60) {
            return false;
        }

        return $hash;
    }

    /**
     * 验证密码是否匹配哈希值
     */
    function password_verify($password, $hash) {
        if (!is_string($password) || !is_string($hash)) {
            return false;
        }
        
        $check = crypt($password, $hash);
        
        // 使用恒定时间比较防止时序攻击
        $result = 0;
        $hashLen = strlen($hash);
        $checkLen = strlen($check);
        $len = $hashLen < $checkLen ? $hashLen : $checkLen;
        
        for ($i = 0; $i < $len; $i++) {
            $result |= (ord($hash[$i]) ^ ord($check[$i]));
        }
        
        return $result === 0 && $hashLen === $checkLen;
    }
}

/**
 * ====================================================
 * 数据库操作类 - 使用SQLite存储笔记本数据
 * ====================================================
 */
class NotebookDB {
    private $db;
    private static $instance;
    
    /**
     * 构造函数 - 初始化数据库连接
     */
    private function __construct() {
        try {
            // 计算数据库文件的绝对路径
            $db_path = dirname(__DIR__) . '/data/notebook.db';
            
            // 创建/连接到SQLite数据库
            $this->db = new PDO('sqlite:' . $db_path);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 初始化表结构（如果不存在）
            $this->initTables();
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取单例实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            // 确保data目录存在
            $data_dir = dirname(__DIR__) . '/data';
            if (!is_dir($data_dir)) {
                if (!mkdir($data_dir, 0755, true)) {
                    die('无法创建data目录');
                }
            }
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初始化数据库表
     */
    private function initTables() {
        // 创建笔记本表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS notebooks (
                id TEXT PRIMARY KEY,
                password_hash TEXT NOT NULL,
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                always_require_password BOOLEAN DEFAULT 0,
                ispublic BOOLEAN DEFAULT 0
            )
        ');
    }
    
    /**
     * 检查笔记本是否存在
     */
    public function notebookExists($id) {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notebooks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 创建新笔记本
     */
    public function createNotebook($id, $password) {
        // 直接使用传入的密码哈希，不再进行哈希处理
        $default_content = <<<EOT
# 欢迎使用云笔记

这是一个简单、安全的在线笔记工具。

## 基本功能
- 左侧编辑，右侧实时预览
- 所有内容会自动保存
- 可以随时通过密码访问你的笔记
- 支持将笔记设置为公开或私密

## 如何使用
1. 编辑内容：直接在左侧编辑区输入内容
2. 保存：内容会自动保存，无需手动操作
3. 访问笔记：
   - 私密笔记：通过密码访问
   - 公开笔记：直接通过链接访问，无需密码
4. 设置公开/私密：
   - 点击右上角的设置按钮
   - 选择"设为公开"或"设为私密"
   - 公开笔记的访问地址：https://你的域名/notebook.php?id=笔记ID

开始记录你的想法吧！
EOT;
        $stmt = $this->db->prepare("INSERT INTO notebooks (id, password_hash, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$id, $password, $default_content, $now, $now]);
        return true;
    }
    
    /**
     * 获取笔记本内容
     */
    public function getNotebookContent($id) {
        $stmt = $this->db->prepare('SELECT content FROM notebooks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['content'] : '';
    }
    
    /**
     * 获取笔记本密码哈希
     */
    public function getPasswordHash($id) {
        $stmt = $this->db->prepare('SELECT password_hash FROM notebooks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['password_hash'] : '';
    }
    
    /**
     * 保存笔记本内容
     */
    public function saveNotebook($id, $content) {
        $stmt = $this->db->prepare('
            UPDATE notebooks 
            SET content = :content, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        return $stmt->execute();
    }
    
    /**
     * 设置是否总是要求密码
     */
    public function setAlwaysRequirePassword($id, $value) {
        $stmt = $this->db->prepare('
            UPDATE notebooks 
            SET always_require_password = :value 
            WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $value = (int) $value;
        $stmt->bindParam(':value', $value, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * 检查是否总是要求密码
     */
    public function getAlwaysRequirePassword($id) {
        $stmt = $this->db->prepare('SELECT always_require_password FROM notebooks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (bool)$result['always_require_password'] : false;
    }
    
    /**
     * 获取所有记事本列表
     */
    public function getAllNotebooks() {
        try {
            $stmt = $this->db->prepare('
                SELECT id, created_at, updated_at, always_require_password 
                FROM notebooks 
                ORDER BY created_at DESC
            ');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * 分页获取记事本列表
     */
    public function getNotebooksPaginated($offset, $limit) {
        try {
            $stmt = $this->db->prepare('
                SELECT id, created_at, updated_at, always_require_password 
                FROM notebooks 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * 获取记事本总数
     */
    public function getNotebooksCount() {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM notebooks');
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * 更新笔记本设置
     */
    public function updateSettings($id, $settings) {
        // 只支持always_require_password设置
        if (isset($settings['always_require_password'])) {
            return $this->setAlwaysRequirePassword($id, $settings['always_require_password']);
        }
        return false;
    }
    
    /**
     * 删除记事本
     */
    public function deleteNotebook($id) {
        $stmt = $this->db->prepare('DELETE FROM notebooks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        return $stmt->execute();
    }
    
    /**
     * 设置笔记本是否公开
     */
    public function setPublic($id, $isPublic) {
        $stmt = $this->db->prepare('
            UPDATE notebooks 
            SET ispublic = :ispublic 
            WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $isPublic = (int) $isPublic;
        $stmt->bindParam(':ispublic', $isPublic, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * 检查笔记本是否公开
     */
    public function isPublic($id) {
        $stmt = $this->db->prepare('SELECT ispublic FROM notebooks WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (bool)$result['ispublic'] : false;
    }
}

/**
 * ====================================================
 * API处理函数 - 处理笔记本操作请求
 * ====================================================
 */
class NotebookAPI {
    private $db;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->db = NotebookDB::getInstance();
    }
    
    /**
     * 处理API请求
     */
    public function handleRequest() {
        // 设置JSON响应头
        header('Content-Type: application/json');
        
        // 获取请求方法和操作类型
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $id = isset($_POST['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']) : '';

        // 验证ID
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            exit;
        }

        // 根据操作类型处理请求
        try {
            switch ($action) {
                case 'verify_password':
                    $this->verifyPassword($id);
                    break;
                case 'create_note':
                    $this->createNote($id);
                    break;
                case 'save_note':
                    $this->saveNote($id);
                    break;
                case 'update_settings':
                    $this->updateSettings($id);
                    break;
                case 'update_public':
                    $this->updatePublicStatus($id);
                    break;
                case 'get_public_status':
                    $this->getPublicStatus($id);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => '无效的操作']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '处理请求时发生错误']);
        }
    }
    
    /**
     * 验证密码
     */
    private function verifyPassword($id) {
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => '密码不能为空']);
            exit;
        }
        
        if (!$this->db->notebookExists($id)) {
            // 清除可能存在的认证状态
            if (isset($_SESSION['auth_' . $id])) {
                unset($_SESSION['auth_' . $id]);
            }
            echo json_encode(['success' => false, 'message' => '记事本不存在']);
            exit;
        }
        
        // 获取存储的密码哈希
        $password_hash = $this->db->getPasswordHash($id);
        
        if (empty($password_hash)) {
            echo json_encode(['success' => false, 'message' => '读取记事本失败']);
            exit;
        }
        
        // 验证密码
        if (password_verify($password, $password_hash)) {
            // 设置认证状态
            $_SESSION['auth_' . $id] = true;
            
            // 获取是否总是需要密码的设置
            $always_require_password = $this->db->getAlwaysRequirePassword($id);
            
            // 返回验证结果，并通知前端是否总是需要密码
            echo json_encode([
                'success' => true,
                'always_require_password' => $always_require_password
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '密码错误']);
        }
    }
    
    /**
     * 创建新记事本
     */
    private function createNote($id) {
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => '密码不能为空']);
            exit;
        }
        
        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => '两次输入的密码不一致']);
            exit;
        }
        
        if ($this->db->notebookExists($id)) {
            echo json_encode(['success' => false, 'message' => '记事本ID已存在']);
            exit;
        }
        
        // 创建密码哈希 - 只在这里进行一次哈希处理
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // 创建笔记本 - 传递哈希后的密码
        $result = $this->db->createNotebook($id, $password_hash);
        
        if ($result) {
            $_SESSION['auth_' . $id] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '创建记事本失败']);
        }
    }
    
    /**
     * 保存记事本内容
     */
    private function saveNote($id) {
        // 检查认证
        if (!isset($_SESSION['auth_' . $id]) || $_SESSION['auth_' . $id] !== true) {
            echo json_encode(['success' => false, 'message' => '未授权的操作']);
            exit;
        }
        
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        
        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => '内容不能为空']);
            exit;
        }
        
        if (!$this->db->notebookExists($id)) {
            // 清除可能存在的认证状态
            if (isset($_SESSION['auth_' . $id])) {
                unset($_SESSION['auth_' . $id]);
            }
            echo json_encode(['success' => false, 'message' => '记事本不存在']);
            exit;
        }
        
        // 保存笔记本内容
        $result = $this->db->saveNotebook($id, $content);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '保存失败']);
        }
    }
    
    /**
     * 更新笔记本设置
     */
    private function updateSettings($id) {
        // 检查认证
        if (!isset($_SESSION['auth_' . $id]) || $_SESSION['auth_' . $id] !== true) {
            echo json_encode(['success' => false, 'message' => '未授权的操作']);
            exit;
        }
        
        if (!$this->db->notebookExists($id)) {
            // 清除可能存在的认证状态
            if (isset($_SESSION['auth_' . $id])) {
                unset($_SESSION['auth_' . $id]);
            }
            echo json_encode(['success' => false, 'message' => '记事本不存在']);
            exit;
        }
        
        // 获取设置值
        $always_require_password = isset($_POST['always_require_password']) ? (bool)$_POST['always_require_password'] : false;
        
        // 更新设置
        $result = $this->db->updateSettings($id, [
            'always_require_password' => $always_require_password
        ]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新设置失败']);
        }
    }
    
    /**
     * 更新笔记本公开状态
     */
    private function updatePublicStatus($id) {
        // 检查认证
        if (!isset($_SESSION['auth_' . $id]) || $_SESSION['auth_' . $id] !== true) {
            echo json_encode(['success' => false, 'message' => '未授权的操作']);
            exit;
        }
        
        if (!$this->db->notebookExists($id)) {
            echo json_encode(['success' => false, 'message' => '记事本不存在']);
            exit;
        }
        
        $isPublic = isset($_POST['ispublic']) ? (bool)$_POST['ispublic'] : false;
        
        // 更新公开状态
        $result = $this->db->setPublic($id, $isPublic);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新公开状态失败']);
        }
    }
    
    /**
     * 获取笔记本公开状态
     */
    private function getPublicStatus($id) {
        // 检查认证
        if (!isset($_SESSION['auth_' . $id]) || $_SESSION['auth_' . $id] !== true) {
            echo json_encode(['success' => false, 'message' => '未授权的操作']);
            exit;
        }
        
        if (!$this->db->notebookExists($id)) {
            echo json_encode(['success' => false, 'message' => '记事本不存在']);
            exit;
        }
        
        $isPublic = $this->db->isPublic($id);
        echo json_encode(['success' => true, 'isPublic' => $isPublic]);
    }
}

/**
 * ====================================================
 * 笔记本认证和处理函数
 * ====================================================
 */
class NotebookHandler {
    private $db;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->db = NotebookDB::getInstance();
    }
    
    /**
     * 处理注销请求
     */
    public function handleLogout($id) {
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
    
    /**
     * 检查认证状态
     */
    public function checkAuth($id) {
        $is_authenticated = isset($_SESSION['auth_' . $id]) && $_SESSION['auth_' . $id] === true;
        
        // 如果已认证，检查是否设置了总是需要密码
        if ($is_authenticated) {
            try {
                $always_require_password = $this->db->getAlwaysRequirePassword($id);
                if ($always_require_password) {
                    // 如果设置了总是需要密码，且是通过GET请求访问的，且不是刚刚验证过的请求，清除认证状态
                    $verified = isset($_GET['verified']) && $_GET['verified'] == '1';
                    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['logout']) && !$verified) {
                        unset($_SESSION['auth_' . $id]);
                        $is_authenticated = false;
                    }
                }
            } catch (Exception $e) {
                // 忽略错误，默认不需要每次输入密码
            }
        }
        
        return $is_authenticated;
    }
}
?> 
