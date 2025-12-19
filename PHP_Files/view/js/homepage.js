/**
 * ReConnect Homepage JavaScript
 * Handles likes, comments, and dynamic post loading
 */

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// ============================================
// LIKE FUNCTIONALITY
// ============================================

document.addEventListener('click', async function(e) {
    const likeBtn = e.target.closest('.like-btn');
    if (!likeBtn) return;

    const postId = likeBtn.dataset.postId;
    const icons = likeBtn.querySelectorAll('i.fa-heart');
    const countSpans = document.querySelectorAll(`.like-count`);

    try {
        const response = await fetch('../actions/post_like_action.php?action=toggle', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `post_id=${postId}`
        });

        const data = await response.json();

        if (data.status === 'success') {
            // Update all like buttons for this post
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            const likeCounts = postElement.querySelectorAll('.like-count');
            const likeIcons = postElement.querySelectorAll('.like-btn i.fa-heart');
            const likeTexts = postElement.querySelectorAll('.like-btn h6');

            likeCounts.forEach(count => count.textContent = data.like_count);

            if (data.action === 'liked') {
                likeIcons.forEach(icon => {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = '#e74c3c';
                });
                likeTexts.forEach(text => text.textContent = 'Liked');
            } else {
                likeIcons.forEach(icon => {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '#6c757d';
                });
                likeTexts.forEach(text => text.textContent = 'Like');
            }
        } else {
            alert(data.message || 'Failed to update like');
        }
    } catch (error) {
        console.error('Error toggling like:', error);
        alert('An error occurred while updating like');
    }
});

// ============================================
// COMMENT TOGGLE FUNCTIONALITY
// ============================================

document.addEventListener('click', function(e) {
    const toggleBtn = e.target.closest('.comment-toggle-btn');
    if (!toggleBtn) return;

    const postId = toggleBtn.dataset.postId;
    const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);

    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        loadComments(postId);
    } else {
        commentsSection.style.display = 'none';
    }
});

// ============================================
// COMMENT SUBMISSION
// ============================================

document.addEventListener('click', async function(e) {
    const submitBtn = e.target.closest('.submit-comment-btn');
    if (!submitBtn) return;

    const postId = submitBtn.dataset.postId;
    const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
    const input = commentsSection.querySelector('.comment-input');
    const comment = input.value.trim();

    if (!comment) {
        alert('Please enter a comment');
        return;
    }

    submitBtn.disabled = true;

    try {
        const response = await fetch('../actions/post_comment_action.php?action=create', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
        });

        const data = await response.json();

        if (data.status === 'success') {
            input.value = '';
            await loadComments(postId);

            // Update comment count
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            const commentCounts = postElement.querySelectorAll('.comment-count');
            commentCounts.forEach(count => {
                const currentCount = parseInt(count.textContent) || 0;
                count.textContent = currentCount + 1;
            });
        } else {
            alert(data.message || 'Failed to add comment');
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        alert('An error occurred while adding comment');
    }

    submitBtn.disabled = false;
});

// ============================================
// LOAD COMMENTS
// ============================================

