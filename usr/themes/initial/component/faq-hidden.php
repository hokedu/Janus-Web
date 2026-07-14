<?php
/**
 * FAQ 隐形渲染组件
 * 仅当文章有 FAQ 字段时输出 FAQPage JSON-LD Schema（给 AI 爬虫）
 * 前端不可见，无可见 DOM
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$archive = $this;
if (!$archive->is('post')) return;

$faqItems = parseFaqGroup($archive->fields->faq_group);
if (empty($faqItems)) return;

$faqSchema = array(
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array()
);
foreach ($faqItems as $item) {
    $faqSchema['mainEntity'][] = array(
        '@type' => 'Question',
        'name' => $item['q'],
        'acceptedAnswer' => array(
            '@type' => 'Answer',
            'text' => $item['a']
        )
    );
}
?>
<script type="application/ld+json">
<?php echo json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>