# Janus眼科小站 — GEO (Generative Engine Optimization) 优化实施文档

> **版本**：v1.0  
> **日期**：2026-07-13  
> **维护者**：[待填写]  
> **线上地址**：https://janusbanana.com.cn/  
> **目标 AI 搜索引擎**：DeepSeek / Kimi / 豆包(字节) / 文心一言(百度) / GPTBot / ClaudeBot / PerplexityBot

---

## 1. 现状基线

| 维度 | 现状 | 缺口 |
|------|------|------|
| **Schema.org 标记** | `WebSite` + 基础 `Article` | 缺 `Person`、`Organization`、`BreadcrumbList`、`FAQPage`、`WebPage`、`speakable`、`ImageObject` |
| **作者实体** | 仅管理员账号 | 无 `credential`/`affiliation`/`sameAs`/`knowsAbout` |
| **FAQ 结构** | 发文时手动写 3 个隐形问答 | 无字段、无 Schema、前端可见性不可控 |
| **Sitemap** | 无 | 无 `sitemap.xml`，AI 爬虫无结构化发现入口 |
| **robots.txt** | 仅基础允许 | 无 AI 爬虫白名单、无 Sitemap 指向 |
| **IndexNow** | 无 | 无实时推送，Bing/Yandex 依赖被动抓取 |
| **Feed** | 仅 RSS 1/2 | 无 JSON Feed（AI 友好格式） |
| **安全头** | 无 | 缺 `X-Content-Type-Options`、`X-Frame-Options` |

---

## 2. 目标状态

| 指标 | 目标 | 验收方式 |
|------|------|----------|
| **Schema 覆盖率** | 100% 文章页含 `Article`+`BreadcrumbList`+`FAQPage`(如有) | Schema.org Validator 批量 0 error |
| **AI 爬虫抓取率** | 7 天内 `DeepSeekBot`/`KimiBot`/`GPTBot`/`ClaudeBot`/`Bytespider`/`YisouSpider`/`PerplexityBot` 均出现 200 | Nginx access_log grep |
| **引用曝光** | 3 个月内品牌词/核心词在 DeepSeek/Kimi 搜索出现引用 ≥ 5 次 | 人工搜索记录 / 第三方监测 |
| **知识图谱实体** | 站点名/作者名在百度/搜狗/头条百科有词条 | 搜索验证 |
| **Sitemap 有效性** | `/sitemap.xml` 通过 xmllint，含 `lastmod`/`image:image`/`priority` | `curl -s https://janusbanana.com.cn/sitemap.xml \| xmllint --noout -` |
| **IndexNow 推送** | 发文后 5 分钟内 Bing Webmaster 显示「已提交」 | Bing Webmaster 后台查看 |
| **安全头** | `X-Content-Type-Options: nosniff`、`X-Frame-Options: SAMEORIGIN` | `curl -I https://janusbanana.com.cn/` |

---

## 3. 实施阶段

### Phase 1：核心 Schema + 爬虫准入（P0，预估 2-3h）

| # | 任务 | 文件 | 关键产出 |
|---|------|------|----------|
| 1.1 | `robots.txt` 允许 AI 爬虫 + Sitemap 指向 | 根目录 `robots.txt` | `User-agent: GPTBot/ClaudeBot/DeepSeekBot/KimiBot/Bytespider/YisouSpider/PerplexityBot/bingbot/Googlebot` + `Allow: /` + `Sitemap: https://janusbanana.com.cn/sitemap.xml` |
| 1.2 | `Person` + `Organization` + `WebSite` + `BreadcrumbList` JSON-LD | `usr/themes/initial/header.php` | `<head>` 注入 4 个 `<script type="application/ld+json">` |
| 1.3 | `Article` 完整 Schema（含 `speakable`、`FAQPage` 占位） | `functions.php` 新增 `postSchema()` + `post.php` 调用 | 文章页完整结构化数据 |
| 1.4 | 安全响应头 | 宝塔 Nginx 配置 | `add_header X-Content-Type-Options "nosniff" always;` `add_header X-Frame-Options "SAMEORIGIN" always;` |

**Schema 字段映射（作者实体）**

```json
{
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
    "name": "[执业医院全称，待补充]",
    "department": "眼科"
  },
  "knowsAbout": [
    "眼科", "近视防控", "白内障", "干眼症", "角膜塑形镜", "屈光手术", "视光学"
  ],
  "description": "南方医科大学临床医学眼科博士，知乎眼科领域答主，专注眼健康科普与临床诊疗。"
}
```

**Organization 字段映射（站点发布方）**

```json
{
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
```

---

### Phase 2：FAQ 隐形字段 + 渲染（P1，预估 1.5h）

