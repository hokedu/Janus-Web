# Janus-Web 部署与运维指南

## 📋 目录
- [快速开始](#快速开始)
- [环境架构](#环境架构)
- [部署流程](#部署流程)
- [回滚操作](#回滚操作)
- [监控告警](#监控告警)
- [备份恢复](#备份恢复)
- [常见问题](#常见问题)

---

## 🚀 快速开始

### 前置要求
- GitHub 仓库: `hokedu/Janus-Web`
- 服务器: `47.83.168.225` (Ubuntu 22.04)
- 域名: `janusbanana.com.cn`

### 首次部署流程

```bash
# 1. 克隆仓库
git clone git@github.com:hokedu/Janus-Web.git
cd Janus-Web

# 2. 配置 SSH 密钥 (仅首次)
# 将 deploy key 添加到 GitHub Secrets: DEPLOY_SSH_KEY

# 2. 预发布环境部署
dep deploy staging

# 3. 验证预发布
curl -f https://staging.janusbanana.com.cn/

# 4. 生产部署
dep deploy production
```

---

## 🏗️ 环境架构

```
┌─────────────────────────────────────────────────────────────┐
│                      GitHub Actions                          │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐      │
│  │   Lint      │───▶│  Staging    │───▶│ Production  │      │
│  │  (PHP 8.2)  │    │  (develop)  │    │   (main)    │      │
│  └─────────────┘    └─────────────┘    └─────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    服务器架构 (47.83.168.225)                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐      │
│  │   Nginx     │───▶│  PHP-FPM    │───▶│   MySQL     │      │
│  │  (SSL/8888) │    │  (8.2)      │    │  (5.7)      │      │
│  └─────────────┘    └─────────────┘    └─────────────┘      │
│        │                                    │               │
│        ▼                                    ▼               │
│  ┌─────────────┐                    ┌─────────────┐        │
│  │  Redis      │                    │  备份存储    │        │
│  │  (可选)     │                    │  (OSS/MinIO) │        │
│  └─────────────┘                    └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

### 目录结构
```
/www/wwwroot/
├── janusbanana.com.cn/          # 生产环境 (current -> releases/xxx)
│   ├── current -> releases/20240115120000
│   ├── releases/
│   │   ├── 20240115120000/
│   │   ├── 20240114120000/
│   │   └── ...
│   └── shared/
│       ├── config.inc.php
│       └── usr/uploads/
│
├── staging.janusbanana.com.cn/  # 预发布环境
│   ├── current -> releases/xxx
│   ├── releases/
│   └── shared/
│       ├── config.inc.php
│       └── usr/uploads/
```

---

## 🚀 部署流程

### 自动部署 (GitHub Actions)
```yaml
# 自动触发条件
on:
  push:
    branches: [main]          # 生产环境
  push:
    branches: [develop]       # 预发布环境
  workflow_dispatch:          # 手动触发
```

**部署流程**:
```
1. 代码推送/手动触发
       ↓
2. 代码质量检查 (Lint)
       ↓
3. 部署到预发布环境
       ↓
4. 冒烟测试 (HTTP 200 + Schema 验证)
       ↓
4. 人工确认 (可选)
       ↓
5. 部署到生产环境
       ↓
6. 健康检查 + 关键页面验证
       ↓
6. 通知结果
```

---

## 📦 部署命令

### 本地开发机操作

```bash
# 安装 Deployer (本地)
composer global require deployer/deployer

# 或使用 Docker
docker run --rm -v $(pwd):/app deployer/deployer dep deploy staging

# 常用命令
dep deploy staging          # 部署预发布
dep deploy production       # 部署生产
dep rollback production     # 回滚生产
dep rollback:emergency production  # 紧急回滚
dep status                  # 查看部署状态
```

### GitHub Actions 手动触发
1. 进入 GitHub 仓库 → Actions
2. 选择 "Deploy to Production" workflow
3. 点击 "Run workflow"
6. 选择环境: `production` 或 `staging`
7. 点击 "Run workflow"

---

## 🔄 回滚操作

### 自动回滚 (GitHub Actions)
```bash
# GitHub Actions 界面手动触发
# 1. 进入 Actions → Emergency Rollback
# 2. 选择环境: production
# 3. (可选) 指定目标版本
# 3. 点击 Run workflow
```

### 手动回滚 (SSH)
```bash
# SSH 连接服务器
ssh root@47.83.168.225

# 查看可用版本
ls -lt /www/wwwroot/janusbanana.com.cn/releases/

# 紧急回滚到上一版本
dep rollback production

# 或指定版本回滚
dep rollback production 20240115120000

# 验证回滚
curl -f https://janusbanana.com.cn/
```

### 紧急回滚脚本
```bash
#!/bin/bash
# emergency-rollback.sh
set -e

ENV=${1:-production}
TARGET=${2:-}

echo "🚨 紧急回滚: $ENV"
if [ -n "$TARGET" ]; then
    dep rollback $ENV $TARGET
else
    dep rollback $ENV
fi

# 验证
sleep 3
if [ "$1" = "production" ]; then
    curl -f https://janusbanana.com.cn/ || exit 1
else
    curl -f https://staging.janusbanana.com.cn/ || exit 1
fi
echo "✅ 回滚完成"
```

---

## 📊 监控告警

### Uptime Kuma 监控配置
| 检查项 | 频率 | 告警阈值 | 通知渠道 |
|--------|------|----------|----------|
| 首页可用性 | 1 分钟 | HTTP != 200 | 钉钉/企微/邮件 |
| SSL 证书过期 | 1 天 | < 30 天 | 邮件 |
| 响应时间 | 5 分钟 | > 3 秒 | 钉钉 |
| SSL 评级 | 1 周 | < A | 邮件 |

### Grafana 仪表盘
- **请求量**: QPS, 延迟 P50/P95/P99
- **错误率**: 5xx 率, 4xx 率
- **资源**: CPU, 内存, 磁盘, 网络
- **PHP-FPM**: 进程数, 请求队列, 慢查询

### 告警规则示例
```yaml
groups:
  - name: janus-web
    rules:
      - alert: SiteDown
        expr: up{job="janus-web"} == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "网站宕机"
          
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 2m
        labels:
          severity: warning
        annotations:
          summary: "错误率过高"
```

---

## 💾 备份恢复

### 备份策略
| 数据类型 | 频率 | 保留 | 存储位置 |
|----------|------|------|----------|
| 数据库 | 每日 03:00 | 30 天 | 阿里云 OSS |
| 代码/配置 | 每次部署 | 90 天 | GitHub / OSS |
| 上传文件 | 增量/每日 | 90 天 | OSS |
| 完整镜像 | 每周 | 4 周 | EBS 快照 |

### 备份脚本
```bash
#!/bin/bash
# backup.sh - 每日 03:00 执行

set -e

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/www/backup"
OSS_BUCKET="oss://janus-backup"

# 1. 数据库备份
mysqldump -u janus -p${DB_PASS} janus_db | gzip > ${BACKUP_DIR}/db_${DATE}.sql.gz

# 2. 上传文件备份 (增量)
rsync -avz --delete /www/wwwroot/janusbanana.com.cn/usr/uploads/ ${BACKUP_DIR}/uploads/

# 3. 配置备份
tar -czf ${BACKUP_DIR}/config_${DATE}.tar.gz /www/wwwroot/janusbanana.com.cn/config.inc.php

# 4. 上传到 OSS
ossutil cp ${BACKUP_DIR}/* ${OSS_BUCKET}/${DATE}/ -r

# 5. 清理本地旧备份 (保留 7 天)
find ${BACKUP_DIR} -type f -mtime +7 -delete

echo "✅ 备份完成: ${DATE}"
```

### 恢复演练 (每月一次)
```bash
#!/bin/bash
# disaster-recovery-drill.sh

# 1. 从 OSS 下载最新备份
ossutil cp oss://janus-backup/latest/db_latest.sql.gz /tmp/
ossutil cp oss://janus-backup/latest/uploads/ /tmp/uploads/ -r

# 2. 在测试环境恢复
gunzip -c /tmp/db_latest.sql.gz | mysql -u test -p${TEST_DB_PASS} test_db
rsync -avz /tmp/uploads/ /www/wwwroot/test.janusbanana.com.cn/usr/uploads/

# 3. 验证数据完整性
curl -f https://test.janusbanana.com.cn/
mysql -u test -p${TEST_DB_PASS} test_db -e "SELECT COUNT(*) FROM contents;"

# 4. 记录演练结果
echo "$(date) - 恢复演练完成" >> /var/log/drill.log
```

---

## 🔧 常见问题

### Q: 部署失败 "Permission denied"
```bash
# 检查 SSH 密钥权限
chmod 600 ~/.ssh/id_ed25519

# 检查服务器目录权限
ssh root@server "ls -la /www/wwwroot/janusbanana.com.cn/"
```

### Q: 部署后页面 500
```bash
# 1. 查看 PHP 错误日志
tail -50 /www/wwwlogs/janusbanana.com.cn.error.log

# 2. 检查 PHP-FPM 状态
systemctl status php-fpm

# 3. 检查磁盘空间
df -h
```

### Q: SSL 证书过期
```bash
# 使用 acme.sh 自动续期
acme.sh --renew -d janusbanana.com.cn --force

# 重载 Nginx
nginx -s reload
```

### Q: 数据库连接失败
```bash
# 检查配置
cat /www/wwwroot/janusbanana.com.cn/config.inc.php

# 测试连接
mysql -h localhost -u janus -p
```

### Q: 部署卡住不动
```bash
# 查看部署日志
dep deploy production -vvv

# 强制解锁
dep deploy:unlock production
```

---

## 📞 紧急联系

| 角色 | 联系方式 | 响应时间 |
|------|----------|----------|
| 主开发 | 微信/电话 | 15 分钟 |
| 运维 | 钉钉群 | 10 分钟 |
| DBA | 电话 | 30 分钟 |

---

## 📝 变更记录

| 版本 | 日期 | 变更内容 | 操作人 |
|------|------|----------|--------|
| 1.0.0 | 2026-07-13 | 初始版本 | AI Assistant |
| 1.1.0 | 2026-07-13 | 添加回滚脚本 | AI Assistant |

---

> **提示**: 本文档需随项目演进持续更新。重大架构变更必须更新本文档。