<?php
/**
 * 数据库操作类
 * 使用SQLite存储笔记本数据
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
                always_require_password BOOLEAN DEFAULT 0
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
    public function createNotebook($id, $password_hash) {
        $content = "# " . $id . " 记事本\n\n开始记录你的想法...";
        $stmt = $this->db->prepare('
            INSERT INTO notebooks (id, password_hash, content) 
            VALUES (:id, :password_hash, :content)
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        return $stmt->execute();
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
     * @param int $offset 起始位置
     * @param int $limit 每页条数
     * @return array 记事本列表
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
     * @return int 记事本总数
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
     * 删除记事本
     */
    public function deleteNotebook($id) {
        try {
            // 在删除前先检查记事本是否存在
            if (!$this->notebookExists($id)) {
                return false;
            }
            
            // 开始事务
            $this->db->beginTransaction();
            
            // 执行删除操作
            $stmt = $this->db->prepare('DELETE FROM notebooks WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $result = $stmt->execute();
            
            // 提交事务
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            // 回滚事务
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return false;
        }
    }
}
?> 