async function loadComments(postId) {
    try {
        const response = await fetch(`../actions/post_comment_action.php?action=get&post_id=${postId}`);
        const data = await response.json();

        if (data.status === 'success') {
            const commentsList = document.querySelector(`.comments-section[data-post-id="${postId}"] .comments-list`);

            if (data.comments.length === 0) {
                commentsList.innerHTML = '<div style="color:#999;font-size:14px;padding:10px 0;text-align:center;">No comments yet. Be the first to comment!</div>';
            } else {
                let html = '';
                data.comments.forEach(comment => {
                    html += `
                        <div class="comment-item" style="padding:12px 0;border-bottom:1px solid #f5f5f5;">
                            <div class="comment-author" style="font-weight:600;color:#2c3e50;margin-bottom:4px;">
                                <i class="fas fa-user me-1" style="color:#667eea;font-size:12px;"></i>
                                ${escapeHtml(comment.user_name)}
                                <span style="color:#999;font-size:0.8rem;font-weight:400;margin-left:8px;">${comment.created_at}</span>
                            </div>
                            <div style="color:#555;font-size:0.95rem;line-height:1.5;margin-left:22px;">
                                ${escapeHtml(comment.comment)}
                            </div>
                        </div>
                    `;
                });
                commentsList.innerHTML = html;
            }
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    }
}

// ============================================
// LOAD MORE POSTS
// ============================================

document.getElementById('loadMoreBtn')?.addEventListener('click', async function() {
    const btn = this;
    let offset = parseInt(btn.dataset.offset || '0');
    const limit = 10;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

    try {
        const response = await fetch(`../actions/load_all_posts_action.php?offset=${offset}&limit=${limit}`);
        const data = await response.json();

        if (data.status === 'success' && data.posts.length > 0) {
            const container = document.getElementById('postsContainer');

            data.posts.forEach(post => {
                const postHtml = createPostHtml(post);
                container.insertAdjacentHTML('beforeend', postHtml);
            });

            offset += data.posts.length;
            btn.dataset.offset = offset;

            if (data.posts.length < limit) {
                btn.style.display = 'none';
            } else {
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Load More Posts';
            }
        } else {
            btn.style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading more posts:', error);
        alert('Failed to load more posts');
    }

    btn.disabled = false;
});

// ============================================
// CREATE POST HTML
// ============================================

function createPostHtml(post) {
    // Avatar
    let avatarContent = '';
    if (post.creator_type === 'user') {
        if (post.profile_photo) {
            avatarContent = `<img class="rounded-circle img-fluid" src="${escapeHtml(post.profile_photo)}" alt="Profile" style="width:48px;height:48px;object-fit:cover;">`;
        } else {
            const initials = (post.first_name ? post.first_name[0] : '') + (post.last_name ? post.last_name[0] : '');
            avatarContent = `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-weight:700;">${initials.toUpperCase()}</div>`;
        }
    } else {
        avatarContent = `<div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:linear-gradient(135deg,#ff9800 0%,#ff5722 100%);color:white;font-size:1.2rem;"><i class="fas fa-university"></i></div>`;
    }

    // Verified Badge
    let verifiedBadge = '';
    if (post.creator_type === 'university') {
        verifiedBadge = '<span class="verified-badge uni-badge"><i class="fas fa-check-circle"></i> University</span>';
    } else if (post.is_verified) {
        verifiedBadge = '<span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Alumni</span>';
    }

    // Image
    let imageHtml = '';
    if (post.image_url) {
        imageHtml = `<div class="user-post mt-3"><img src="${escapeHtml(post.image_url)}" alt="Post image" class="img-fluid rounded w-100" onclick="window.open(this.src, '_blank')" style="cursor:pointer;"></div>`;
    }

    // Creator Name
    const creatorName = post.creator_type === 'user' 
        ? `${post.first_name} ${post.last_name}` 
        : post.university_name;

    return `
        <div class="user-post-data" data-post-id="${post.post_id}">
            <div class="d-flex justify-content-between">
                <div class="me-3">${avatarContent}</div>
                <div class="w-100">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-0 d-inline-block">
                                ${escapeHtml(creatorName)}
                                ${verifiedBadge}
                            </h5>
                            <p class="mb-0 text-primary">${post.created_at}</p>
                        </div>
                    </div>
                </div>
            </div>
            ${post.content ? `<div class="mt-3"><p>${escapeHtml(post.content).replace(/\n/g, '<br>')}</p></div>` : ''}
            ${imageHtml}
            <div class="comment-area mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="like-block position-relative d-flex align-items-center">
                        <div class="total-like-block ms-2 me-3">
                            <span class="like-btn" data-post-id="${post.post_id}" style="cursor:pointer;">
                                <i class="${post.user_liked ? 'fas' : 'far'} fa-heart" style="color:${post.user_liked ? '#e74c3c' : '#6c757d'};"></i>
                                <span class="like-count">${post.like_count}</span> Likes
                            </span>
                        </div>
                    </div>
                    <div class="total-comment-block">
                        <span class="comment-toggle-btn" data-post-id="${post.post_id}" style="cursor:pointer;">
                            <i class="far fa-comment"></i>
                            <span class="comment-count">${post.comment_count}</span> Comments
                        </span>
                    </div>
                </div>
                <hr>
                <div class="share-block d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center feather-icon like-btn" data-post-id="${post.post_id}">
                        <i class="${post.user_liked ? 'fas' : 'far'} fa-heart me-2" style="font-size:1.2rem;color:${post.user_liked ? '#e74c3c' : '#6c757d'};"></i>
                        <h6 class="mb-0">${post.user_liked ? 'Liked' : 'Like'}</h6>
                    </div>
                    <div class="d-flex align-items-center feather-icon comment-toggle-btn" data-post-id="${post.post_id}">
                        <i class="far fa-comment me-2" style="font-size:1.2rem;"></i>
                        <h6 class="mb-0">Comment</h6>
                    </div>
                    <div class="d-flex align-items-center feather-icon">
                        <i class="fas fa-share me-2" style="font-size:1.2rem;"></i>
                        <h6 class="mb-0">Share</h6>
                    </div>
                </div>
                <div class="comments-section mt-3" data-post-id="${post.post_id}" style="display:none;">
                    <div class="comments-list mb-3"></div>
                    <form class="comment-text d-flex align-items-center mt-3">
                        <input type="text" class="form-control rounded comment-input" placeholder="Write a comment..." data-post-id="${post.post_id}">
                        <button type="button" class="btn btn-primary ms-2 submit-comment-btn" data-post-id="${post.post_id}">Post</button>
                    </form>
                </div>
            </div>
        </div>
        <hr>
    `;
}
