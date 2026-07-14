#!/bin/bash
DATE=$(date +%Y%m%d_%H%M)
DEST=/www/backup
mkdir -p $DEST
cp /www/wwwroot/janusbanana.com.cn/usr/db.sqlite $DEST/db_$DATE.sqlite 2>/dev/null
cp /www/wwwroot/janusbanana.com.cn/config.inc.php $DEST/config_$DATE.php 2>/dev/null
find $DEST -mtime +30 -delete
echo "$(date): backup OK"