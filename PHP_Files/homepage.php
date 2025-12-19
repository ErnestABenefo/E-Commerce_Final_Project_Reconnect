<?php
// Main Feed - Shows all posts from all users and universities
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: Log In/log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/settings/db_class.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id) {
    header('Location: Log In/log_in_and_register.php');
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get current user info
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_photo FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$displayName = $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'Member';

// Check if acting as university
$acting_as_university = isset($_SESSION['acting_as_university']) && $_SESSION['acting_as_university'] === true;
if ($acting_as_university) {
    $displayName = htmlspecialchars($_SESSION['active_university_name']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feed - ReConnect</title>
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
    <style>
        :root{--primary:#667eea;--danger:#e74c3c;--muted:#666}
        body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f4f6f8;margin:0;padding:0}
        
        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-brand i {
            font-size: 1.8rem;
        }
        .navbar-menu {
            list-style: none;
            display: flex;
            gap: 8px;
            margin: 0;
            padding: 0;
            align-items: center;
        }
        .navbar-menu a {
            text-decoration: none;
            color: #555;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .navbar-menu a:hover {
            background: #f4f6f8;
            color: var(--primary);
        }
        .navbar-menu a.active {
            background: var(--primary);
            color: white;
        }
        .navbar-menu .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .navbar-menu .btn.danger {
            background: var(--danger);
            color: white;
        }
        .navbar-menu .btn.danger:hover {
            background: #c0392b;
        }
        
        /* Container */
        .container{max-width:800px;margin:24px auto;padding:0 16px}
        
        /* Card */
        .card{background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:16px}
        
        /* Header */
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
        .page-header h1{margin:0;font-size:1.8rem;color:#333}
        
        /* Create Post Button */
        .btn{background:var(--primary);color:#fff;border:0;padding:10px 20px;border-radius:6px;cursor:pointer;font-weight:600;font-size:1rem;transition:background 0.2s}
        .btn:hover{background:#5568d3}
        .btn.secondary{background:#6c757d}
        .btn.secondary:hover{background:#5a6268}
        
        /* Post Item */
        .post-item{background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:16px}
        .post-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
        .post-avatar{width:48px;height:48px;border-radius:50%;background:#ddd;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:1.2rem}
        .post-avatar img{width:100%;height:100%;border-radius:50%;object-fit:cover}
        .post-author{flex:1}
        .post-author-name{font-weight:700;font-size:1rem;color:#333;margin:0}
        .post-date{color:var(--muted);font-size:0.85rem}
        .post-content{color:#333;font-size:1rem;line-height:1.6;margin-bottom:12px;white-space:pre-wrap}
        .post-image{max-width:100%;height:auto;border-radius:8px;margin-top:12px;cursor:pointer}
        
        /* Post Actions */
        .post-actions{display:flex;gap:20px;padding-top:12px;border-top:1px solid #f0f0f0}
        .action-btn{background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:6px;transition:background 0.2s;color:#666;font-size:0.95rem}
        .action-btn:hover{background:#f5f5f5}
        .action-btn i{font-size:1.1rem}
        .action-btn .count{font-weight:600}
        
        /* Comments Section */
        .comments-section{margin-top:16px;padding-top:16px;border-top:1px solid #f0f0f0;display:none}
        .comments-list{margin-bottom:12px}
        .comment-item{padding:12px 0;border-bottom:1px solid #f8f8f8}
        .comment-author{font-weight:600;font-size:0.9rem;margin-bottom:4px}
        .comment-text{font-size:0.9rem;color:#333;padding-left:20px}
        .comment-form{display:flex;gap:8px;margin-top:12px}
        .comment-input{flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:20px;outline:none;font-size:0.9rem}
        .comment-submit{background:var(--primary);color:#fff;border:none;padding:10px 20px;border-radius:20px;cursor:pointer;font-weight:600}
        
        /* Modal */
        .modal{display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:9999}
        .modal-content{background:#fff;padding:24px;border-radius:12px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto}
        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .modal-header h2{margin:0;font-size:1.5rem}
        .modal-close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:600;margin-bottom:6px}
        .form-group textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-family:inherit;font-size:1rem;resize:vertical;min-height:120px}
        .form-group input[type="file"]{display:none}
        .file-label{display:inline-block;padding:10px 16px;background:#f0f0f0;border-radius:6px;cursor:pointer;font-size:0.95rem}
        .file-label:hover{background:#e0e0e0}
        .image-preview-container{margin-top:12px;display:none}
        .image-preview{max-width:100%;max-height:300px;border-radius:8px;border:1px solid #ddd}
        .remove-image-btn{margin-top:8px;padding:6px 12px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:0.85rem}
        
        /* Loading */
        .loading{text-align:center;padding:20px;color:var(--muted)}
        
        /* Empty state */
        .empty-state{text-align:center;padding:40px;color:var(--muted)}
        .empty-state i{font-size:3rem;margin-bottom:16px;opacity:0.5}
        
        /* Creator Badge */
        .creator-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:4px;font-size:0.75rem;font-weight:600;margin-left:8px}
        .creator-badge.user{background:#e3f2fd;color:#1976d2}
        .creator-badge.university{background:#fff3e0;color:#f57c00}
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-graduation-cap"></i>
                ReConnect
            </a>
            
            <?php include 'view/search_component.php'; ?>
            
            <ul class="navbar-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="view/groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="view/events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="view/marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="view/jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="view/dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
                <li>
                    <form method="post" action="actions/logout_user_action.php" style="margin:0">
                        <button type="submit" class="btn danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-rss"></i> Community Feed</h1>
            <button id="openCreatePostModal" class="btn">
                <i class="fas fa-plus"></i> Create Post
            </button>
        </div>

        <!-- Context Banner (if acting as university) -->
        <?php if ($acting_as_university): ?>
        <div class="card" style="background:#fff3e0;border-left:4px solid #f57c00;margin-bottom:16px">
            <div style="display:flex;align-items:center;gap:10px">
                <i class="fas fa-university" style="font-size:1.5rem;color:#f57c00"></i>
                <div>
                    <div style="font-weight:700;color:#e65100">Acting as University</div>
                    <div style="font-size:0.9rem;color:#666">You are posting as <?php echo htmlspecialchars($_SESSION['active_university_name']); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Posts Feed -->
        <div id="postsContainer">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading posts...
            </div>
        </div>

        <!-- Load More Button -->
        <div style="text-align:center;margin-top:20px">
            <button id="loadMoreBtn" class="btn secondary" style="display:none" data-offset="0">
                <i class="fas fa-chevron-down"></i> Load More
            </button>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div id="createPostModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Post</h2>
                <button id="closeModal" class="modal-close">&times;</button>
            </div>
            <form id="createPostForm">
                <div class="form-group">
                    <label for="postContent">What's on your mind?</label>
                    <textarea id="postContent" name="content" placeholder="Share your thoughts..."></textarea>
                </div>
                <div class="form-group">
                    <label for="postImage" class="file-label">
                        <i class="fas fa-image"></i> Add Image
                    </label>
                    <input type="file" id="postImage" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <div id="imagePreviewContainer" class="image-preview-container">
                        <img id="imagePreview" class="image-preview" alt="Preview">
                        <button type="button" id="removeImageBtn" class="remove-image-btn">
                            <i class="fas fa-times"></i> Remove Image
                        </button>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button type="button" id="cancelPostBtn" class="btn secondary">Cancel</button>
                    <button type="submit" id="submitPostBtn" class="btn">Post</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const userId = <?php echo $user_id; ?>;
        let currentOffset = 0;
        const postsPerPage = 10;

        // Load initial posts
        loadPosts();

        // Load posts function
        async function loadPosts(append = false) {
            try {
                const response = await fetch(`actions/load_all_posts_action.php?offset=${currentOffset}&limit=${postsPerPage}`);
                const data = await response.json();

                if (data.status === 'success') {
                    const container = document.getElementById('postsContainer');
                    
                    if (data.posts.length === 0 && currentOffset === 0) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No posts yet. Be the first to share something!</p>
                            </div>
                        `;
                        return;
                    }

                    if (!append) {
                        container.innerHTML = '';
                    }

                    data.posts.forEach(post => {
                        container.appendChild(createPostElement(post));
                    });

                    // Show/hide load more button
                    const loadMoreBtn = document.getElementById('loadMoreBtn');
                    if (data.posts.length === postsPerPage) {
                        loadMoreBtn.style.display = 'inline-block';
                        loadMoreBtn.dataset.offset = currentOffset;
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading posts:', error);
                document.getElementById('postsContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading posts. Please refresh the page.</p>
                    </div>
                `;
            }
        }

        // Create post element
        function createPostElement(post) {
            const postDiv = document.createElement('div');
            postDiv.className = 'post-item';
            postDiv.dataset.postId = post.post_id;

            // Determine creator info
            let creatorIcon = '';
            let creatorBadge = '';
            if (post.creator_type === 'university') {
                creatorIcon = '<i class="fas fa-university" style="color:#f57c00;font-size:2rem"></i>';
                creatorBadge = '<span class="creator-badge university"><i class="fas fa-university"></i> University</span>';
            } else {
                creatorIcon = '<i class="fas fa-user" style="color:#667eea;font-size:2rem"></i>';
                creatorBadge = '<span class="creator-badge user"><i class="fas fa-user"></i> User</span>';
            }

            // Image HTML
            let imageHtml = '';
            if (post.image_url) {
                // Adjust path: stored as '../uploads/posts/...' from view folder, need 'uploads/posts/...' from root
                let imagePath = post.image_url;
                if (imagePath.startsWith('../uploads/')) {
                    imagePath = imagePath.substring(3); // Remove '../'
                }
                imageHtml = `<img src="${escapeHtml(imagePath)}" alt="Post image" class="post-image" onclick="window.open(this.src, '_blank')">`;
            }

            postDiv.innerHTML = `
                <div class="post-header">
                    <div class="post-avatar">${creatorIcon}</div>
                    <div class="post-author">
                        <div class="post-author-name">
                            ${escapeHtml(post.user_name)}
                            ${creatorBadge}
                        </div>
                        <div class="post-date">${formatDate(post.created_at)}</div>
                    </div>
                </div>
                <div class="post-content">${escapeHtml(post.content)}</div>
                ${imageHtml}
                <div class="post-actions">
                    <button class="action-btn like-btn" data-post-id="${post.post_id}">
                        <i class="${post.user_liked ? 'fas' : 'far'} fa-heart" style="color:${post.user_liked ? '#e74c3c' : '#666'}"></i>
                        <span class="count like-count">❤️ ${post.like_count}</span>
                    </button>
                    <button class="action-btn comment-btn" data-post-id="${post.post_id}">
                        <i class="far fa-comment"></i>
                        <span class="count comment-count">💬 ${post.comment_count}</span>
                    </button>
                </div>
                <div class="comments-section" data-post-id="${post.post_id}">
                    <div class="comments-list"></div>
                    <div class="comment-form">
                        <input type="text" class="comment-input" placeholder="Write a comment...">
                        <button class="comment-submit" data-post-id="${post.post_id}">Post</button>
                    </div>
                </div>
            `;

            return postDiv;
        }

        // Load More Button
        document.getElementById('loadMoreBtn').addEventListener('click', function() {
            currentOffset += postsPerPage;
            loadPosts(true);
        });

        // Modal handlers
        const modal = document.getElementById('createPostModal');
        const openModalBtn = document.getElementById('openCreatePostModal');
        const closeModalBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelPostBtn');
        const postImageInput = document.getElementById('postImage');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const removeImageBtn = document.getElementById('removeImageBtn');

        openModalBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
            document.getElementById('postContent').value = '';
            postImageInput.value = '';
            imagePreviewContainer.style.display = 'none';
        });

        closeModalBtn.addEventListener('click', () => modal.style.display = 'none');
        cancelBtn.addEventListener('click', () => modal.style.display = 'none');

        // Image preview
        postImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        removeImageBtn.addEventListener('click', () => {
            postImageInput.value = '';
            imagePreview.src = '';
            imagePreviewContainer.style.display = 'none';
        });

        // Create post form submission
        document.getElementById('createPostForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const content = document.getElementById('postContent').value.trim();
            const imageFile = postImageInput.files[0];
            
            if (!content && !imageFile) {
                alert('Please enter content or select an image');
                return;
            }
            
            const submitBtn = document.getElementById('submitPostBtn');
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('content', content);
                if (imageFile) {
                    formData.append('image', imageFile);
                }
                
                const response = await fetch('actions/create_post_action.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    modal.style.display = 'none';
                    currentOffset = 0;
                    loadPosts(); // Reload all posts
                } else {
                    alert(data.message || 'Failed to create post');
                }
            } catch (error) {
                console.error('Error creating post:', error);
                alert('An error occurred while creating post');
            }
            
            submitBtn.disabled = false;
        });

        // Like button handler
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.like-btn')) {
                const btn = e.target.closest('.like-btn');
                const postId = btn.dataset.postId;
                const icon = btn.querySelector('i');
                const countSpan = btn.querySelector('.like-count');
                
                try {
                    const response = await fetch('actions/post_like_action.php?action=toggle', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `post_id=${postId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        countSpan.textContent = '❤️ ' + data.like_count;
                        
                        if (data.action === 'liked') {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            icon.style.color = '#e74c3c';
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            icon.style.color = '#666';
                        }
                    }
                } catch (error) {
                    console.error('Error toggling like:', error);
                }
            }
        });

        // Comment button handler
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.comment-btn')) {
                const btn = e.target.closest('.comment-btn');
                const postId = btn.dataset.postId;
                const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
                
                if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
                    commentsSection.style.display = 'block';
                    await loadComments(postId);
                } else {
                    commentsSection.style.display = 'none';
                }
            }
        });

        // Comment submit handler
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.comment-submit')) {
                const btn = e.target.closest('.comment-submit');
                const postId = btn.dataset.postId;
                const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
                const input = commentsSection.querySelector('.comment-input');
                const comment = input.value.trim();
                
                if (!comment) {
                    alert('Please enter a comment');
                    return;
                }
                
                btn.disabled = true;
                
                try {
                    const response = await fetch('actions/post_comment_action.php?action=create', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        input.value = '';
                        await loadComments(postId);
                        
                        const countSpan = document.querySelector(`.comment-btn[data-post-id="${postId}"] .comment-count`);
                        if (countSpan) {
                            const currentCount = parseInt(countSpan.textContent.replace(/[^0-9]/g, '')) || 0;
                            countSpan.textContent = '💬 ' + (currentCount + 1);
                        }
                    } else {
                        alert(data.message || 'Failed to add comment');
                    }
                } catch (error) {
                    console.error('Error adding comment:', error);
                    alert('An error occurred');
                }
                
                btn.disabled = false;
            }
        });

        // Load comments function
        async function loadComments(postId) {
            try {
                const response = await fetch(`actions/post_comment_action.php?action=get&post_id=${postId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    const commentsList = document.querySelector(`.comments-section[data-post-id="${postId}"] .comments-list`);
                    
                    if (data.comments.length === 0) {
                        commentsList.innerHTML = '<div style="color:#999;font-size:0.9rem;padding:10px 0">No comments yet. Be the first!</div>';
                    } else {
                        let html = '';
                        data.comments.forEach(comment => {
                            html += `
                                <div class="comment-item">
                                    <div class="comment-author">
                                        <i class="fas fa-user" style="color:#667eea;margin-right:4px"></i>
                                        ${escapeHtml(comment.user_name)}
                                        <span style="font-weight:400;color:#999;font-size:0.85rem;margin-left:8px">${formatDate(comment.created_at)}</span>
                                    </div>
                                    <div class="comment-text">${escapeHtml(comment.comment)}</div>
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

        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds

            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    </script>
</body>
</html>