| # | 任务 | 文件 | 关键产出 |
|---|------|------|----------|
| 2.1 | 后台右侧 Meta Box：3 组 FAQ 字段（问/答各 1 行 Textarea） | `functions.php → themeFields()` | 后台右侧发布按钮附近可见，前端隐形 |
| 2.2 | FAQ 隐形渲染组件 + `FAQPage` Schema | 新建 `component/faq-hidden.php` + `post.php` 引用 | 仅字段非空时输出 JSON-LD + 隐形 DOM (`style="display:none"`) |
| 2.3 | 短代码 `[faq q="..." a="..."]` 兼容历史内容 | `functions.php` 新增 `shortcode_faq()` | 兼容旧文章手动插入 |

**后台字段定义**

| 字段名 | 类型 | 标签 | 说明 |
|--------|------|------|------|
| `faq_1_q` | Textarea | FAQ 1 - 问题 | 隐形问答第 1 条问题 |
| `faq_1_a` | Textarea | FAQ 1 - 回答 | 隐形问答第 1 条回答 |
| `faq_2_q` | Textarea | FAQ 2 - 问题 | 隐形问答第 2 条问题 |
| `faq_2_a` | Textarea | FAQ 2 - 回答 | 隐形问答第 2 条回答 |
| `faq_3_q` | Textarea | FAQ 3 - 问题 | 隐形问答第 3 条问题 |
| `faq_3_a` | Textarea | FAQ 3 - 回答 | 隐形问答第 3 条回答 |

**FAQPage Schema 输出示例**

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "近视手术后会反弹吗？",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "正规手术后屈光状态通常稳定，但需注意用眼卫生，避免二次近视。"
      }
    }
  ]
}
```

---

### Phase 3：Sitemap + IndexNow + JSON Feed（P2，预估 1.5h）

| # | 任务 | 文件 | 关键产出 |
|---|------|------|----------|
| 3.1 | 生成 128 位 IndexNow Key | 根目录 `indexnow-key.txt` | `a1b2c3d4e5f6...` (32 字符十六进制) |
| 3.2 | `sitemap.php` 静态生成器 | 根目录 `sitemap.php` | 遍历 `contents` 表，输出 `urlset` + `image:image` + `lastmod` + `changefreq` + `priority` |
| 3.3 | 宝塔计划任务：每天 03:00 生成 | 宝塔面板 → 计划任务 | `php /www/wwwroot/janusbanana.com.cn/sitemap.php > /www/wwwroot/janusbanana.com.cn/sitemap.xml` |
| 3.4 | 发文钩子自动推送 IndexNow | `functions.php` 挂 `content.publishAfter` | `POST https://api.indexnow.org/indexnow?url=...&key=...` |
| 3.5 | `feed.php` 生成 JSON Feed | 根目录 `feed.php` | `/feed.json` 可访问，含 `authors`/`items`/`favicon`/`icon` |

**Sitemap 字段规则**

| 页面类型 | `changefreq` | `priority` | `lastmod` 来源 |
|----------|--------------|------------|----------------|
| 首页 | `daily` | `1.0` | 最新文章 `modified` |
| 文章页 | `weekly` | `0.8` | 文章 `modified` |
| 分类/标签/归档页 | `weekly` | `0.6` | 该分类最新文章 `modified` |
| 独立页面 | `monthly` | `0.5` | 页面 `modified` |

**IndexNow 推送载荷**

```json
{
  "host": "janusbanana.com.cn",
  "key": "[indexnow-key.txt 内容]",
  "keyLocation": "https://janusbanana.com.cn/indexnow-key.txt",
  "urlList": ["https://janusbanana.com.cn/new-post-url.html"]
}
```

---

### Phase 4：验收与测试（P3，预估 0.5h）

| 验收项 | 工具/命令 | 通过标准 |
|--------|-----------|----------|
| Schema 语法零报错 | Google Rich Results Test / Schema.org Validator | 0 Errors, 0 Warnings (非必填字段除外) |
| AI 爬虫 200 抓取 | `grep -E "DeepSeekBot\|KimiBot\|GPTBot\|ClaudeBot\|Bytespider\|YisouSpider\|PerplexityBot" /www/logs/nginx/access.log \| head -20` | 近 7 天各 Bot 均有 200 记录 |
| Sitemap.xml 有效 | `curl -s https://janusbanana.com.cn/sitemap.xml \| xmllint --noout -` | 无解析错误 |
| IndexNow 推送成功 | 发布测试文章 → Bing Webmaster → URL 提交历史 | 显示「成功」或「已索引」 |
| FAQ 隐形字段 | 发文填 FAQ → 前端查看源码搜索 `FAQPage` | 含 JSON-LD，前端不可见 (`display:none`) |
| 安全头生效 | `curl -I https://janusbanana.com.cn/ \| grep -i "x-content-type-options\|x-frame-options"` | 两行均存在 |

---

## 4. 文件改动清单（最终版）

