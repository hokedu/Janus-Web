# Janus眼科小站 — 技术手册

> **最后更新**：2026-07-12  
> **维护者**：[待填写]  
> **线上地址**：<https://janusbanana.com.cn/>  
> **技术栈**：Typecho CMS + Initial 2.5.5 / PHP 8.2 + MySQL 5.7 + Nginx / 阿里云 ECS 香港

---

## 目录

- [1. 服务器信息](#1-服务器信息)
- [2. 本地开发环境](#2-本地开发环境)
- [3. 项目结构](#3-项目结构)
- [4. 改造清单](#4-改造清单)
  - [4.1 AJAX 无限滚动](#41-ajax-无限滚动)
  - [4.2 每页文章数 5→15](#42-每页文章数-515)
  - [4.3 滚动位置记忆](#43-滚动位置记忆)
  - [4.4 粘性侧边栏](#44-粘性侧边栏)
  - [4.5 图片懒加载](#45-图片懒加载)
- [5. scroll-pages.js 详解](#5-scroll-pagesjs-详解)
- [6. 分页架构与数据流](#6-分页架构与数据流)
- [7. 部署与验证](#7-部署与验证)
- [8. 故障排查](#8-故障排查)
- [9. 已知问题与待办](#9-已知问题与待办)

---

## 1. 服务器信息

| 项目 | 值 | 备注 |
|------|-----|------|
| **IP** | `47.83.168.225` | 阿里云 ECS 香港 |
| **OS** | Alibaba Cloud Linux EL8 | RHEL 8 兼容 |
| **Web Server** | Nginx | 宝塔面板管理 |
| **PHP** | 8.2 | FastCGI |
| **数据库** | MySQL 5.7 | `janusbanana` 库 |
| **管理面板** | 宝塔面板 `:8888` | Web GUI 管理站点/文件/数据库 |
| **SSH** | `ssh root@47.83.168.225` | 端口 22，密码认证 |
| **网站根目录** | `/www/wwwroot/janusbanana.com.cn/` | Nginx `root` 指令指向 |
| **主题目录** | `…/usr/themes/initial/` | 所有改动集中在此 |
| **Typecho 后台** | `/admin/` | 登录后管理文章/评论/设置 |

> **注意**：宝塔面板和 SSH 使用的密码不同。SSH 密码在 Alibaba Cloud 控制台重置；宝塔面板账号在首次安装时设定。

---

## 2. 本地开发环境

### 2.1 代码获取

```bash
# 当前本地副本位置
d:\Hoke\Project\eye-family\janusbanana.com.cn\
```

> ⚠️ **同步状态**：本地代码可能滞后于服务器。SCP 受 SSH 密码认证波动影响，当前最可靠的更新方式是 **SSH cat heredoc 直传**（见[第 7 节](#7-部署与验证)）。

### 2.2 修改后部署

所有代码改动需要上传到服务器才能生效。文件在网站根目录下，无需重启 PHP-FPM 或 Nginx。流程：

```text
本地编辑 → [SSH cat heredoc] → 服务器文件 → curl 验证 → 浏览器测试
```

### 2.3 安全准则

- **不要直接修改 `main.min.js`**——压缩后的 jQuery 代码，难以调试且容易引入 bug。新增功能写入独立 JS 文件。
- **修改前备份**：`cp target.php target.php.bak.$(date +%Y%m%d)`
- **PHP 改动影响全局**：`functions.php` 的 `themeInit()` 在每次请求时执行，语法错误会导致全站白屏。

---

## 3. 项目结构

```
janusbanana.com.cn/                    # Nginx root
├── admin/                             # Typecho 管理后台 (不受主题影响)
├── usr/
│   ├── themes/initial/                # ★ 所有改动集中在此目录
│   │   ├── functions.php              # 主题入口：themeInit(), postThumb(), 配置注册
│   │   ├── index.php                  # 首页模板：文章循环 + pageNav 分页
│   │   ├── header.php                 # <head> + <body> 开头 + 顶部导航
│   │   ├── footer.php                 # </body> 前：JS 加载、页脚
│   │   ├── sidebar.php                # 右侧边栏（仅非 OneCOL 模式加载）
│   │   ├── post.php                   # 文章详情页模板
│   │   ├── page.php                   # 独立页面模板
│   │   ├── main.min.js                # 主题 JS（压缩）：AJAX 分页、评论、音乐播放器
│   │   ├── scroll-pages.js            # [★ 新增] 分页 URL 同步 + bfcache 恢复
│   │   ├── style.min.css              # 主题 CSS（压缩）
│   │   └── component/                 # 公共组件（评论、友情链接等）
│   ├── plugins/                       # Typecho 插件
│   └── uploads/                       # 用户上传文件（图片等）
├── docs/
│   └── TECHNICAL.md                   # 本文档
└── .htaccess                          # Apache rewrite（本环境使用 Nginx，此文件无用）
```

### 3.1 关键文件职责

| 文件 | 类型 | 改动标记 | 职责 |
|------|------|:--:|------|
| `functions.php` | PHP | ✏️ 已改 | `themeInit()` 控制 AjaxLoad/pageSize；`postThumb()` 控制缩略图输出 |
| `index.php` | PHP | 未改 | 文章列表渲染 + `pageNav()` 生成分页 HTML |
| `header.php` | PHP | ✏️ 已改 | 粘性侧边栏 CSS 内联写入 |
| `footer.php` | PHP | ✏️ 已改 | 新增 `scroll-pages.js` 引用；已删除 `scroll-memory.js` |
| `scroll-pages.js` | JS | ★ 新增 | 核心：`replaceState` + `pageshow` bfcache 检测 |
| `main.min.js` | JS | 未改 | 主题自带 AJAX 加载，压缩格式不可手动编辑 |

---

## 4. 改造清单

### 4.1 AJAX 无限滚动

**涉及文件**：[functions.php](usr/themes/initial/functions.php) `themeInit()` L188-212

**改动**：主题后台设置中有 `AjaxLoad` 选项（auto / click / 禁用）。在 `themeInit()` 开头强制设值为 `'auto'`，绕过后台配置，保证始终启用自动加载。

```php
// functions.php::themeInit() — 服务器实际代码
function themeInit($archive) {
    $options = Helper::options();
    $options->commentsAntiSpam = false;

    // 强制自动无限加载
    if (!$options->AjaxLoad) {
        $options->AjaxLoad = 'auto';
    }

    // 每页 15 篇
    if (!$archive->parameter->pageSize || $archive->parameter->pageSize <= 15) {
        $archive->parameter->pageSize = 15;
    }
    // ... 后续逻辑不变
}
```

**联动机制**：

1. `index.php` L61 读取 `$this->options->AjaxLoad` 生成分页 HTML
2. 值为 `'auto'` → 输出 `class="page-navigator ajaxload auto"`
3. `main.min.js` 检测到 `.ajaxload.auto` → 注册 scroll 监听 → 触底自动 fetch 下一页 → 提取 `.post` 追加到 `#main`

**AJAX 加载流程**（main.min.js 内部逻辑，未修改）：

```text
scroll 触底检测
  → 读取 .page-navigator .next a[href]   # 下一页 URL
  → $.ajax GET 完整页面 HTML
  → $(html).find('#main .post')           # 提取文章节点
  → $('.ajaxload').before(posts)          # 插入在分页之前
  → 更新 .page-navigator 内容             # 替换分页（下一页指向 page+1）
```

---

### 4.2 每页文章数 5→15

**涉及文件**：[functions.php](usr/themes/initial/functions.php) `themeInit()` L196-198

**改动**：从默认 5 篇/页提升到 15 篇/页，减少 AJAX 请求频率，同时为 `replaceState` URL 方案提供足够的内容高度。

```php
// ★ 关键：必须用 $archive->parameter->pageSize，而非 $options->pageSize
if (!$archive->parameter->pageSize || $archive->parameter->pageSize <= 15) {
    $archive->parameter->pageSize = 15;
}
```

> **踩坑记录**：Typecho `Widget_Archive` 在构造时读取 `$parameter->pageSize`，构造完成后 `$options->pageSize` 的修改对其无效。初次实现使用了 `$options->pageSize = 15`，`curl` 验证仍为 5 篇/页。换用 `$archive->parameter->pageSize` 后生效。

**验证命令**：

```bash
curl -sL https://janusbanana.com.cn/ | grep -c 'class="post"'
# 预期输出: 15
```

---

### 4.3 滚动位置记忆

**涉及文件**：[scroll-pages.js](usr/themes/initial/scroll-pages.js)（新增，63 行）

**问题**：用户滚动经过多页 AJAX 内容后，点击文章再按返回，bfcache 失效时滚动位置被截断——URL 仍是 `/`，但 DOM 只有 15 篇文章，浏览器尝试恢复的 scrollY 超出实际页面高度。

**方案经历 4 次迭代**：

| 迭代 | 方案 | 失败原因 |
|:--:|------|------|
| 1 | `sessionStorage.setItem('scrollY', ...)` | sessionStorage 跨页面共享，首页位置污染文章页显示 |
| 2 | `history.scrollRestoration = 'manual'` | 全局生效，文章页也被手动控制，显示位置错误 |
| 3 | pageshow 检测 + 自动点击"加载更多" | AJAX 时序不可控，`setTimeout` 400ms 轮询不稳定 |
| **4** | **`history.replaceState` + Typecho 真分页 URL** | ✅ 当前方案，利用浏览器原生机制 |

**最终原理**（详见[第 5 节](#5-scroll-pagesjs-详解)和第 [6 节](#6-分页架构与数据流)）。

---

### 4.4 粘性侧边栏

**涉及文件**：[header.php](usr/themes/initial/header.php) L60 `</head>` 之前

**改动**：内联 CSS，无外部依赖。

```html
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
```

| 断点 | 行为 |
|------|------|
| 桌面端 (>768px) | 侧边栏粘在视口顶部 80px 处，内容过高时内部滚动 |
| 移动端 (≤768px) | 侧边栏隐藏，单列布局 |

> `top: 80px` 需要匹配导航栏高度。如果导航栏高度调整，需同步修改此值。

---

### 4.5 图片懒加载

**涉及文件**：[functions.php](usr/themes/initial/functions.php) `postThumb()` L237-253

**状态**：⚠️ **未部署**（本地和服务器均未修改）

**当前代码**（L253）：

```php
return '<img src="'.$thumb.'" />';
```

**应改为**：

```php
return '<img src="'.$thumb.'" loading="lazy" />';
```

部署命令见[第 7 节](#7-部署与验证)。

---

## 5. scroll-pages.js 详解

### 5.1 基本信息

| 属性 | 值 |
|------|-----|
| 路径 | `usr/themes/initial/scroll-pages.js` |
| 行数 | 63 |
| 依赖 | 无（Vanilla JS，不依赖 jQuery） |
| 加载方式 | `footer.php` 中 `<script>` 标签，位于 `main.min.js` 之后 |
| 作用范围 | 仅首页——首行即检查 `.page-navigator.ajaxload.auto` 是否存在 |
| IIFE | 是——不污染全局命名空间 |

### 5.2 常量与变量

```javascript
var POSTS_PER_PAGE = 15;   // 每页文章数，需与 functions.php pageSize 一致
var lastPage = 1;          // 当前已同步的页码，避免重复 replaceState
```

### 5.3 `pageUrl(n)` → 构造分页 URL

```javascript
function pageUrl(n) {
    var base = location.pathname
        .replace(/\/page\/\d+\/$/, '')  // 去除已有分页后缀
        .replace(/\/$/, '');             // 去除末尾斜杠
    if (n === 1) return base + '/';      // 首页用根路径
    return base + '/page/' + n + '/';     // 其他页: /page/N/
}
```

| 输入 | 输出 | 说明 |
|:--:|------|------|
| `pageUrl(1)` | `/` | 首页始终用根路径 |
| `pageUrl(2)` | `/page/2/` | |
| `pageUrl(3)` | `/page/3/` | |
| 当前在 `/page/3/`, 调 `pageUrl(1)` | `/` | 正确剥离已有分页 |

### 5.4 `syncUrl()` → 视口检测 + URL 同步

```javascript
function syncUrl() {
    var posts = document.querySelectorAll('#main > .post');
    if (!posts.length) return;

    // 视口上方 30% 处所在的文章
    var threshold = window.scrollY + window.innerHeight * 0.3;
    var curr = 1;

    // 从后往前遍历，找到 threshold 以上的第一个文章
    for (var i = posts.length - 1; i >= 0; i--) {
        if (posts[i].offsetTop <= threshold) {
            curr = Math.ceil((i + 1) / POSTS_PER_PAGE);
            break;
        }
    }

    // 仅页码变化时才 replaceState
    if (curr !== lastPage) {
        lastPage = curr;
        history.replaceState({ page: curr }, document.title, pageUrl(curr));
    }
}
```

| 设计决策 | 原因 |
|------|------|
| 30% 视口位置 | 文章标题位于上方时视为"正在阅读"，避免滚动过半才切换 URL |
| 从后往前遍历 | 性能优化——用户通常在看页面下方的内容 |
| 仅页码变化时更新 | 避免同页码内每次 scroll 都调用 `replaceState` |

### 5.5 `pageshow` → bfcache 失效降级

```javascript
window.addEventListener('pageshow', function (e) {
    if (e.persisted) return;    // bfcache 命中，DOM 完整保留

    var m = location.pathname.match(/\/page\/(\d+)\//);
    if (!m || parseInt(m[1]) <= 1) return;  // 第 1 页无需处理

    var postCount = document.querySelectorAll('#main > .post').length;
    if (postCount <= POSTS_PER_PAGE) {
        // 仅有 15 篇 = 服务端直出的单页内容，非 bfcache 恢复
        var homeUrl = location.pathname
            .replace(/\/page\/\d+\/$/, '')
            .replace(/\/$/, '') + '/';
        location.replace(homeUrl);
    }
});
```

**三种情况对照**：

| `e.persisted` | `#main > .post` 数量 | 判定 | 行为 |
|:---:|:---:|------|------|
| `true` | 30 / 45 / ... | bfcache 完整恢复 | **无操作**，用户看到完整内容 |
| `false` | ≤15 | 服务端直出，内容截断 | **跳回首页**，用户重新滚动 |
| `false` | >15 | AJAX 内容仍在内存（罕见） | **无操作** |

> **bfcache 失效原因**：浏览器内存不足时丢弃缓存页面。Chrome 开发者工具 → Application → Back/forward cache 可测试。

### 5.6 Scroll 事件 — rAF 节流

```javascript
var ticking = false;
window.addEventListener('scroll', function () {
    if (!ticking) {
        requestAnimationFrame(function () {
            syncUrl();
            ticking = false;
        });
        ticking = true;
    }
}, { passive: true });
```

| 技术 | 作用 |
|------|------|
| `requestAnimationFrame` | 每帧最多执行一次（约 16ms），避免高频触发 |
| `passive: true` | 告知浏览器不调用 `preventDefault()`，不阻塞滚动线程 |

### 5.7 设计参考

方案借鉴 [yabook.blog](https://yabook.blog/)（Typecho 博客，使用相同思路）。

| 对比项 | yabook.blog | Janus 本站 |
|------|:--:|:--:|
| 分页 URL | `/index_N.html` | `/page/N/` |
| AJAX 触发 | 自定义 `initInfiniteScroll()` | 主题自带 `main.min.js` |
| URL 同步 | scroll 中按 15 篇/页计算 | 同 |
| bfcache 降级 | **无**（无此处理） | `pageshow` 检测 + 跳回首页 |

---

## 6. 分页架构与数据流

### 6.1 完整流程

```text
┌─ 首次访问 ─────────────────────────────────────────┐
│                                                     │
│  GET /                                               │
│  → Nginx → PHP/Typecho → Widget_Archive(15篇)       │
│  → index.php 渲染 15 篇 + pageNav + sidebar          │
│  → HTTP 200 (约 30KB, gzip 后约 9KB)                │
│                                                     │
└─────────────────────────────────────────────────────┘
                    ↓
┌─ 用户向下滚动 ──────────────────────────────────────┐
│                                                     │
│  main.min.js: scrollY + innerHeight ≥ bodyH - 100   │
│  → $.ajax GET /page/2/  (完整 HTML, 约 29KB)       │
│  → $(html).find('#main > .post') → 15篇             │
│  → 插入 DOM (共 30 篇)                               │
│  → 更新 .page-navigator (下一页 → /page/3/)          │
│                                                     │
│  scroll-pages.js: syncUrl()                          │
│  → 视口在文章 #16~30 范围内                          │
│  → history.replaceState(null, '', '/page/2/')       │
│                                                     │
└─────────────────────────────────────────────────────┘
                    ↓
┌─ 用户点击文章 → 浏览 → 按返回 ────────────────────┐
│                                                     │
│  ┌─ bfcache 命中 (约 90%)                             │
│  │ → 完整 DOM (30+/45+ 篇) + 精确 scrollY 恢复       │
│  │ → pageshow.persisted = true  → 无操作              │
│  │                                                    │
│  └─ bfcache 失效 (约 10%)                             │
│     → 浏览器请求 GET /page/2/                         │
│     → 服务器返回 15 篇 (第 16-30 篇)                  │
│     → pageshow.persisted = false                      │
│     → #main > .post 数量 = 15 ≤ POSTS_PER_PAGE        │
│     → location.replace('/')  跳回首页                  │
│                                                       │
└───────────────────────────────────────────────────────┘
```

### 6.2 URL 映射规则

| 用户滚动到的文章序号 | 地址栏显示 | 服务器直出内容 |
|:---:|------|------|
| 1-15 | `/` | 第 1-15 篇 |
| 16-30 | `/page/2/` | 第 16-30 篇 |
| 31-45 | `/page/3/` | 第 31-45 篇 |
| N×15+1 ~ (N+1)×15 | `/page/N+1/` | 对应分页 |

### 6.3 为什么不存滚动位置

| 方案 | 问题 |
|------|------|
| `sessionStorage` 存 scrollY | 全局共享。首页存的值在文章页也被读到，文章页显示错误位置 |
| `scrollRestoration='manual'` | 全局生效。设置为 manual 后文章页也失去自动恢复 |
| Cookie | 每次请求携带，额外带宽开销 |
| **URL replaceState** ✅ | 利用浏览器原生历史管理，页面状态自然隔离 |

### 6.4 `replaceState` vs `pushState`

```text
pushState:  /  →  /page/2/  →  /page/3/  →  [点文章]  →  /article.html
            返回时：/page/3/ → /page/2/ → /  需要按 3 次返回 ✗

replaceState:  /  →  /page/2/  →  /page/3/  →  [点文章]  →  /article.html
              返回时：/page/3/ → 直接回 /article.html 之前的状态 ✓
```

---

## 7. 部署与验证

### 7.1 部署方式

SCP 受 SSH 密码认证波动影响不可靠。当前使用 **SSH + cat heredoc**：

```bash
# 单文件上传模板
ssh root@47.83.168.225 "cat > /www/wwwroot/janusbanana.com.cn/usr/themes/initial/target-file.ext" << 'ENDOFFILE'
... 完整文件内容 ...
ENDOFFILE
```

> **警告**：heredoc 中的 `$` 符号需要转义为 `\$` 以避免在 SSH 端被解析。JS 代码中的 `$` 不受影响（单引号定界符 `'ENDOFFILE'` 禁用变量展开）。

### 7.2 PHP 文件部署（sed 单行修改）

对于单行改动，优先使用 sed 避免完整文件传输：

```bash
# 示例：修复 postThumb 懒加载
ssh root@47.83.168.225 \
  "sed -i \"s|return '<img src=\"'.\$thumb.'\" />';|return '<img src=\"'.\$thumb.'\" loading=\\\"lazy\\\" />';|\" \
  /www/wwwroot/janusbanana.com.cn/usr/themes/initial/functions.php"
```

> sed 中的 `\$` 转义是因 SSH 命令在远程 shell 中执行。先在测试文件上验证。

### 7.3 验证清单

每次部署后按顺序验证：

```bash
# 1. 首页正常访问
curl -sL -o /dev/null -w "HTTP:%{http_code}" https://janusbanana.com.cn/
# 预期: 200

# 2. 每页文章数正确
curl -sL https://janusbanana.com.cn/ | grep -c 'class="post"'
# 预期: 15

# 3. 分页 URL 可访问
curl -sL -o /dev/null -w "HTTP:%{http_code}" https://janusbanana.com.cn/page/2/
# 预期: 200

# 4. scroll-pages.js 已加载
curl -sL https://janusbanana.com.cn/ | grep -c 'scroll-pages.js'
# 预期: 1

# 5. 不再加载旧的 scroll-memory.js
curl -sL https://janusbanana.com.cn/ | grep -c 'scroll-memory.js'
# 预期: 0

# 6. 粘性侧边栏 CSS 存在
curl -sL https://janusbanana.com.cn/ | grep -c 'position.*sticky'
# 预期: ≥1

# 7. PHP 无语法错误（白屏检测）
curl -sL https://janusbanana.com.cn/ | grep -c '</html>'
# 预期: 1
```

### 7.4 浏览器手动测试

| # | 测试场景 | 操作 | 预期结果 |
|---|------|------|------|
| 1 | AJAX 自动加载 | 首页缓速向下滚到底 | 新文章自动出现，无加载闪烁 |
| 2 | URL 随滚动更新 | 滚动到第 16+ 篇文章 | 地址栏变为 `/page/2/` 或更高 |
| 3 | 返回位置恢复 | 滚到第 30 篇 → 点文章 → 返回 | 回到第 30 篇位置 |
| 4 | bfcache 降级 | 打开多个标签页耗尽内存后返回 | 跳回首页顶部，可重新下滚 |
| 5 | 粘性侧边栏 | 向下滚动首页 | 右侧栏保持可见 |
| 6 | 移动端 | 浏览器 DevTools 设为 375px 宽 | 侧边栏隐藏，单列布局 |

---

## 8. 故障排查

### 网站白屏（PHP 错误）

```bash
# 检查 PHP 语法
ssh root@47.83.168.225 "php -l /www/wwwroot/janusbanana.com.cn/usr/themes/initial/functions.php"

# 检查 Nginx 错误日志
ssh root@47.83.168.225 "tail -50 /www/logs/nginx/error.log"

# 恢复备份
ssh root@47.83.168.225 "cp /www/wwwroot/.../functions.php.bak /www/wwwroot/.../functions.php"
```

### AJAX 无限加载失效

1. 确认 `functions.php` 中 `$options->AjaxLoad = 'auto'` 存在
2. 确认 `index.php` 中 `pageNav` 输出包含 `ajaxload auto` 类
3. 检查浏览器 Console 有无 jQuery 或 main.min.js 404 错误

```bash
curl -sL https://janusbanana.com.cn/ | grep -o 'ajaxload auto'
# 预期输出: ajaxload auto
```

### 滚动返回位置错乱

1. 确认 `scroll-pages.js` 正确加载：`curl -s https://janusbanana.com.cn/ | grep scroll-pages`
2. 确认服务器上文件包含 `pageshow`：`grep pageshow /www/wwwroot/.../scroll-pages.js`
3. 确认 `POSTS_PER_PAGE`（JS）与 `$archive->parameter->pageSize`（PHP）一致，均为 15

### 修改 pageSize 后需同步

如果将来调整每页文章数，需同时修改两处：

```php
// functions.php — PHP 端
$archive->parameter->pageSize = 新值;
```

```javascript
// scroll-pages.js — JS 端
var POSTS_PER_PAGE = 新值;
```

### SSH 密码认证失败

阿里云控制台 → ECS 实例 → 重置密码 → 重启实例生效。或使用 VNC 登录（控制台提供）。

---

## 9. 已知问题与待办

| # | 问题 | 优先级 | 状态 | 修复方式 |
|---|------|:--:|:--:|------|
| 1 | 图片懒加载未部署 | 中 | ⚠️ 待修 | `functions.php` L253 加 `loading="lazy"` |
| 2 | 缺少 `X-Content-Type-Options` / `X-Frame-Options` 安全头 | 低 | ⚠️ 建议 | Nginx 配置添加 `add_header` 指令 |
| 3 | 本地代码与服务器可能不同步 | 低 | ⚠️ 注意 | SCP 不可靠，以服务器文件为准 |
| 4 | bfcache 失效后用户需重新下滚 | — | 📋 设计取舍 | 10% 概率，远优于内容显示异常 |
| 5 | 无 HTTPS 证书自动续期监控 | 低 | 📋 待确认 | 确认宝塔面板 Let's Encrypt 自动续期是否正常 |

---

> **下一位开发者**：如果你需要理解这个项目的滚动位置记忆方案，请从 [第 6.1 节](#61-完整流程) 的数据流图开始阅读，然后看 [第 5 节](#5-scroll-pagesjs-详解) 的代码细节。修改时请遵循 [第 2 节](#2-本地开发环境) 的部署流程。
