<?php
require_once 'config.php';
$conn = get_db_connection();

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get post details
$post_query = $conn->prepare("
    SELECT p.*, u.username 
    FROM posts p 
    JOIN users u ON p.author_id = u.id 
    WHERE p.id = ?
");
$post_query->bind_param("i", $post_id);
$post_query->execute();
$post_result = $post_query->get_result();

if ($post_result->num_rows === 0) {
    die("Post not found");
}

$post = $post_result->fetch_assoc();

// Handle comment submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $author_name = trim($_POST['author_name']);
    $comment_text = trim($_POST['comment']);
    
    if (empty($author_name) || empty($comment_text)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, author_name, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $post_id, $author_name, $comment_text);
        
        if ($stmt->execute()) {
            $success = 'Comment submitted successfully!';
            // Clear form
            $_POST['author_name'] = $_POST['comment'] = '';
        } else {
            $error = 'Error submitting comment: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get comments for this post
$comments_query = $conn->prepare("
    SELECT * FROM comments 
    WHERE post_id = ? 
    ORDER BY created_at DESC
");
$comments_query->bind_param("i", $post_id);
$comments_query->execute();
$comments_result = $comments_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean_output($post['title']); ?> | Simple Blog</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Add to existing styles */
        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .post-detail {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            margin-bottom: 2rem;
        }
        
        .post-content-full {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
        }
        
        .post-content-full p {
            margin-bottom: 1.5rem;
        }
        
        .comments-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .comments-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }
        
        .comment {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .comment:last-child {
            border-bottom: none;
        }
        
        .comment-author {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .comment-date {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .comment-text {
            color: #4a5568;
            line-height: 1.6;
        }
        
        .comment-form {
            background: #f7fafc;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .form-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .no-comments {
            text-align: center;
            padding: 2rem;
            color: #718096;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to All Posts</a>
        
        <article class="post-detail">
            <h1 class="post-title"><?php echo clean_output($post['title']); ?></h1>
            
            <div class="post-meta">
                <span>By <?php echo clean_output($post['username']); ?></span>
                <span>•</span>
                <span><?php echo date('F j, Y \a\t g:i a', strtotime($post['created_at'])); ?></span>
            </div>
            
            <div class="post-content-full">
                <?php 
                // Preserve line breaks in content
                $content = clean_output($post['content']);
                echo nl2br($content);
                ?>
            </div>
        </article>
        
        <section class="comments-section">
            <h2 class="comments-title">
                <?php echo $comments_result->num_rows; ?> 
                Comment<?php echo $comments_result->num_rows != 1 ? 's' : ''; ?>
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($comments_result->num_rows > 0): ?>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment">
                        <div class="comment-author">
                            <?php echo clean_output($comment['author_name']); ?>
                        </div>
                        <div class="comment-date">
                            <?php echo date('F j, Y \a\t g:i a', strtotime($comment['created_at'])); ?>
                        </div>
                        <div class="comment-text">
                            <?php echo nl2br(clean_output($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-comments">
                    No comments yet. Be the first to comment!
                </div>
            <?php endif; ?>
            
            <div class="comment-form">
                <h3 class="form-title">Add a Comment</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="author_name">Your Name</label>
                        <input type="text" 
                               id="author_name" 
                               name="author_name" 
                               class="form-control" 
                               value="<?php echo isset($_POST['author_name']) ? clean_output($_POST['author_name']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Your Comment</label>
                        <textarea 
                            id="comment" 
                            name="comment" 
                            class="form-control" 
                            required><?php echo isset($_POST['comment']) ? clean_output($_POST['comment']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_comment" class="btn">
                        Post Comment
                    </button>
                </form>
            </div>
        </section>
        
        <footer>
            <p>© <?php echo date('Y'); ?> Simple Blog</p>
        </footer>
    </div>
</body>
</html>
<?php $conn->close(); ?>