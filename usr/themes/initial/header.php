<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="<?php $this->options->charset(); ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php if ($this->options->favicon): ?>
<meta http-equiv="Content-Language" content="zh-CN" />
<link rel="shortcut icon" href="<?php $this->options->favicon(); ?>" />
<?php endif; ?>
<title><?php $this->archiveTitle(array(
'category'  =>  _t('分类 %s 下的文章'),
'search'    =>  _t('包含关键字 %s 的文章'),
'tag'       =>  _t('标签 %s 下的文章'),
'date'      =>  _t('在 %s 发布的文章'),
'author'    =>  _t('作者 %s 发布的文章')
), '', ' - '); ?><?php $this->options->title(); if ($this->is('index') && $this->options->subTitle): ?> - <?php $this->options->subTitle(); endif; ?></title>
<?php $this->header('generator=&template=&pingback=&xmlrpc=&wlw=&commentReply=&rss1=&rss2=&antiSpam=&atom=&description='); ?>
<?php if ($this->is('post') || $this->is('page')): ?>
<meta name="description" content="<?php $this->excerpt(150, ''); ?>" />
<?php else: ?>
<meta name="description" content="<?php $this->options->description(); ?>" />
<?php endif; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "<?php $this->options->title(); ?>",
  "url": "<?php $this->options->siteUrl(); ?>",
  "description": "<?php $this->options->description(); ?>",
  "publisher": {
    "@type": "Organization",
    "name": "Janus眼科小站",
    "url": "https://janusbanana.com.cn/",
    "logo": "https://janusbanana.com.cn/usr/themes/initial/logo.png",
    "sameAs": [
      "https://www.zhihu.com/people/janus-85-60"
    ],
    "contactPoint": {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "availableLanguage": ["Chinese"],
      "url": "https://janusbanana.com.cn/"
    }
  },
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://janusbanana.com.cn/?s={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Janus眼科小站",
  "url": "https://janusbanana.com.cn/",
  "logo": "https://janusbanana.com.cn/usr/themes/initial/logo.png",
  "sameAs": [
    "https://www.zhihu.com/people/janus-85-60"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "customer service",
    "availableLanguage": ["Chinese"],
    "url": "https://janusbanana.com.cn/"
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Janus",
  "alternateName": "janus-85-60",
  "url": "https://janusbanana.com.cn/",
  "sameAs": [
    "https://www.zhihu.com/people/janus-85-60"
  ],
  "credential": "博士",
  "alumniOf": {
    "@type": "EducationalOrganization",
    "name": "南方医科大学"
  },
  "affiliation": {
    "@type": "MedicalOrganization",
    "name": "广东省人民医院",
    "department": "眼科"
  },
  "knowsAbout": [
    "眼科",
    "近视防控",
    "白内障",
    "干眼症",
    "角膜塑形镜",
    "屈光手术",
    "视光学"
  ],
  "description": "南方医科大学临床医学眼科博士，知乎眼科领域答主，专注眼健康科普与临床诊疗。"
}
</script>
<?php
// BreadcrumbList Schema - 所有页面通用
// 用 output buffering 防止 Typecho 的 echo 方法泄露文字
ob_start();
$breadcrumbs = array(
    array('name' => '首页', 'url' => $this->options->siteUrl())
);
$archiveTitle = $this->getArchiveTitle();
if ($this->is('post')) {
    $breadcrumbs[] = array('name' => $this->category(',', false), 'url' => $this->category(',', false, true));
    $breadcrumbs[] = array('name' => $this->title(), 'url' => $this->permalink());
} elseif ($this->is('page')) {
    $breadcrumbs[] = array('name' => $this->title(), 'url' => $this->permalink());
} elseif ($this->is('category')) {
    $breadcrumbs[] = array('name' => $archiveTitle, 'url' => $this->permalink());
} elseif ($this->is('tag')) {
    $breadcrumbs[] = array('name' => '标签: ' . $archiveTitle, 'url' => $this->permalink());
} elseif ($this->is('date')) {
    $breadcrumbs[] = array('name' => $archiveTitle, 'url' => $this->permalink());
} elseif ($this->is('author')) {
    $breadcrumbs[] = array('name' => '作者: ' . $archiveTitle, 'url' => $this->permalink());
} elseif ($this->is('search')) {
    $breadcrumbs[] = array('name' => '搜索: ' . $archiveTitle, 'url' => $this->permalink());
} elseif ($this->is('archive')) {
    $breadcrumbs[] = array('name' => '归档: ' . $archiveTitle, 'url' => $this->permalink());
}
ob_end_clean();
$breadcrumbList = array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array());
$position = 1;
foreach ($breadcrumbs as $crumb) {
    $breadcrumbList['itemListElement'][] = array(
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => $crumb['name'],
        'item' => $crumb['url']
    );
}
?>
<script type="application/ld+json"><?php echo json_encode($breadcrumbList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php if ($this->is('post')): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "<?php $this->title(); ?>",
  "url": "<?php $this->permalink(); ?>",
  "datePublished": "<?php $this->date('c'); ?>",
  "dateModified": "<?php $this->modified('c'); ?>",
  "author": {
    "@type": "Person",
    "name": "<?php $this->author(); ?>",
    "url": "https://janusbanana.com.cn/"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Janus眼科小站",
    "url": "https://janusbanana.com.cn/",
    "logo": {
      "@type": "ImageObject",
      "url": "https://janusbanana.com.cn/usr/themes/initial/logo.png"
    }
  },
  "description": "<?php $this->excerpt(150, ''); ?>",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?php $this->permalink(); ?>"
  },
  "articleSection": "<?php $this->category(',', false); ?>",
  "keywords": "<?php $this->tags(',', true, 'none'); ?>",
  "speakable": {
    "@type": "SpeakableSpecification",
    "cssSelector": [".post-content"]
  }
}
</script>
<?php endif; ?>
<link rel="stylesheet" href="<?php cjUrl('style.min.css') ?>" />
<?php if ($this->options->CustomCSS): ?>
<style type="text/css"><?php $this->options->CustomCSS(); ?></style>
<?php endif; ?>
<style>
#secondary {
    position: -webkit-sticky;
    position: sticky;
    top: 80px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}
