/**
 * Support Chat Widget
 *
 * @package PaidMemberSubscriptions
 */

(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        var widget = document.getElementById('pms-support-chat-widget');
        if (!widget || typeof pmsSupportChat === 'undefined') {
            return;
        }

        var config = pmsSupportChat;
        var strings = config.strings;

        var toggle = widget.querySelector('.pms-support-chat__toggle');
        var chatWindow = widget.querySelector('.pms-support-chat__window');
        var closeBtn = widget.querySelector('.pms-support-chat__close');
        var postsContainer = widget.querySelector('.pms-support-chat__posts');
        var loadingContainer = widget.querySelector('.pms-support-chat__loading');
        var sectionLabel = widget.querySelector('.pms-support-chat__section-label');
        var badge = widget.querySelector('.pms-support-chat__badge');

        widget.querySelector('.pms-support-chat__title').textContent = strings.title;
        widget.querySelector('.pms-support-chat__subtitle').textContent = strings.subtitle;
        widget.querySelector('.pms-support-chat__loading span').textContent = strings.loading;
        widget.querySelector('.pms-support-chat__encourage-title').textContent = strings.encourageTitle;
        widget.querySelector('.pms-support-chat__encourage-text').textContent = strings.encourageText;
        widget.querySelector('.pms-support-chat__tip-title').textContent = strings.tipTitle;
        widget.querySelector('.pms-support-chat__tip-text').textContent = strings.tipText;
        widget.querySelector('.pms-support-chat__btn--primary span').textContent = strings.askQuestion;
        widget.querySelector('.pms-support-chat__btn--secondary span').textContent = strings.viewAll;

        if (sectionLabel) {
            sectionLabel.textContent = strings.subtitle;
        }

        var isOpen = false;
        var postsLoaded = false;

        widget.style.display = 'block';

        var newCount = parseInt(config.newCount, 10) || 0;
        updateBadge(newCount);

        if (newCount > 0) {
            setTimeout(function() {
                toggle.classList.add('pms-support-chat__toggle--attention');
            }, 2000);
        }

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            isOpen = !isOpen;
            widget.classList.toggle('pms-support-chat--open', isOpen);
            toggle.classList.remove('pms-support-chat__toggle--attention');

            if (isOpen) {
                markPostsAsRead();
                updateBadge(0);

                if (!postsLoaded) {
                    loadPosts();
                }
            }
        });

        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            isOpen = false;
            widget.classList.remove('pms-support-chat--open');
        });

        document.addEventListener('click', function(e) {
            if (isOpen && !widget.contains(e.target)) {
                isOpen = false;
                widget.classList.remove('pms-support-chat--open');
            }
        });

        chatWindow.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                isOpen = false;
                widget.classList.remove('pms-support-chat--open');
                toggle.focus();
            }
        });

        function updateBadge(count) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
                badge.classList.add('pms-support-chat__badge--pulse');
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
                badge.classList.remove('pms-support-chat__badge--pulse');
            }
        }

        function markPostsAsRead() {
            var formData = new FormData();
            formData.append('action', 'pms_mark_forum_posts_read');
            formData.append('nonce', config.nonce);

            fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
        }

        function loadPosts() {
            var formData = new FormData();
            formData.append('action', 'pms_get_forum_posts');
            formData.append('nonce', config.nonce);

            fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.data.posts) {
                    renderPosts(data.data.posts);
                    postsLoaded = true;
                } else {
                    showError(data.data ? data.data.message : strings.error);
                }
            })
            .catch(function(error) {
                console.error('PMS Support Chat Error:', error);
                showError(strings.error);
            });
        }

        function renderPosts(posts) {
            posts = posts.filter(function(post) {
                return isHttpUrl(post.link);
            });

            if (!posts.length) {
                showError(strings.error);
                return;
            }

            postsContainer.textContent = '';
            posts.forEach(function(post) {
                var link = document.createElement('a');
                link.href = post.link;
                link.target = '_blank';
                link.rel = 'noopener';
                link.className = 'pms-support-chat__post';

                link.appendChild(createElement('h5', 'pms-support-chat__post-title', post.title));

                var meta = createElement('div', 'pms-support-chat__post-meta', '');
                if (post.author) {
                    meta.appendChild(createElement('span', 'pms-support-chat__post-author', strings.postedBy + ' ' + post.author));
                }
                if (post.date) {
                    meta.appendChild(createElement('span', 'pms-support-chat__post-date', post.date));
                }
                link.appendChild(meta);

                postsContainer.appendChild(link);
            });

            postsContainer.classList.add('pms-support-chat__posts--loaded');
            loadingContainer.classList.add('pms-support-chat__loading--hidden');
        }

        function showError(message) {
            loadingContainer.innerHTML = '<div class="pms-support-chat__error">' +
                '<div class="pms-support-chat__error-icon">!</div>' +
                '<p>' + escapeHtml(message) + '</p>' +
                '</div>';
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createElement(tagName, className, text) {
            var element = document.createElement(tagName);
            element.className = className;
            element.textContent = text;
            return element;
        }

        function isHttpUrl(url) {
            try {
                var protocol = new URL(url).protocol;
                return protocol === 'http:' || protocol === 'https:';
            } catch (e) {
                return false;
            }
        }
    }
})();
