<?php
/**
 * Deployer 配置文件
 * 用于 Typecho 博客自动化部署
 * 
 * 用法:
 *   dep deploy staging    # 部署到预发布环境
 *   dep deploy production # 部署到生产环境
 *   dep rollback production # 回滚生产环境
 */

namespace Deployer;

require 'recipe/common.php';

// ========== 基础配置 ==========
set('application', 'janus-web');
set('repository', 'git@github.com:hokedu/Janus-Web.git');
set('git_tty', true);
set('git_recursive', true);
set('ssh_multiplexing', true);

// ========== 共享目录/文件 ==========
set('shared_dirs', [
    'usr/uploads',
    'var/cache',
    'var/log',
]);

set('shared_files', [
    'config.inc.php',
]);

// ========== 权限设置 ==========
set('writable_dirs', [
    'usr/uploads',
    'var/cache',
    'var/log',
]);

set('writable_use_sudo', false);
set('writable_mode', '0755');
set('writable_chmod_dirs', true);
set('writable_chmod_files', true);

// ========== 部署钩子 ==========
// 部署前：检查环境
task('deploy:check_environment', function () {
    $phpVersion = run('php -v | head -1');
    writeln("<info>PHP 版本: {$phpVersion}</info>");
    
    $diskFree = run('df -h / | tail -1');
    writeln("<info>磁盘空间: {$diskFree}</info>");
    
    $memFree = run('free -h | grep Mem');
    writeln("<info>内存: {$memFree}</info>");
})->desc('检查服务器环境');

// 部署前：清理缓存
task('deploy:clear_cache', function () {
    if (test('[ -d {{release_path}}/var/cache ]')) {
        run('rm -rf {{release_path}}/var/cache/*');
        writeln('<info>缓存已清理</info>');
    }
})->desc('清理缓存');

// 部署后：生成静态文件
task('deploy:generate_static', function () {
    cd('{{release_path}}');
    
    // 生成 sitemap.xml
    if (test('[ -f sitemap.php ]')) {
        run('php sitemap.php > sitemap.xml');
        writeln('<info>sitemap.xml 已生成</info>');
    }
    
    // 生成 feed.json
    if (test('[ -f feed.php ]')) {
        run('php feed.php > feed.json');
        writeln('<info>feed.json 已生成</info>');
    }
})->desc('生成静态文件');

// 部署后：修复权限
task('deploy:fix_permissions', function () {
    $path = '{{release_path}}';
    run("chown -R www:www {$path}/usr/uploads");
    run("chown -R www:www {$path}/var/cache");
    run("chown -R www:www {$path}/var/log");
    run("chmod -R 755 {$path}/usr/uploads");
    run("chmod -R 755 {$path}/var/cache");
    run("chmod -R 755 {$path}/var/log");
    writeln('<info>权限已修复</info>');
})->desc('修复文件权限');

// 部署后：健康检查
task('deploy:health_check', function () {
    $domain = get('health_check_domain');
    if ($domain) {
        $result = run("curl -s -o /dev/null -w '%{http_code}' -H 'Host: {$domain}' http://127.0.0.1/ 2>/dev/null || echo '000'");
        if ($result === '200') {
            writeln("<info>健康检查通过: {$domain}</info>");
        } else {
            writeln("<error>健康检查失败: HTTP {$result}</error>");
            exit(1);
        }
    }
})->desc('健康检查');

// 部署后：清理旧版本 (保留最近 5 个)
task('deploy:cleanup_releases', function () {
    run('cd {{deploy_path}} && ls -dt releases/* | tail -n +6 | xargs rm -rf 2>/dev/null || true');
    writeln('<info>旧版本已清理 (保留最近 5 个)</info>');
})->desc('清理旧版本');

// ========== 环境定义 ==========

// 预发布环境
host('staging')
    ->hostname('47.83.168.225')
    ->user('root')
    ->port(22)
    ->identityFile()
    ->set('deploy_path', '/www/wwwroot/staging.janusbanana.com.cn')
    ->set('branch', 'develop')
    ->set('health_check_domain', 'staging.janusbanana.com.cn')
    ->set('keep_releases', 3);

// 生产环境
host('production')
    ->hostname('47.83.168.225')
    ->user('root')
    ->port(22)
    ->identityFile()
    ->set('deploy_path', '/www/wwwroot/janusbanana.com.cn')
    ->set('branch', 'main')
    ->set('health_check_domain', 'janusbanana.com.cn')
    ->set('keep_releases', 5);

// ========== 部署流程 ==========
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:clear_cache',
    'deploy:generate_static',
    'deploy:fix_permissions',
    'deploy:health_check',
    'deploy:cleanup_releases',
    'deploy:unlock',
    'deploy:success',
]);

// 回滚后清理
after('rollback:finish', 'deploy:fix_permissions');
after('rollback:finish', 'deploy:health_check');

// 成功通知
task('deploy:success', function () {
    writeln('<info>部署成功完成！</info>');
    writeln('<info>网站地址: https://' . get('health_check_domain') . '</info>');
})->desc('部署成功通知');

// ========== 自定义任务 ==========

// 快速部署（跳过某些检查）
task('deploy:fast', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:fix_permissions',
    'deploy:health_check',
    'deploy:success',
])->desc('快速部署（跳过缓存清理等）');

// 紧急回滚
task('rollback:emergency', function () {
    $releases = run('ls -dt {{deploy_path}}/releases/* | head -2 | tail -1');
    $previousRelease = trim($releases);
    
    if ($previousRelease) {
        run("ln -nfs {$previousRelease} {{deploy_path}}/current");
        writeln("<info>已回滚到: {$previousRelease}</info>");
        invoke('deploy:fix_permissions');
        invoke('deploy:health_check');
    } else {
        writeln('<error>没有可回滚的版本</error>');
        exit(1);
    }
})->desc('紧急回滚到上一个版本');

// 查看部署状态
task('deploy:status', function () {
    writeln('<info>=== 部署状态 ===</info>');
    writeln("当前版本: " . run('readlink -f {{deploy_path}}/current'));
    writeln("可用版本:");
    run('ls -lt {{deploy_path}}/releases/ | head -10');
    writeln("");
    writeln("进程状态:");
    run('ps aux | grep -E "php|nginx" | grep -v grep');
})->desc('查看部署状态');

// 清理缓存
task('cache:clear', function () {
    if (test('[ -d {{release_path}}/var/cache ]')) {
        run('rm -rf {{release_path}}/var/cache/*');
        writeln('<info>缓存已清理</info>');
    }
})->desc('清理缓存');

// 重新生成静态文件
task('static:generate', function () {
    invoke('deploy:generate_static');
    writeln('<info>静态文件已重新生成</info>');
})->desc('重新生成静态文件');

// 紧急回滚
task('rollback:emergency', function () {
    $releases = run('ls -dt {{deploy_path}}/releases/* | head -2 | tail -1');
    $previousRelease = trim($releases);
    
    if ($previousRelease) {
        run("ln -nfs {$previousRelease} {{deploy_path}}/current");
        writeln("<info>已回滚到: {$previousRelease}</info>");
        invoke('deploy:fix_permissions');
        invoke('deploy:health_check');
    } else {
        writeln('<error>没有可回滚的版本</error>');
        exit(1);
    }
})->desc('紧急回滚到上一个版本');