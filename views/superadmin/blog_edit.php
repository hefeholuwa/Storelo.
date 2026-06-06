<?php
// views/superadmin/blog_edit.php — Create or Edit a Blog Post
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_superadmin();

$db = DB::connect();
$error = '';
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$post = [
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content' => '',
    'featured_image' => '',
    'author_name' => 'Storelo Team',
    'status' => 'draft'
];

if ($post_id > 0) {
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $post = $existing;
    } else {
        redirect('/superadmin/blog');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author_name = trim($_POST['author_name'] ?? 'Storelo Team');
    $status = in_array($_POST['status'] ?? 'draft', ['draft', 'published']) ? $_POST['status'] : 'draft';
    
    // Auto-generate slug if empty
    if (empty($slug) && !empty($title)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = trim($slug, '-');
    }

    if (empty($title) || empty($slug)) {
        $error = "Title and URL Slug are required.";
    } else {
        // Handle Featured Image Upload
        $featured_image = $post['featured_image'];
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('featured_') . '.' . $ext;
            $upload_dir = __DIR__ . '/../../uploads/blog/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $filename)) {
                $featured_image = '/uploads/blog/' . $filename;
            }
        }

        if (empty($error)) {
            try {
                if ($post_id > 0) {
                    $stmt = $db->prepare("UPDATE blog_posts SET title=?, slug=?, excerpt=?, content=?, featured_image=?, author_name=?, status=? WHERE id=?");
                    $stmt->execute([$title, $slug, $excerpt, $content, $featured_image, $author_name, $status, $post_id]);
                } else {
                    $stmt = $db->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, author_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $excerpt, $content, $featured_image, $author_name, $status]);
                    $post_id = $db->lastInsertId();
                }
                redirect('/superadmin/blog?msg=saved');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate entry for slug)
                    $error = "The URL Slug '$slug' is already in use. Please choose another one.";
                } else {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
    
    // Repopulate form on error
    $post = array_merge($post, [
        'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 
        'content' => $content, 'author_name' => $author_name, 'status' => $status
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post_id ? 'Edit' : 'Create' ?> Blog Post — Storelo Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    
    <!-- Quill Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .form-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
        .editor-wrapper { background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); overflow: hidden; }
        .ql-toolbar.ql-snow { border: none; border-bottom: 1px solid var(--border-subtle); background: #f8fafc; padding: 12px; }
        .ql-container.ql-snow { border: none; height: 500px; font-family: inherit; font-size: 1rem; }
        .image-preview { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; border: 1px dashed var(--border-light); margin-top: 10px; display: <?= $post['featured_image'] ? 'block' : 'none' ?>; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border-subtle); font-weight: 600; background: #f8fafc; border-radius: 8px 8px 0 0; }
        .card-body { padding: 20px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; color: var(--text-secondary); }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/superadmin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <a href="<?= BASE_URL ?>/superadmin/blog" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">&larr; Back to Blog</a>
                    <h1 style="margin-top: 8px;"><?= $post_id ? 'Edit Post' : 'Create New Post' ?></h1>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="blogForm">
                <div class="form-grid">
                    
                    <!-- Main Content Column -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <div class="glass-card">
                            <div class="card-body">
                                <div class="input-group">
                                    <label>Post Title</label>
                                    <input type="text" name="title" class="form-control" style="font-size: 1.1rem; padding: 12px; font-weight: 600;" placeholder="Enter an engaging title..." value="<?= e($post['title']) ?>" required>
                                </div>
                                
                                <div class="input-group" style="margin-bottom: 0;">
                                    <label>Post Content</label>
                                    <div class="editor-wrapper">
                                        <div id="editor-container"><?= $post['content'] ?></div>
                                    </div>
                                    <input type="hidden" name="content" id="hiddenContent">
                                </div>
                            </div>
                        </div>
                        
                        <div class="glass-card">
                            <div class="card-header">Search Engine Optimization (SEO)</div>
                            <div class="card-body">
                                <div class="input-group">
                                    <label>URL Slug</label>
                                    <div style="display: flex; align-items: center; border: 1px solid var(--border-subtle); border-radius: 6px; overflow: hidden; background: #f8fafc;">
                                        <span style="padding: 10px 14px; color: var(--text-muted); font-size: 0.9rem; border-right: 1px solid var(--border-subtle);">storelo.com/blog/</span>
                                        <input type="text" name="slug" class="form-control" style="border: none; border-radius: 0; box-shadow: none;" placeholder="how-to-sell-online" value="<?= e($post['slug']) ?>">
                                    </div>
                                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 6px;">Leave blank to auto-generate from title.</small>
                                </div>
                                
                                <div class="input-group" style="margin-bottom: 0;">
                                    <label>Short Excerpt (Meta Description)</label>
                                    <textarea name="excerpt" class="form-control" rows="3" placeholder="A brief summary of the post for Google search results and the blog list page..."><?= e($post['excerpt']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Column -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        
                        <div class="glass-card">
                            <div class="card-header">Publishing</div>
                            <div class="card-body">
                                <div class="input-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft - Hidden from public</option>
                                        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published - Visible to everyone</option>
                                    </select>
                                </div>
                                
                                <div class="input-group">
                                    <label>Author Name</label>
                                    <input type="text" name="author_name" class="form-control" value="<?= e($post['author_name']) ?>" required>
                                </div>

                                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 10px;">💾 Save Post</button>
                            </div>
                        </div>

                        <div class="glass-card">
                            <div class="card-header">Featured Image</div>
                            <div class="card-body">
                                <div class="input-group" style="margin-bottom: 0;">
                                    <label>Cover Photo (Recommended 1200x630)</label>
                                    <input type="file" name="featured_image" id="featuredImage" class="form-control" accept="image/jpeg, image/png, image/webp">
                                    <img id="imagePreview" class="image-preview" src="<?= $post['featured_image'] ? BASE_URL . $post['featured_image'] : '' ?>" alt="Preview">
                                </div>
                            </div>
                        </div>

                    </div>
                    
                </div>
            </form>
        </div>
    </div>

    <!-- Quill Editor Scripts -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // Image preview logic
        document.getElementById('featuredImage').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Initialize Quill editor
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Write your amazing article here...',
            modules: {
                toolbar: {
                    container: [
                        [{ 'header': [2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image', 'video', 'blockquote'],
                        ['clean']
                    ],
                    handlers: {
                        image: selectLocalImage
                    }
                }
            }
        });

        // Handle form submission to pass Quill HTML
        document.getElementById('blogForm').addEventListener('submit', function() {
            var html = quill.root.innerHTML;
            document.getElementById('hiddenContent').value = html;
        });

        // Custom Image Handler for Quill (Upload to Server)
        function selectLocalImage() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = () => {
                const file = input.files[0];
                if (/^image\//.test(file.type)) {
                    saveToServer(file);
                } else {
                    alert('You can only upload images.');
                }
            };
        }

        function saveToServer(file) {
            const fd = new FormData();
            fd.append('file', file);

            // Add loading placeholder
            const range = quill.getSelection(true);
            quill.insertText(range.index, 'Uploading image...', 'user');

            fetch('<?= BASE_URL ?>/api/upload_blog_image.php', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                quill.deleteText(range.index, 18); // remove 'Uploading image...' text
                if (data.location) {
                    quill.insertEmbed(range.index, 'image', data.location);
                    quill.setSelection(range.index + 1);
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                quill.deleteText(range.index, 18);
                alert('Upload failed due to network error.');
                console.error(err);
            });
        }
    </script>
</body>
</html>
