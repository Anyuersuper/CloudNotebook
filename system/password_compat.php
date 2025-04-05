<?php
/**
 * 为低版本PHP提供password_hash/password_verify兼容函数
 * 适用于PHP 5.3.7及以上版本，但低于5.5.0的版本
 */

// 如果函数已存在，不再定义
if (!function_exists('password_hash')) {
    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * 密码哈希创建函数
     * 使用crypt()函数实现BCRYPT算法
     * 
     * @param string $password 需要哈希的密码
     * @param int $algo 算法常量（PASSWORD_DEFAULT或PASSWORD_BCRYPT）
     * @param array $options 选项数组
     * @return string 哈希后的字符串
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
            // 验证成本因子范围
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

        // crypt返回的盐值长度必须是60个字符
        if (strlen($hash) < 60) {
            return false;
        }

        return $hash;
    }

    /**
     * 验证密码是否匹配哈希值
     * 
     * @param string $password 需要验证的密码
     * @param string $hash 存储的哈希值
     * @return bool 如果密码匹配哈希值返回true，否则返回false
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
?> 