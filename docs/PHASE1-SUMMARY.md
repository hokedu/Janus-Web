# Phase 1 实施总结：GEO 优化核心 Schema + 爬虫准入

> **完成时间**：2026-07-13  
> **目标**：提升 DeepSeek / Kimi / 豆包 / 文心一言 / GPTBot 等 AI 搜索引擎的引用率

---

## 一、实现的功能

### 1. robots.txt — AI 爬虫白名单
- 新增 9 个 AI 爬虫 User-Agent 白名单（GPTBot, ClaudeBot, DeepSeekBot, KimiBot, Bytespider, YisouSpider, PerplexityBot, bingbot, Googlebot）
- 添加 Sitemap 指向：`Sitemap: https://janusbanana.com.cn/sitemap.xml`

### 2. Schema.org 结构化数据（5 个 JSON-LD）
| Schema | 位置 | 内容 |
|--------|------|------|
| **WebSite** | header.php | 站点信息 + 搜索框 (`potentialAction`) + 发布方 Organization |
| **Organization** | header.php | Janus眼科小站实体：logo、知乎主页、联系方式 |
| **Person** | header.php | 作者实体：Janus，南方医科大学眼科博士，广东省人民医院眼科，知乎 @janus-85-60，专业领域 knowsAbout |
| **Article** | header.php | 文章结构化数据：标题、发布时间、作者、发布方、分类、标签、speakable（TTS友好） |
| **BreadcrumbList** | header.php | 面包屑导航结构（首页 → 分类 → 文章标题） |

### 3. 图片懒加载
- `functions.php` `postThumb()` 函数：图片输出增加 `loading="lazy"` 属性

### 4. IndexNow 即时推送
- 生成 128 位 Key 文件：`indexnow-key.txt`
- 发文时自动推送 Google/Bing/Naver/Yandex 等搜索引擎（`pushIndexNow()` 函数）
- 钩子注册在 `themeInit()` 中（避免提前加载 Plugin 框架导致致命错误）

### 5. Sitemap 静态生成器
- 独立 PHP 脚本：`sitemap.php`，支持 CLI + Web 双模式
- 输出格式：标准 XML `urlset` + Google 扩展 `image:image`
- 覆盖：首页、文章页、独立页面、分类页、标签页、归档页
- 优先级规则：首页 1.0 → 文章 0.8 → 分类 0.6 → 标签 0.5 → 归档 0.4

### 6. JSON Feed
- 独立 PHP 脚本：`feed.php`，符合 JSON Feed 1.1 标准
- 包含：作者信息、Gravatar 头像、全文内容、摘要、图片

### 7. FAQ 隐形问答字段（后台）
- `functions.php` `themeFields()` 新增 `faq_group` 自定义字段（右侧 Meta Box）
- 发文时填写 3 组问答，前端不可见（`display:none`），仅输出 FAQPage Schema
- 渲染组件：`component/faq-hidden.php`

### 8. 安全响应头
- `X-Content-Type-Options: nosniff`（禁止 MIME 类型嗅探）
- `X-Frame-Options: SAMEORIGIN`（防止点击劫持）

### 9. 定时任务
- 每天 03:00 自动执行：`php sitemap.php > sitemap.xml && php feed.php > feed.json`

---

## 二、遇到的错误与修复

### 错误 1：首页 HTTP 500 — PHP 致命错误
**现象**：部署后 `curl` 返回 `HTTP:500`  
**根因**：`Typecho_Plugin::factory(...)` 钩子注册写在 `functions.php` 末尾全局作用域中，主题的 `functions.php` 加载时 `Typecho_Plugin` 类尚未初始化，导致直接 fatal error  
**修复**：将钩子注册移到 `themeInit()` 函数内部，并检查 `class_exists('Typecho_Plugin')`

