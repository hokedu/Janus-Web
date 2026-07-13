<?php
/**
 * Initial - 简约而不简单
 * 还原本质 勿忘初心
 *
 * @package Initial
 * @author JIElive
 * @version 2.5.5
 * @link http://www.offodd.com/
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
if ($this->_currentPage == 1 && !empty($this->options->ShowWhisper) && in_array('index', $this->options->ShowWhisper)): ?>
<article class="post whisper">
<?php Whisper(); ?>
</article>
<?php endif; ?>
<?php if ($this->_currentPage == 1): ?>
<?php $stickyPosts = getStickyPosts(); ?>
<?php if (!empty($stickyPosts)): ?>
<div class="sticky-posts" style="border-left:3px solid #e74c3c;padding:15px;margin-bottom:20px;background:#fdf2f2;border-radius:4px;">
<h3 style="margin:0 0 10px;font-size:14px;color:#c0392b;">📌 置顶文章</h3>
<?php foreach ($stickyPosts as $post): ?>
<div style="margin-bottom:5px;font-size:14px;">
<a href="<?php echo $this->options->siteUrl(); ?>/<?php echo date('Y/m/d', $post['created']); ?>/<?php echo $post['slug']; ?>.html" style="font-weight:bold;color:#2c3e50;"><?php echo $post['title']; ?></a>
<span style="color:#999;font-size:12px;margin-left:10px;"><?php echo date('Y-m-d', $post['created']); ?></span>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php while($this->next()): ?>
<article class="post<?php if ($this->options->PjaxOption && $this->hidden): ?> protected<?php endif; ?>">
<h2 class="post-title"><a href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h2>
<ul class="post-meta">
<li><?php $this->date(); ?></li>
<li><?php $this->category(',', false); ?></li>
<li><?php $this->commentsNum('暂无评论', '%d 条评论'); ?></li>
<li><?php Postviews($this); ?></li>
</ul>
<div class="post-content">
<?php if ($this->options->PjaxOption && $this->hidden): ?>
<form <?php if (!$this->options->AjaxLoad): ?>action="<?php echo Typecho_Widget::widget('Widget_Security')->getTokenUrl($this->permalink); ?>" <?php endif; ?>method="post">
<p class="word">请输入密码访问</p>
<p>
<input type="password" class="text" name="protectPassword" />
<input type="submit" class="submit" value="提交" />
</p>
</form>
<?php else: ?>
<?php if (postThumb($this)): ?>
<p class="thumb"><?php echo postThumb($this); ?></p>
<?php endif; ?>
<p><?php $this->excerpt(200, ''); ?></p>
<?php endif; if (!$this->options->OneCOL): ?>
<p class="more"><a href="<?php $this->permalink() ?>" title="<?php $this->title() ?>">- 阅读全文 -</a></p>
<?php endif; ?>
</div>
</article>
<?php endwhile; ?>
<?php $this->pageNav('上一页', $this->options->AjaxLoad ? '查看更多' : '下一页', 0, '..', $this->options->AjaxLoad ? array('wrapClass' => $this->options->AjaxLoad == 'auto' ? 'page-navigator ajaxload auto' : 'page-navigator ajaxload') : ''); ?>
</div>
<?php if (!$this->options->OneCOL): $this->need('sidebar.php'); endif; ?>
<?php $this->need('footer.php'); ?>
