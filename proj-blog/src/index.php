<?php
require_once 'config.php';
$conn = get_db_connection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 5;
$offset = ($page - 1) * $posts_per_page;

// Get total posts count
$total_posts_result = $conn->query("SELECT COUNT(*) as total FROM posts");
$total_posts = $total_posts_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get posts for current page
$posts_query = $conn->prepare("
    SELECT p.*, u.username, COUNT(c.id) as comment_count 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    LEFT JOIN comments c ON p.id = c.post_id 
    GROUP BY p.id 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");
$posts_query->bind_param("ii", $posts_per_page, $offset);
$posts_query->execute();
$posts_result = $posts_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Blog tests</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .tagline {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .post {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .post:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .post-title {
            color: #2d3748;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .post-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .post-title a:hover {
            color: #667eea;
        }
        
        .post-meta {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .post-content {
            color: #4a5568;
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }
        
        .read-more {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .read-more:hover {
            background: #5a67d8;
        }
        
        .comment-count {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: #edf2f7;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 3rem 0;
        }
        
        .page-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .page-link.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        footer {
            text-align: center;
            padding: 2rem 0;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            margin-top: 2rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .post {
                padding: 1.5rem;
            }
            
            .post-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">📝 Simple Blog</div>
            <div class="tagline">Sharing thoughts, one post at a time</div>
        </header>
        
        <main>
            <?php if ($posts_result->num_rows > 0): ?>
                <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <article class="post">
                        <h2 class="post-title">
                            <a href="post.php?id=<?php echo $post['id']; ?>">
                                <?php echo clean_output($post['title']); ?>
                            </a>
                        </h2>
                        
                        <div class="post-meta">
                            <span>By <?php echo clean_output($post['username']); ?></span>
                            <span>•</span>
                            <span><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                            <span class="comment-count">
                                💬 <?php echo $post['comment_count']; ?> comments
                            </span>
                        </div>
                        
                        <div class="post-content">
                            <?php 
                            $content = $post['content'];
                            if (strlen($content) > 200) {
                                $content = substr($content, 0, 200) . '...';
                            }
                            echo clean_output($content);
                            ?>
                        </div>
                        
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="read-more">
                            Read More →
                        </a>
                    </article>
                <?php endwhile; ?>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <h3>No posts yet</h3>
                    <p>Check back soon for new content!</p>
                </div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>© <?php echo date('Y'); ?> Simple Blog. All rights reserved.</p>
            <p>Built with PHP & MySQL</p>
        </footer>
    </div>
</body>
</html>
<?php $conn->close(); ?>