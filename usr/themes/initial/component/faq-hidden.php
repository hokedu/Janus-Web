<?php
/**
 * FAQ 隐形渲染组件
 * 仅当文章有 FAQ 字段时输出：
 * 1. FAQPage JSON-LD Schema（给 AI 爬虫）
 * 2. 隐形 DOM（display:none，给结构化数据测试工具验证）
 * 前端用户不可见
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$archive = $this;
if (!$archive->is('post')) return;

$faqItems = array();
if (!empty($archive->fields->faq_group)) {
    $lines = preg_split('/\r\n|\r|\n/', $archive->fields->faq_group);
    $q = null;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if ($q === null) {
            $q = $line;
        } else {
            $faqItems[] = array('q' => $q, 'a' => $line);
            $q = null;
            if (count($faqItems) >= 3) break;
        }
    }
}

if (empty($faqItems)) return;
?>

<!-- FAQPage Schema (AI 爬虫可读) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
<?php
foreach ($faqItems as $index => $item) {
    echo '    {' . PHP_EOL;
    echo '      "@type": "Question",' . PHP_EOL;
    echo '      "name": ' . json_encode($item['q'], JSON_UNESCAPED_UNICODE) . ',' . PHP_EOL;
    echo '      "acceptedAnswer": {' . PHP_EOL;
    echo '        "@type": "Answer",' . PHP_EOL;
    echo '        "text": ' . json_encode($item['a'], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo '      }' . PHP_EOL;
    echo '    }' . ($index < count($faqItems) - 1 ? ',' : '') . PHP_EOL;
}
?>
  ]
}
</script>

<!-- 隐形 DOM（结构化数据测试工具验证用，前端不可见） -->
<div class="faq-hidden" style="display:none;" itemscope itemtype="https://schema.org/FAQPage">
<?php foreach ($faqItems as $item): ?>
  <div itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
    <meta itemprop="name" content="<?php echo htmlspecialchars($item['q']); ?>" />
    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
      <meta itemprop="text" content="<?php echo htmlspecialchars($item['a']); ?>" />
    </div>
  </div>
<?php endforeach; ?>
</div>