### 错误 2：nginx 未监听 443 端口
**现象**：`netstat -tlnp` 只显示 80 端口监听，443 缺失  
**根因**：服务器存在两个 nginx（系统 `/usr/sbin/nginx` + 宝塔 `/www/server/nginx/sbin/nginx`）。系统 nginx 缺少 Lua 等模块，无法加载宝塔 vhost 配置；宝塔 nginx 未被使用  
**修复**：`killall nginx` → `/www/server/nginx/sbin/nginx` 启动宝塔版

### 错误 3：宝塔 nginx 不加载 vhost 配置
**现象**：宝塔 nginx 启动后仍返回默认页面，443 无监听  
**根因**：`/etc/nginx/nginx.conf` 主配置缺少 `include /www/server/panel/vhost/nginx/*.conf`（系统 nginx 的配置文件 vs 宝塔 nginx 的配置文件路径不同）  
**修复**：发现宝塔 nginx 使用 `/www/server/nginx/conf/nginx.conf` 为主配置，已正确 include vhost 目录，只需切换 nginx 二进制即可

### 错误 4：nginx 配置语法错误 — `http2 on;` 不支持
**现象**：`nginx -t` 报 `unknown directive "http2"`  
**根因**：系统 nginx 版本不支持独立的 `http2 on;` 指令（只在 `listen` 中支持）  
**修复**：删除独立 `http2 on;`，将 `listen 443 ssl` 改为 `listen 443 ssl http2;`

### 错误 5：nginx 配置语法 — 嵌套 location 冲突
**现象**：`location "[^/]\.php(/|$)" cannot be inside the exact location "/sitemap.xml"`  
**根因**：在 `location = /sitemap.xml {}` 内 `include enable-php-82.conf` 与该文件中的 regex location 嵌套冲突  
**修复**：改为直接在 location 内写 `fastcgi_pass` + `fastcgi_param SCRIPT_FILENAME`

### 错误 6：feed.json 返回 404
**现象**：`curl https://janusbanana.com.cn/feed.json` 返回 404  
**根因**：Typecho 伪静态规则 `if (!-e $request_filename) { rewrite ^(.*)$ /index.php last; }` 在 `feed.json` 文件不存在时，将请求重写到 `index.php`，跳过了我们的 `location = /feed.json` 块  
**修复**：修改 `/www/server/panel/vhost/rewrite/janusbanana.com.cn.conf`，用 `$rewrite_skip` 变量排除 `/feed.json`、`/sitemap.xml`、`/indexnow-key.txt` 三个路径

### 错误 7：sitemap.php / feed.php 路径错误
**现象**：`require_once` 路径重复，报 `No such file or directory`  
**根因**：本地开发时的路径（`__DIR__ . '/janusbanana.com.cn'`）与服务器实际路径不符  
**修复**：服务器端统一使用 `require_once __DIR__ . '/config.inc.php'`

### 错误 8：feed.php 语法错误（heredoc 损坏）
**现象**：`PHP Parse error: unexpected double-quoted string`  
**根因**：SSH heredoc 中包含单引号 `'` 与 shell 转义冲突，导致文件内容损坏  
**修复**：重新用 heredoc 写入，使用单引号定界符 `'EOF'`，PHP 代码内置避免引号冲突

### 错误 9：header.php 被 heredoc 损坏导致页面乱码
**现象**：导航栏出现 `p class="site-title">` 和 `h1 class="site-title">` 裸文本  
**根因**：第一次部署 header.php 时 heredoc 内容被 shell 截断或转义，导致 HTML/PHP 标签丢失  
**修复**：从备份 `header.php.bak.20260713` 恢复原始文件，然后用 `sed` 在 `</head>` 前插入 Schema 代码（避免完整文件 heredoc 传输）

### 错误 10：BlogPosting Schema headline/url 为 null
**现象**：文章页出现第二个 BlogPosting Schema，字段值为 null  
**根因**：`post.php` 中 `postSchema($this)` 调用时 `$this` 上下文不对（在 `need('header.php')` 之后才调用，此时 archive 上下文不完整）  
**修复**：删除 `post.php` 中的 `postSchema($this)` 调用，Article Schema 统一由 `header.php` 输出

