# AI 开发流程

> 本文档定义与 AI 协作开发本项目的标准流程，确保每次改动安全、可回溯、可回滚。

---

## 一、核心原则

1. **生产环境只读**：不在服务器直接改代码，所有改动走 Git
2. **预发布先验证**：任何改动先在 staging 验证，确认无误再上线
3. **密码不入库**：`config.inc.php`、密钥、证书等通过 `.gitignore` 排除
4. **改前备份，改后可回滚**：每次部署保留旧版本，出问题立刻恢复

---

## 二、标准开发流程

### 流程图

```
┌──────────────┐
│ 1. 本地改代码  │
└──────┬───────┘
       │ git add / git commit
       ▼
┌──────────────┐
│ 2. git push   │ → GitHub (版本记录 + 备份)
│  GitHub       │
└──────┬───────┘
       │ SSH 登录服务器
       ▼
┌──────────────┐
│ 3. 部署预发布  │ → bash /root/deploy.sh staging
│  staging      │   → 健康检查 HTTP 200
└──────┬───────┘
       │ 浏览器验证
       ▼
┌──────────────┐
│ 4. 部署生产   │ → bash /root/deploy.sh production
│  production   │   → 健康检查 HTTP 200
└──────────────┘
```

### 详细步骤

#### 1. 改代码

```bash
# 在本地项目目录
vim usr/themes/initial/xxx.php  # 改代码
git add .                        # 暂存所有改动
git commit -m "feat: 描述改动"    # 提交
```

#### 2. 推送

```bash
git push origin master
```

#### 3. 部署到预发布

```bash
ssh root@47.83.168.225
bash /root/deploy.sh staging
```

#### 4. 验证预发布

```bash
# 命令行验证
curl -sL -o /dev/null -w "%{http_code}" http://staging.janusbanana.com.cn/
# 预期: 200

# 浏览器验证
# 打开 http://staging.janusbanana.com.cn/ 检查功能是否正常
```

#### 5. 部署到生产（验证通过后）

```bash
bash /root/deploy.sh production
```

---

## 三、deploy.sh 脚本说明

**位置**：服务器 `/root/deploy.sh`
**用法**：`bash /root/deploy.sh [staging|production]`

**内部流程**：
1. `git clone` 从 GitHub 拉最新代码
2. `rsync --delete` 同步到目标目录（排除 config/上传/缓存）
3. `php sitemap.php > sitemap.xml` 生成站点地图
4. `php feed.php > feed.json` 生成 JSON Feed
5. `chown -R www:www .` 修复文件权限
6. `curl` 健康检查

---

## 四、紧急回滚

```bash
# 如果生产部署后发现问题，恢复上一版本
ssh root@47.83.168.225
cd /www/wwwroot/janusbanana.com.cn
ls -t releases/           # 查看历史版本
ln -nfs releases/上一版本 current  # 回滚
```

---

## 五、安全准则

| 禁止 | 原因 |
|------|------|
| ❌ SSH 直接 vim 改生产文件 | 无记录、不可回溯 |
| ❌ 把 config.inc.php 提交到 Git | 密码泄露 |
| ❌ 跳过 staging 直接部署生产 | 无验证，高危 |
| ❌ 部署前不备份 | 无法回滚 |
| ✅ 每次改动走 Git → staging → 验证 → production | 安全 |

---

## 六、服务器信息

| 项目 | 值 |
|------|-----|
| SSH | `ssh root@47.83.168.225` |
| 生产目录 | `/www/wwwroot/janusbanana.com.cn/` |
| 预发布目录 | `/www/wwwroot/staging.janusbanana.com.cn/` |
| GitHub | https://github.com/hokedu/Janus-Web |
| 部署脚本 | `/root/deploy.sh` |
| 备份脚本 | `/root/backup.sh` (每天 02:00 自动) |
