
// Open comment modal
function openCommentModal() {
    const modal = document.getElementById('commentModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    loadComments();
}

// Load counts on page init
function loadCountsOnInit() {
    const postLikesSpan = document.getElementById('postLikeCount');
    const postCommentSpan = document.getElementById('postCommentCount');

    // Get post info
    fetch(`/api/post/${postId}/info`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) return null;
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                if (postLikesSpan) {
                    postLikesSpan.textContent = data.likes > 0 ? `(${data.likes})` : '';
                }
                if (postCommentSpan) {
                    postCommentSpan.textContent = data.comments > 0 ? `(${data.comments})` : '';
                }
                // Set like state if user has liked
                if (data.is_liked && document.getElementById('likeBtn')) {
                    document.getElementById('likeBtn').classList.add('liked');
                }
            }
        })
        .catch(err => console.log('Load counts error:', err));
}

// Load on page init
window.addEventListener('DOMContentLoaded', function () {
    loadCountsOnInit();
});

function closeCommentModal() {
    const modal = document.getElementById('commentModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Load comments via AJAX
function loadComments() {
    fetch(`/api/post/${postId}/comments`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateCommentList(data.comments);
                document.getElementById('commentCount').textContent = data.comments.length;
            } else {
                console.error('Error loading comments:', data.message);
            }
        })
        .catch(err => {
            console.error('Load comments error:', err);
        });
}

// Update comment list in modal
function updateCommentList(comments) {
    const commentList = document.querySelector('.comment-list');
    if (!commentList) return;

    if (!comments || comments.length === 0) {
        commentList.innerHTML =
            `<div class="no-comments"><div><i class="fas fa-inbox"></i></div><p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p></div>`;
        return;
    }

    let html = '';
    comments.forEach(c => {
        html += `
            <div class="modal-comment-item" id="comment-${c.id}">
                <img src="${c.avatar_url || '/assets/images/default-avatar.png'}" alt="${c.username}" class="comment-avatar">
                <div class="comment-body" style="flex:1;">
                    <div>
                        <strong class="comment-author">${c.username}</strong>
                        <span class="comment-time" style="margin-left:8px;"><i class="fas fa-clock" style="font-size:10px;"></i> ${c.created_at}</span>
                    </div>
                    <div class="comment-content">${c.content.replace(/\n/g, '<br>')}</div>
                    <div class="comment-actions">
                        <button class="comment-action-btn ${c.is_liked ? 'liked' : ''}" onclick="likeComment(${c.id}, event)" title="Tim bình luận">
                            <i class="far fa-heart"></i> <span>Tim</span> <span class="likes-count">(<span>${c.likes || 0}</span>)</span>
                        </button>
                        <button class="comment-action-btn" onclick="openReplyForm(${c.id})" title="Trả lời bình luận">
                            <i class="fas fa-reply"></i> <span>Trả lời</span>
                        </button>
                    </div>
                    <div id="reply-form-${c.id}"></div>`;

        if (c.replies && c.replies.length > 0) {
            html += `<div class="comment-replies">`;
            c.replies.forEach(r => {
                html += `
                        <div class="reply-item">
                            <img src="${r.avatar_url || '/assets/images/default-avatar.png'}" alt="${r.username}" class="comment-avatar">
                            <div style="flex:1;">
                                <strong style="font-size:13px;font-weight:700;">${r.username}</strong>
                                <div class="comment-content" style="margin-top:4px;margin-bottom:0;font-size:13px;padding:6px 8px;">${r.content.replace(/\n/g, '<br>')}</div>
                            </div>
                        </div>`;
            });
            html += `</div>`;
        }

        html += `</div></div>`;
    });
    commentList.innerHTML = html;
}

