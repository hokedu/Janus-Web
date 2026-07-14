<?php
/**
 * sitemap.php - 静态 Sitemap 生成器
 * 用法：php sitemap.php > sitemap.xml
 * 宝塔计划任务：每天 03:00 执行 php /www/wwwroot/janusbanana.com.cn/sitemap.php > /www/wwwroot/janusbanana.com.cn/sitemap.xml
 */

require_once __DIR__ . '/config.inc.php';
if (!defined('__TYPECHO_ROOT_DIR__')) {
    define('__TYPECHO_ROOT_DIR__', __DIR__ . '/var');
}

Typecho_Common::init();
$options = Typecho_Widget::widget('Widget_Options');
$db = Typecho_Db::get();

$siteUrl = $options->siteUrl;
$now = date('c');

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;

// 首页
echo '  <url>' . PHP_EOL;
echo '    <loc>' . htmlspecialchars($siteUrl) . '</loc>' . PHP_EOL;
echo '    <lastmod>' . $now . '</lastmod>' . PHP_EOL;
echo '    <changefreq>daily</changefreq>' . PHP_EOL;
echo '    <priority>1.0</priority>' . PHP_EOL;
echo '  </url>' . PHP_EOL;

// 文章页
$posts = $db->fetchAll($db->select('cid', 'slug', 'title', 'text', 'created', 'modified')
    ->from('table.contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish')
    ->where('created < ?', $options->time)
    ->order('modified', Typecho_Db::SORT_DESC));

foreach ($posts as $post) {
    $url = $siteUrl . '/' . date('Y/m/d', $post['created']) . '/' . $post['slug'] . '.html';
    $lastmod = date('c', $post['modified'] ? $post['modified'] : $post['created']);
    
    // 提取文章第一张图片
    $imageUrl = '';
    if (preg_match('/<img.*?src=["\']([^"\']+)["\']/', $post['text'], $matches)) {
        $imageUrl = $matches[1];
    }
    
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
    echo '    <priority>0.8</priority>' . PHP_EOL;
    if ($imageUrl) {
        echo '    <image:image>' . PHP_EOL;
        echo '      <image:loc>' . htmlspecialchars($imageUrl) . '</image:loc>' . PHP_EOL;
        echo '      <image:title>' . htmlspecialchars($post['title']) . '</image:title>' . PHP_EOL;
        echo '    </image:image>' . PHP_EOL;
    }
    echo '  </url>' . PHP_EOL;
}

// 独立页面
$pages = $db->fetchAll($db->select('cid', 'slug', 'created', 'modified')
    ->from('table.contents')
    ->where('type = ?', 'page')
    ->where('status = ?', 'publish')
    ->where('created < ?', $options->time)
    ->order('created', Typecho_Db::SORT_DESC));

foreach ($pages as $page) {
    $permalink = $siteUrl . '/' . $page['slug'] . '.html';
    $modified = date('c', $page['modified']);
    
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($permalink) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . htmlspecialchars($modified) . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
    echo '    <priority>0.5</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 分类页
$categories = $db->fetchAll($db->select()->from('table.metas')
    ->where('type = ?', 'category')
    ->where('count > ?', 0)
    ->order('count', Typecho_Db::SORT_DESC));

foreach ($categories as $cat) {
    $permalink = $siteUrl . '/category/' . $cat['slug'] . '/';
    // 该分类最新文章时间
    $latest = $db->fetchRow($db->select('modified')->from('table.relationships')
        ->join('table.contents', 'table.contents.cid = table.relationships.cid', Typecho_Db::LEFT_JOIN)
        ->where('table.relationships.mid = ?', $cat['mid'])
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.type = ?', 'post')
        ->order('table.contents.modified', Typecho_Db::SORT_DESC)
        ->limit(1));
    $lastmod = $latest ? date('c', $latest['modified']) : $now;
    
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($permalink) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . htmlspecialchars($lastmod) . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
    echo '    <priority>0.6</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 标签页
$tags = $db->fetchAll($db->select()->from('table.metas')
    ->where('type = ?', 'tag')
    ->where('count > ?', 0)
    ->order('count', Typecho_Db::SORT_DESC)
    ->limit(100)); // 限制 100 个热门标签

foreach ($tags as $tag) {
    $permalink = $siteUrl . '/tag/' . $tag['slug'] . '/';
    $latest = $db->fetchRow($db->select('modified')->from('table.relationships')
        ->join('table.contents', 'table.contents.cid = table.relationships.cid', Typecho_Db::LEFT_JOIN)
        ->where('table.relationships.mid = ?', $tag['mid'])
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.type = ?', 'post')
        ->order('table.contents.modified', Typecho_Db::SORT_DESC)
        ->limit(1));
    $lastmod = $latest ? date('c', $latest['modified']) : $now;
    
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($permalink) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . htmlspecialchars($lastmod) . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
    echo '    <priority>0.5</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 归档页（按年月）
$archives = $db->fetchAll($db->select('DATE_FORMAT(FROM_UNIXTIME(created), "%Y/%m") as ym, MAX(modified) as lastmod')
    ->from('table.contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish')
    ->where('created < ?', $options->time)
    ->group('ym')
    ->order('lastmod', Typecho_Db::SORT_DESC));

foreach ($archives as $archive) {
    $permalink = $siteUrl . '/' . $archive['ym'] . '/';
    $lastmod = date('c', $archive['lastmod']);
    
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($permalink) . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . htmlspecialchars($lastmod) . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
    echo '    <priority>0.4</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

echo '</urlset>' . PHP_EOL;