| 文件 | 操作 | 关键函数/位置 | 预估行数 |
|------|------|---------------|----------|
| `robots.txt` | 重写 | 根目录 | 15 行 |
| `usr/themes/initial/header.php` | 修改 | `<head>` 内注入 4 个 Schema | +45 行 |
| `usr/themes/initial/functions.php` | 修改 | `themeFields()` + `postSchema()` + `shortcode_faq()` + `pushIndexNow()` | +100 行 |
| `usr/themes/initial/post.php` | 修改 | 调用 `postSchema($this)`、引入 `faq-hidden.php` | +10 行 |
| `usr/themes/initial/component/faq-hidden.php` | **新建** | FAQ 隐形渲染组件 | ~35 行 |
| `sitemap.php` | **新建** | 根目录生成器 | ~70 行 |
| `feed.php` | **新建** | 根目录 JSON Feed 生成器 | ~50 行 |
| `indexnow-key.txt` | **新建** | 根目录 Key 文件 | 1 行 (32 字符) |
| 宝塔 Nginx 配置 | 修改 | 安全头 + `rewrite ^/sitemap.xml$ /sitemap.xml last;` | 6 行 |

---

## 5. 部署流程（SSH 部署模板）

```bash
# 1. 备份原文件
ssh root@47.83.168.225 "cp /www/wwwroot/janusbanana.com.cn/usr/themes/initial/functions.php /www/wwwroot/janusbanana.com.cn/usr/themes/initial/functions.php.bak.$(date +%Y%m%d)"
ssh root@47.83.168.225 "cp /www/wwwroot/janusbanana.com.cn/usr/themes/initial/header.php /www/wwwroot/janusbanana.com.cn/usr/themes/initial/header.php.bak.$(date +%Y%m%d)"
ssh root@47.83.168.225 "cp /www/wwwroot/janusbanana.com.cn/usr/themes/initial/post.php /www/wwwroot/janusbanana.com.cn/usr/themes/initial/post.php.bak.$(date +%Y%m%d)"

# 2. 上传新文件（示例：functions.php）
ssh root@47.83.168.225 "cat > /www/wwwroot/janusbanana.com.cn/usr/themes/initial/functions.php" << 'ENDOFFILE'
...完整文件内容...
ENDOFFILE

# 3. 语法检查
ssh root@47.83.168.225 "php -l /www/wwwroot/janusbanana.com.cn/usr/themes/initial/functions.php"

# 4. 验证首页不白屏
curl -sL -o /dev/null -w "HTTP:%{http_code}" https://janusbanana.com.cn/

# 5. 宝塔重载 Nginx（改配置后）
nginx -t && nginx -s reload
```

---

## 6. 风险点与回滚

| 风险 | 影响 | 回滚方案 |
|------|------|----------|
| `functions.php` 语法错误导致全站白屏 | P0 阻断 | `cp functions.php.bak functions.php` |
| Schema 字段缺失导致 Validator 报错 | SEO 影响 | 逐字段补全，优先保证 `Article` 核心字段 |
| 宝塔计划任务不执行 | Sitemap 过期 | 手动执行一次 `php sitemap.php > sitemap.xml`，检查日志 `/www/wwwlogs/cron.log` |
| IndexNow Key 泄露 | 低 | 重新生成 Key，更新 `indexnow-key.txt` 与推送代码 |
| AI 爬虫仍被拦截 | 索引延迟 | 检查 Nginx `limit_req`/`limit_conn`、宝塔 WAF 白名单 |

---

## 7. 后续运营建议

| 频次 | 动作 | 目的 |
|------|------|------|
| **每发文** | 填写 3 条 FAQ、检查 Schema 预览 | 保证结构化数据质量 |
| **每周** | 查看 Nginx 日志 AI Bot 抓取状态 | 及时发现封禁/异常 |
| **每月** | Google Rich Results Test 抽样 5 篇文章 | 监控 Schema 健康度 |
| **每季度** | 搜索品牌词/核心词在 DeepSeek/Kimi/文心中的引用情况 | 评估 GEO 效果 |
| **每半年** | 更新 `sameAs` 社交链接、补充 `knowsAbout` 新领域 | 实体知识图谱鲜活度 |

---

## 8. 附录：AI 爬虫 User-Agent 白名单完整版

```nginx
# robots.txt 片段
User-agent: GPTBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: DeepSeekBot
Allow: /

User-agent: KimiBot
Allow: /

User-agent: Bytespider
Allow: /

User-agent: YisouSpider
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: bingbot
Allow: /

User-agent: Googlebot
Allow: /

Sitemap: https://janusbanana.com.cn/sitemap.xml
```

---

> **下一步执行顺序**：  
> 1. 先在阿里云控制台重建安全组（**专有网络 VPC** + 入方向 22/80/443/8888）  
> 2. SSH 连通后，按 Phase 1 → 2 → 3 → 4 顺序落地  
> 3. 每阶段完成跑一次验收清单，确认通过再进下一阶段