// Submit comment via AJAX
function submitComment(event) {
    event.preventDefault();
    const content = document.getElementById('commentContent').value.trim();

    if (!content) {
        showNotification('Vui lòng nhập nội dung bình luận!', 'info');
        return;
    }

    fetch('/api/comment/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId,
            content: content
        })
    })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Bình luận đã được thêm!', 'success');
                document.getElementById('commentContent').value = '';
                setTimeout(() => loadComments(), 500);
            } else {
                showNotification(data.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(err => {
            console.log(err);
            showNotification('Có lỗi khi gửi bình luận!', 'error');
        });
}

// Like post function
function likePost() {
    const btn = document.getElementById('likeBtn');
    const likeCountSpan = document.getElementById('postLikeCount');

    fetch('/api/post/like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (btn.classList.contains('liked')) {
                    btn.classList.remove('liked');
                    let count = parseInt(likeCountSpan.textContent) || 0;
                    likeCountSpan.textContent = count > 1 ? `(${count - 1})` : '';
                } else {
                    btn.classList.add('liked');
                    let count = parseInt(likeCountSpan.textContent) || 0;
                    likeCountSpan.textContent = `(${count + 1})`;
                }
            }
        })
        .catch(err => console.log(err));
}

// Like comment function
function likeComment(commentId, event) {
    event.preventDefault();
    const btn = event.target.closest('.comment-action-btn');
    const likesCountSpan = btn.querySelector('.likes-count span');
    let likes = parseInt(likesCountSpan.textContent);

    fetch('/api/comment/like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            comment_id: commentId
        })
    })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (btn.classList.contains('liked')) {
                    btn.classList.remove('liked');
                    likesCountSpan.textContent = likes - 1;
                } else {
                    btn.classList.add('liked');
                    likesCountSpan.textContent = likes + 1;
                }
            } else {
                showNotification(data.message || 'Lỗi khi tim bình luận', 'error');
            }
        })
        .catch(err => {
            console.log(err);
            showNotification('Có lỗi khi tim bình luận', 'error');
        });
}

// Open reply form for comment
function openReplyForm(commentId) {
    const replyContainer = document.getElementById(`reply-form-${commentId}`);

    if (!replyContainer) {
        showNotification('Lỗi: Không tìm thấy form trả lời', 'error');
        return;
    }

    // Toggle - close if already open
    if (replyContainer.innerHTML.trim()) {
        replyContainer.innerHTML = '';
        return;
    }

    replyContainer.innerHTML = `
            <form onsubmit="submitReply(event, ${commentId})" style="margin-top: 8px; padding: 8px; background: #f9fafb; border-radius: 8px;">
                <textarea name="reply_content" placeholder="Viết trả lời..." required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:12px;resize:vertical;min-height:40px;font-family:inherit;"></textarea>
                <div style="display:flex;gap:6px;margin-top:6px;justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('reply-form-${commentId}').innerHTML = ''" style="padding:6px 12px;background:#ccc;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Hủy</button>
                    <button type="submit" style="padding:6px 16px;background:var(--primary);color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:bold;font-size:12px;">Gửi</button>
                </div>
            </form>`;
}

// Submit reply via AJAX
function submitReply(event, parentCommentId) {
    event.preventDefault();
    const content = event.target.reply_content.value.trim();

    if (!content) {
        showNotification('Vui lòng nhập nội dung trả lời!', 'info');
        return;
    }

    fetch('/api/comment/reply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            post_id: postId,
            parent_comment_id: parentCommentId,
            content: content
        })
    })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Trả lời đã được gửi!', 'success');
                const replyForm = document.getElementById(`reply-form-${parentCommentId}`);
                if (replyForm) replyForm.innerHTML = '';
                setTimeout(() => loadComments(), 500);
            } else {
                showNotification(data.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(err => {
            console.log(err);
            showNotification('Có lỗi khi gửi trả lời!', 'error');
        });
}

// Share post function
function sharePost() {
    const url = window.location.href;
    const title = "<?= htmlspecialchars($post['title'] ?? '') ?>";

    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(err => console.log(err));
    } else {
        copyToClipboard(url);
        showNotification('Đã sao chép link bài viết!', 'success');
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Đã sao chép!', 'success');
    }).catch(err => {
        console.log(err);
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.textContent = message;
    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 6px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Close modal when clicking overlay
document.getElementById('commentModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeCommentModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeCommentModal();
    }
});