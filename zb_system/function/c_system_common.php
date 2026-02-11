<?php

/**
 * 辅助通用函数.
 */

if (!defined('ZBP_PATH')) {
    exit('Access denied');
}

/**
 * HTTP服务器及系统检测函数**************************************************************.
 */

/**
 * 得到请求协议（考虑到不正确的配置反向代理等原因，未必准确）
 * 如果想获取准确的值，请zbp->Load后使用$zbp->isHttps.
 *
 * @param array $array
 *
 * @return string
 */
function GetScheme($array)
{
    $array = array_change_key_case($array, CASE_UPPER);

    // 优先检测 Hugging Face / Cloudflare 的转发协议
    if (isset($array['HTTP_X_FORWARDED_PROTO']) && strtolower($array['HTTP_X_FORWARDED_PROTO']) == 'https') {
        return 'https://';
    }

    if (array_key_exists('REQUEST_SCHEME', $array) && (strtolower($array['REQUEST_SCHEME']) == 'https')) {
        return 'https://';
    } elseif (array_key_exists('HTTPS', $array) && (strtolower($array['HTTPS']) == 'on')) {
        return 'https://';
    } elseif (array_key_exists('SERVER_PORT', $array) && ($array['SERVER_PORT'] == 443)) {
        return 'https://';
    } elseif (array_key_exists('HTTP_X_FORWARDED_PORT', $array) && ($array['HTTP_X_FORWARDED_PORT'] == 443)) {
        return 'https://';
    } elseif (array_key_exists('HTTP_X_FORWARDED_PROTOCOL', $array) && (strtolower($array['HTTP_X_FORWARDED_PROTOCOL']) == 'https')) {
        return 'https://';
    } elseif (array_key_exists('HTTP_X_FORWARDED_SSL', $array) && (strtolower($array['HTTP_X_FORWARDED_SSL']) == 'on')) {
        return 'https://';
    } elseif (array_key_exists('HTTP_X_URL_SCHEME', $array) && (strtolower($array['HTTP_X_URL_SCHEME']) == 'https')) {
        return 'https://';
    } elseif (array_key_exists('HTTP_CF_VISITOR', $array) && (stripos($array['HTTP_CF_VISITOR'], 'https') !== false)) {
        return 'https://';
    } elseif (array_key_exists('HTTP_FROM_HTTPS', $array) && (strtolower($array['HTTP_FROM_HTTPS']) == 'on')) {
        return 'https://';
    } elseif (array_key_exists('HTTP_FRONT_END_HTTPS', $array) && (strtolower($array['HTTP_FRONT_END_HTTPS']) == 'on')) {
        return 'https://';
    } elseif (array_key_exists('SERVER_PORT_SECURE', $array) && ($array['SERVER_PORT_SECURE'] == 1)) {
        return 'https://';
    } elseif (array_key_exists('HTTP_X_CLIENT_SCHEME', $array) && (strtolower($array['HTTP_X_CLIENT_SCHEME']) == 'https')) {
        return 'https://';
    }
    return 'http://';
}

/**
 * 获取服务器.
 *
 * @return int
 */
function GetWebServer()
{
    if (!isset($_SERVER['SERVER_SOFTWARE'])) {
        return SERVER_UNKNOWN;
    }
    $webServer = strtolower($_SERVER['SERVER_SOFTWARE']);
    if (strpos($webServer, 'apache') !== false) {
        return SERVER_APACHE;
    } elseif (strpos($webServer, 'microsoft-iis') !== false) {
        return SERVER_IIS;
    } elseif (strpos($webServer, 'nginx') !== false) {
        return SERVER_NGINX;
    } elseif (strpos($webServer, 'lighttpd') !== false) {
        return SERVER_LIGHTTPD;
    } elseif (strpos($webServer, 'kangle') !== false) {
        return SERVER_KANGLE;
    } elseif (strpos($webServer, 'caddy') !== false) {
        return SERVER_CADDY;
    } elseif (strpos($webServer, 'development server') !== false) {
        return SERVER_BUILTIN;
    } else {
        return SERVER_UNKNOWN;
    }
}

/**
 * 获取操作系统
 *
 * @return int
 */
function GetSystem()
{
    if (in_array(strtoupper(PHP_OS), array('WINNT', 'WIN32', 'WINDOWS'))) {
        return SYSTEM_WINDOWS;
    } elseif ((strtoupper(PHP_OS) === 'UNIX')) {
        return SYSTEM_UNIX;
    } elseif (strtoupper(PHP_OS) === 'LINUX') {
        return SYSTEM_LINUX;
    } elseif (strtoupper(PHP_OS) === 'DARWIN') {
        return SYSTEM_DARWIN;
    } elseif (strtoupper(substr(PHP_OS, 0, 6)) === 'CYGWIN') {
        return SYSTEM_CYGWIN;
    } elseif (in_array(strtoupper(PHP_OS), array('NETBSD', 'OPENBSD', 'FREEBSD'))) {
        return SYSTEM_BSD;
    } else {
        return SYSTEM_UNKNOWN;
    }
}

/**
 * 获取PHP解析引擎.
 *
 * @return int
 */
function GetPHPEngine()
{
    return ENGINE_PHP;
}

/**
 * 获取PHP Version.
 *
 * @return string
 */
function GetPHPVersion()
{
    $p = phpversion();
    if (strpos($p, '-') !== false) {
        $p = substr($p, 0, strpos($p, '-'));
    }

    return $p;
}

/**
 * 获取当前网站地址
 */
function GetCurrentHost($blogpath, &$cookiesPath)
{
    $host = HTTP_SCHEME;

    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host .= $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $host .= $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER["SERVER_NAME"])) {
        $host .= $_SERVER["SERVER_NAME"];
    } else {
        $cookiesPath = '/';
        return '/';
    }

    if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
        $x = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $y = $blogpath;
        if (strpos($x, $y) !== false) {
            $x = str_replace($y, '', $x);
            $x = ltrim($x, '/');
            $x = '/' . $x;
        }
        for ($i = 0; $i < strlen($x); $i++) {
            $f = $y . substr($x, ($i - strlen($x)));
            $z = substr($x, 0, $i);
            if (file_exists($f) && is_file($f)) {
                $z = trim($z, '/');
                $z = '/' . $z . '/';
                $z = str_replace('//', '/', $z);
                $cookiesPath = $z;

                return $host . $z;
            }
        }
    }

    $cookiesPath = '/';
    return $host . $cookiesPath;
}

// ... 篇幅原因，此处省略中间未改动的原代码 (SetHttpStatusCode, JsonEncode, SubStrUTF8 等函数) ...
// 请保持你原有的中间函数内容不变，直到文件最后一行。

/**
 * ==========================================================
 * 牛哥专用：强制关闭验证码补丁 (针对 HF 环境优化)
 * ==========================================================
 */
// 这一行会在 Z-Blog 初始化配置时强行覆盖开关
if (!isset($GLOBALS['option'])) {
    $GLOBALS['option'] = array();
}
$GLOBALS['option']['ZC_LOGIN_VERIFY_ENABLE'] = false; // 永久关闭登录验证码
$GLOBALS['option']['ZC_COMMENT_VERIFY_ENABLE'] = false; // 永久关闭评论验证码