@media (max-width: 768px) {
    #secondary { position: static; display: none; }
}
</style>
</head>
<body class="<?php if ($this->options->OneCOL): ?>one-col<?php else: ?>bd<?php endif; if ($this->options->HeadFixed): ?> head-fixed<?php endif; ?>">
<!--[if lt IE 9]>
<div class="browsehappy">当前网页可能 <strong>不支持</strong> 您正在使用的浏览器. 为了正常的访问, 请 <a href="https://browsehappy.com/">升级您的浏览器</a>.</div>
<![endif]-->
<header id="header">
<div class="container clearfix">
<div class="site-name">
<<?php echo $this->is('post') || $this->is('page') ? 'p' : 'h1' ?> class="site-title">
<a id="logo" href="<?php $this->options->siteUrl(); ?>" rel="home"><?php if ($this->options->logoUrl && ($this->options->titleForm == 'logo' || $this->options->titleForm == 'all')): ?><img src="<?php $this->options->logoUrl() ?>" alt="<?php $this->options->title() ?>" title="<?php $this->options->title() ?>" /><?php endif; ($this->options->titleForm == 'logo' && $this->options->logoUrl) ? '' : ($this->options->customTitle ? $this->options->customTitle() : $this->options->title()) ?>
</a>
</<?php echo $this->is('post') || $this->is('page') ? 'p' : 'h1' ?>>
</div>
<script>function Navswith(){document.getElementById("header").classList.toggle("on")}</script>
<button id="nav-swith" onclick="Navswith()"><span></span></button>
<div id="nav">
<div id="site-search">
<form id="search" method="post" action="<?php $this->options->siteUrl(); ?>">
<input type="text" id="s" name="s" class="text" placeholder="输入关键字搜索" required />
<button type="submit"></button>
</form>
</div>
<ul class="nav-menu">
<li><a href="<?php $this->options->siteUrl(); ?>">首页</a></li>
<?php if (!empty($this->options->Navset) && in_array('ShowCategory', $this->options->Navset)): if (in_array('AggCategory', $this->options->Navset)): ?>
<li class="menu-parent"><a><?php echo $this->options->CategoryText ? $this->options->CategoryText : '分类' ?></a>
<ul>
<?php
endif;
$this->widget('Widget_Metas_Category_List')->to($categorys);
while($categorys->next()):
if ($categorys->levels == 0):
$children = $categorys->getAllChildren($categorys->mid);
if (empty($children)):
?>
<li><a href="<?php $categorys->permalink(); ?>" title="<?php $categorys->name(); ?>"><?php $categorys->name(); ?></a></li>
<?php else: ?>
<li class="menu-parent">
<a href="<?php $categorys->permalink(); ?>" title="<?php $categorys->name(); ?>"><?php $categorys->name(); ?></a>
<ul class="menu-child">
<?php foreach ($children as $mid) {
$child = $categorys->getCategory($mid); ?>
<li><a href="<?php echo $child['permalink'] ?>" title="<?php echo $child['name']; ?>"><?php echo $child['name']; ?></a></li>
<?php } ?>
</ul>
</li>
<?php
endif;
endif;
endwhile;
?>
<?php if (in_array('AggCategory', $this->options->Navset)): ?>
</ul>
</li>
<?php
endif;
endif;
if (!empty($this->options->Navset) && in_array('ShowPage', $this->options->Navset)):
if (in_array('AggPage', $this->options->Navset)):
?>
<li class="menu-parent"><a><?php echo $this->options->PageText ? $this->options->PageText : '其他' ?></a>
<ul>
<?php
endif;
$this->widget('Widget_Contents_Page_List')->to($pages);
while($pages->next()):
?>
<li><a href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a></li>
<?php endwhile;
if (in_array('AggPage', $this->options->Navset)): ?>
</ul>
</li>
<?php endif;
endif; ?>
</ul>
</div>
</div>
</header>
<div id="body"<?php if ($this->options->PjaxOption): ?> in-pjax<?php endif; ?>>
<div class="container clearfix">
<div id="main">
