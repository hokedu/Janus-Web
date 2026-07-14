<?php
/**
 * feed.php - JSON Feed 生成器
 * 访问：/feed.json
 * 兼容：https://www.jsonfeed.org/version/1.1/
 */

require_once __DIR__ . '/config.inc.php';
if (!defined('__TYPECHO_ROOT_DIR__')) {
    define('__TYPECHO_ROOT_DIR__', __DIR__ . '/var');
}

Typecho_Common::init();
$options = Typecho_Widget::widget('Widget_Options');
$db = Typecho_Db::get();

$siteUrl = $options->siteUrl;
$siteTitle = $options->title;
$siteDesc = $options->description;

$posts = $db->fetchAll($db->select()->from('table.contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish')
    ->where('created < ?', $options->time)
    ->order('created', Typecho_Db::SORT_DESC)
    ->limit(50));

$items = array();
foreach ($posts as $post) {
    $permalink = rtrim($siteUrl, '/') . '/' . date('Y/m/d', $post['created']) . '/' . $post['slug'] . '.html';
    
    // 摘要
    $excerpt = $post['text'];
    $excerpt = preg_replace('/<img[^>]+>/', '', $excerpt);
    $excerpt = strip_tags($excerpt);
    $excerpt = mb_substr($excerpt, 0, 300, 'utf-8') . '...';
    
    // 图片
    $image = '';
    if (preg_match('/<img.*?src=["\']([^"\']+)["\']/', $post['text'], $matches)) {
        $image = $matches[1];
    }
    
    // 作者
    $author = $db->fetchRow($db->select('name', 'mail', 'url')
        ->from('table.users')
        ->where('uid = ?', $post['authorId']));
    
    $items[] = array(
        'id' => $permalink,
        'url' => $permalink,
        'title' => $post['title'],
        'content_html' => $post['text'],
        'summary' => $excerpt,
        'image' => $image,
        'date_published' => date('c', $post['created']),
        'date_modified' => date('c', $post['modified'] ? $post['modified'] : $post['created']),
        'author' => array(
            'name' => $author['name'],
            'url' => $author['url'] ?: $siteUrl,
            'avatar' => 'https://cn.gravatar.com/avatar/' . md5(strtolower(trim($author['mail']))) . '?s=200&d=mp'
        ),
        'tags' => array()
    );
}

$feed = array(
    'version' => 'https://jsonfeed.org/version/1.1',
    'title' => $siteTitle,
    'home_page_url' => rtrim($siteUrl, '/') . '/',
    'feed_url' => rtrim($siteUrl, '/') . '/feed.json',
    'description' => $siteDesc,
    'icon' => rtrim($siteUrl, '/') . '/usr/themes/initial/logo.png',
    'favicon' => rtrim($siteUrl, '/') . '/usr/themes/initial/logo.png',
    'author' => array(
        'name' => $options->title ?: 'Janus',
        'url' => rtrim($siteUrl, '/') . '/',
        'avatar' => rtrim($siteUrl, '/') . '/usr/themes/initial/logo.png'
    ),
    'language' => 'zh-CN',
    'items' => $items
);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);