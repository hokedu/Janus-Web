/**
 * scroll-pages.js
 * 随滚动更新浏览器URL到对应分页，确保返回时位置正确
 * 仿 yabook.blog 方案：AJAX内容 + history.replaceState + 真分页URL
 */
(function () {
	var nav = document.querySelector('.page-navigator.ajaxload.auto');
	if (!nav) return;

	var POSTS_PER_PAGE = 15;
	var lastPage = 1;

	function pageUrl(n) {
		var base = location.pathname.replace(/\/page\/\d+\/$/, '').replace(/\/$/, '');
		if (n === 1) return base + '/';
		return base + '/page/' + n + '/';
	}

	function syncUrl() {
		var posts = document.querySelectorAll('#main > .post');
		if (!posts.length) return;

		// 以视口上方30%处所在的文章为准
		var threshold = window.scrollY + window.innerHeight * 0.3;
		var curr = 1;

		for (var i = posts.length - 1; i >= 0; i--) {
			if (posts[i].offsetTop <= threshold) {
				curr = Math.ceil((i + 1) / POSTS_PER_PAGE);
				break;
			}
		}

		if (curr !== lastPage) {
			lastPage = curr;
			history.replaceState({ page: curr }, document.title, pageUrl(curr));
		}
	}

	// 修复：bfcache失效时浏览器真请求/page/N/只返回15篇
	// pageshow.persisted=false 且文章数<=15 → 跳回首页
	window.addEventListener('pageshow', function (e) {
		if (e.persisted) return;

		var m = location.pathname.match(/\/page\/(\d+)\//);
		if (!m || parseInt(m[1]) <= 1) return;

		var postCount = document.querySelectorAll('#main > .post').length;
		if (postCount <= POSTS_PER_PAGE) {
			// 服务端直出的单页内容而非bfcache恢复，跳回首页
			var homeUrl = location.pathname.replace(/\/page\/\d+\/$/, '').replace(/\/$/, '') + '/';
			location.replace(homeUrl);
		}
	});

	// rAF节流，避免频繁触发
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

})();