---

## 三、部署架构

```
┌─────────────────────────────────────────────────┐
│                  Nginx (宝塔)                      │
│  port 80 → 301 redirect → port 443                │
│  port 443 → PHP-FPM (unix:/tmp/php-cgi-82.sock)   │
│  add_header X-Content-Type-Options: nosniff       │
│  add_header X-Frame-Options: SAMEORIGIN           │
│  location /sitemap.xml → sitemap.php               │
│  location /feed.json → feed.php                    │
│  rewrite: 排除 feed/sitemap/indexnow               │
└─────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│                 Typecho CMS                       │
│  主题: Initial 2.5.5                             │
│  functions.php:                                    │
│    ├─ themeInit() → AjaxLoad=auto, pageSize=10    │
│    ├─ themeInit() → IndexNow 钩子注册             │
│    ├─ postThumb() → loading="lazy"               │
│    ├─ postSchema() → Article + FAQPage JSON-LD   │
│    ├─ themeFields() → faq_group 自定义字段        │
│    └─ pushIndexNow() → IndexNow API 推送          │
│  header.php:                                       │
│    ├─ WebSite + Organization + Person Schema      │
│    └─ Article + BreadcrumbList Schema             │
│  post.php:                                         │
│    └─ need('component/faq-hidden.php')             │
│  component/faq-hidden.php:                         │
│    └─ FAQPage JSON-LD + 隐形 DOM                   │
└─────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│                  定时任务 (cron)                    │
│  每天 03:00:                                       │
│    php sitemap.php > sitemap.xml                   │
│    php feed.php > feed.json                        │
└─────────────────────────────────────────────────┘
```

---

## 四、改动文件清单

| 文件 | 操作 | 说明 |
|------|:--:|------|
| `robots.txt` | 修改 | AI 爬虫白名单 + Sitemap 指向 |
| `usr/themes/initial/header.php` | 修改 | 插入 5 个 Schema JSON-LD |
| `usr/themes/initial/functions.php` | 修改 | postThumb lazy、themeFields FAQ、postSchema、pushIndexNow、主题钩子 |
| `usr/themes/initial/post.php` | 修改 | 引入 `component/faq-hidden.php` |
| `usr/themes/initial/component/faq-hidden.php` | **新建** | FAQ 隐形渲染组件 |
| `sitemap.php` (根目录) | **新建** | 静态 Sitemap 生成器 |
| `feed.php` (根目录) | **新建** | JSON Feed 1.1 生成器 |
| `indexnow-key.txt` (根目录) | **新建** | IndexNow 128 位 Key |
| `/www/server/panel/vhost/nginx/janusbanana.com.cn.conf` | 修改 | 安全头 + fastcgi location |
| `/www/server/panel/vhost/rewrite/janusbanana.com.cn.conf` | 修改 | 排除特定路径 |
| `/var/spool/cron/root` | 添加 | 每天 03:00 定时任务 |

---

## 五、验收结果

| 验收项 | 实际结果 |
|--------|:---:|
| 首页 HTTP 200 | ✅ |
| 文章页 HTTP 200 | ✅ |
| robots.txt AI 爬虫白名单 | ✅ |
| sitemap.xml 有效（xmllint 通过） | ✅ |
| feed.json JSON Feed | ✅ |
| X-Content-Type-Options: nosniff | ✅ |
| X-Frame-Options: SAMEORIGIN | ✅ |
| 图片懒加载 (loading="lazy") 5 处 | ✅ |
| Schema JSON-LD 5 个 | ✅ |
| FAQ 隐形字段（后台右侧 Meta Box） | ✅ |
| IndexNow Key + 推送钩子 | ✅ |
| 定时任务 (cron) | ✅ |
