<!-- Single Post Item -->
<div class="user-post-data" data-post-id="<?php echo $post['post_id']; ?>">
    <!-- Post Header -->
    <div class="post-header">
        <div class="post-avatar">
            <?php if ($post['creator_type'] === 'user' && !empty($post['profile_photo'])): ?>
                <img class="avatar-circle" 
                     src="<?php echo htmlspecialchars($post['profile_photo']); ?>" 
                     alt="Profile">
            <?php elseif ($post['creator_type'] === 'user'): ?>
                <div class="avatar-circle avatar-initials">
                    <?php
                    $initials = '';
                    if (!empty($post['first_name'])) $initials .= strtoupper($post['first_name'][0]);
                    if (!empty($post['last_name'])) $initials .= strtoupper($post['last_name'][0]);
                    echo $initials;
                    ?>
                </div>
            <?php else: ?>
                <div class="avatar-circle avatar-university">
                    <i class="fas fa-university"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="post-info">
            <div class="post-author">
                <h5 class="author-name">
                    <?php echo htmlspecialchars($post['creator_name']); ?>
                    <?php if ($post['creator_type'] === 'university'): ?>
                        <span class="verified-badge uni-badge">
                            <i class="fas fa-check-circle"></i> University
                        </span>
                    <?php elseif ($post['is_verified']): ?>
                        <span class="verified-badge">
                            <i class="fas fa-check-circle"></i> Verified Alumni
                        </span>
                    <?php endif; ?>
                </h5>
                <p class="post-time">
                    <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Post Content -->
    <?php if (!empty($post['content'])): ?>
        <div class="mt-3">
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Post Image -->
    <?php if (!empty($post['image_url'])): ?>
        <div class="user-post mt-3">
            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                 alt="Post image" 
                 class="img-fluid rounded w-100"
                 onclick="window.open(this.src, '_blank')"
                 style="cursor:pointer;">
        </div>
    <?php endif; ?>

    <!-- Post Actions -->
    <div class="comment-area mt-3">
        <!-- Like & Comment Count -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="like-block position-relative d-flex align-items-center">
                <div class="total-like-block ms-2 me-3">
                    <span class="like-btn" data-post-id="<?php echo $post['post_id']; ?>" style="cursor:pointer;">
                        <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart" 
                           style="color:<?php echo $post['user_liked'] ? '#e74c3c' : '#6c757d'; ?>;"></i>
                        <span class="like-count"><?php echo $post['like_count']; ?></span> Likes
                    </span>
                </div>
            </div>
            <div class="total-comment-block">
                <span class="comment-toggle-btn" data-post-id="<?php echo $post['post_id']; ?>" style="cursor:pointer;">
                    <i class="far fa-comment"></i>
                    <span class="comment-count"><?php echo $post['comment_count']; ?></span> Comments
                </span>
            </div>
        </div>
        
        <hr>
        
        <!-- Action Buttons -->
        <div class="share-block d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center feather-icon like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart me-2" 
                   style="font-size:1.2rem;color:<?php echo $post['user_liked'] ? '#e74c3c' : '#6c757d'; ?>;"></i>
                <h6 class="mb-0"><?php echo $post['user_liked'] ? 'Liked' : 'Like'; ?></h6>
            </div>
            <div class="d-flex align-items-center feather-icon comment-toggle-btn" data-post-id="<?php echo $post['post_id']; ?>">
                <i class="far fa-comment me-2" style="font-size:1.2rem;"></i>
                <h6 class="mb-0">Comment</h6>
            </div>
            <div class="d-flex align-items-center feather-icon">
                <i class="fas fa-share me-2" style="font-size:1.2rem;"></i>
                <h6 class="mb-0">Share</h6>
            </div>
        </div>
        
        <!-- Comments Section (Hidden by default) -->
        <div class="comments-section mt-3" data-post-id="<?php echo $post['post_id']; ?>" style="display:none;">
            <div class="comments-list mb-3"></div>
            <form class="comment-text d-flex align-items-center mt-3">
                <input type="text" 
                       class="form-control rounded comment-input" 
                       placeholder="Write a comment..." 
                       data-post-id="<?php echo $post['post_id']; ?>">
                <button type="button" 
                        class="btn btn-primary ms-2 submit-comment-btn" 
                        data-post-id="<?php echo $post['post_id']; ?>">
                    Post
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (isset($allPosts) && $post !== end($allPosts)): ?>
    <hr>
<?php endif; ?>
