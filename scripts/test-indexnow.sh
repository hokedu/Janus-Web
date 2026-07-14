#!/bin/bash
set -e

KEY=7a3f2b8c4d1e6f9a2b5c8d3e7f1a4b6c
echo "$KEY" > /www/wwwroot/janusbanana.com.cn/indexnow-key.txt

echo "Key: $KEY"
echo "Key file content: $(cat /www/wwwroot/janusbanana.com.cn/indexnow-key.txt)"

echo "--- Test 1: JSON POST ---"
RESP=$(curl -s -w "\n%{http_code}" -X POST 'https://api.indexnow.org/indexnow' \
  -H 'Content-Type: application/json; charset=utf-8' \
  -d '{"host":"janusbanana.com.cn","key":"'"$KEY"'","keyLocation":"https://janusbanana.com.cn/indexnow-key.txt","urlList":["https://janusbanana.com.cn/"]}')
echo "$RESP"

echo "--- Test 2: Bing endpoint ---"
curl -s -o /dev/null -w "HTTP %{http_code}" "https://www.bing.com/indexnow?url=https://janusbanana.com.cn/&key=$KEY"
echo

echo "--- Test 3: Validate JSON ---"
echo '{"host":"janusbanana.com.cn","key":"'"$KEY"'","keyLocation":"https://janusbanana.com.cn/indexnow-key.txt","urlList":["https://janusbanana.com.cn/"]}' | python3 -m json.tool