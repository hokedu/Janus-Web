#!/bin/bash
set -euo pipefail
# Janus-Web 部署脚本
# 用法: bash /root/deploy.sh staging  或  bash /root/deploy.sh production

ENV=${1:-staging}

if [ "$ENV" = "production" ]; then
  TARGET=/www/wwwroot/janusbanana.com.cn
  DOMAIN=janusbanana.com.cn
else
  TARGET=/www/wwwroot/staging.janusbanana.com.cn
  DOMAIN=staging.janusbanana.com.cn
fi

echo "=== 从 GitHub 拉取代码 ==="
rm -rf /tmp/Janus-Web
git clone --depth 1 https://github.com/hokedu/Janus-Web.git /tmp/Janus-Web || {
  echo "FATAL: git clone 失败"
  exit 1
}
cd /tmp/Janus-Web

echo "=== 同步到 $ENV ==="
rsync -avz --delete \
  --exclude '.git' --exclude '.github' --exclude 'config.inc.php' \
  --exclude 'usr/uploads' --exclude 'var/cache' \
  --exclude '*.log' --exclude '*.bak' --exclude 'deploy.php' \
  ./ "$TARGET/"

echo "=== 生成 sitemap + feed ==="
cd "$TARGET"
php sitemap.php > sitemap.xml 2>/dev/null || echo "WARN: sitemap 生成失败"
php feed.php > feed.json 2>/dev/null || echo "WARN: feed 生成失败"
chown -R www:www . 2>/dev/null || true

echo "=== 健康检查 ==="
CODE=$(curl -sL -o /dev/null -w "%{http_code}" "http://$DOMAIN/")
echo "$DOMAIN: HTTP $CODE"
if [ "$CODE" != "200" ]; then
  echo "FATAL: 健康检查失败"
  exit 1
fi
echo "=== 部署完成 ==="