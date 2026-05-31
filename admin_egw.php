<?php
// admin_egw.php — Admin Panel for EGW Writings
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';

$db = getDB();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add Category
    if ($action === 'add_category') {
        $cat_name = trim($_POST['category_name'] ?? '');
        if ($cat_name) {
            $stmt = $db->prepare('INSERT INTO egw_categories (category_name) VALUES (:name)');
            $stmt->execute([':name' => $cat_name]);
            $message = "Category '$cat_name' added successfully.";
        } else {
            $error = "Category name cannot be empty.";
        }
    }
    
    // Delete Category
    if ($action === 'delete_category') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        if ($cat_id) {
            $stmt = $db->prepare('DELETE FROM egw_categories WHERE id = :id');
            $stmt->execute([':id' => $cat_id]);
            $message = "Category deleted successfully.";
        }
    }

    // Add Book
    if ($action === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        
        $cover_path = '';
        $pdf_path = '';
        
        if (empty($title) || empty($category_id)) {
            $error = "Title and Category are required.";
        } else {
            // Handle Cover Upload
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $coverName = uniqid('cover_') . '.' . $ext;
                $dest = __DIR__ . '/uploads/egw/covers/' . $coverName;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                    $cover_path = 'uploads/egw/covers/' . $coverName;
                }
            }
            
            // Handle PDF Upload
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
                if (strtolower($ext) === 'pdf') {
                    $pdfName = uniqid('book_') . '.pdf';
                    $dest = __DIR__ . '/uploads/egw/books/' . $pdfName;
                    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dest)) {
                        $pdf_path = 'uploads/egw/books/' . $pdfName;
                    }
                } else {
                    $error = "Only PDF files are allowed for books.";
                }
            }
            
            if (!$error) {
                $stmt = $db->prepare('INSERT INTO egw_books (category_id, title, description, cover_image, pdf_file) VALUES (:cat, :title, :desc, :cover, :pdf)');
                $stmt->execute([
                    ':cat' => $category_id,
                    ':title' => $title,
                    ':desc' => $description,
                    ':cover' => $cover_path,
                    ':pdf' => $pdf_path
                ]);
                $message = "Book '$title' added successfully.";
            }
        }
    }
    
    // Delete Book
    if ($action === 'delete_book') {
        $book_id = (int)($_POST['book_id'] ?? 0);
        if ($book_id) {
            // Fetch to delete files
            $stmt = $db->prepare('SELECT cover_image, pdf_file FROM egw_books WHERE id = :id');
            $stmt->execute([':id' => $book_id]);
            $book = $stmt->fetch();
            
            if ($book) {
                if ($book['cover_image'] && file_exists(__DIR__ . '/' . $book['cover_image'])) {
                    unlink(__DIR__ . '/' . $book['cover_image']);
                }
                if ($book['pdf_file'] && file_exists(__DIR__ . '/' . $book['pdf_file'])) {
                    unlink(__DIR__ . '/' . $book['pdf_file']);
                }
                
                $del = $db->prepare('DELETE FROM egw_books WHERE id = :id');
                $del->execute([':id' => $book_id]);
                $message = "Book deleted successfully.";
            }
        }
    }
}

// Fetch data for the views
$categories = $db->query('SELECT * FROM egw_categories ORDER BY category_name ASC')->fetchAll();
$books = $db->query('SELECT b.*, c.category_name FROM egw_books b LEFT JOIN egw_categories c ON b.category_id = c.id ORDER BY b.created_at DESC')->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - EGW Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b1120; color: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { background: #141e32; border: 1px solid rgba(148,163,184,.12); border-radius: 1rem; padding: 1.5rem; }
        .form-input { width: 100%; background: #0b1120; border: 1px solid rgba(148,163,184,.2); color: #fff; padding: 0.75rem; border-radius: 0.5rem; outline: none; margin-bottom: 1rem; }
        .form-input:focus { border-color: #c9953a; }
        .btn-gold { background: #c9953a; color: #0b1120; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer; transition: 0.2s; }
        .btn-gold:hover { background: #e8b84a; }
        .btn-red { background: #ef4444; color: #fff; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; }
        .btn-red:hover { background: #dc2626; }
        th { text-align: left; padding: 0.75rem; border-bottom: 1px solid rgba(148,163,184,.12); color: #94a3b8; font-weight: 500; font-size: 0.875rem; }
        td { padding: 0.75rem; border-bottom: 1px solid rgba(148,163,184,.05); font-size: 0.875rem; }
    </style>
</head>
<body class="p-8">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">EGW Library Admin Panel</h1>
            <a href="ellen_white.php" class="text-amber-400 hover:text-amber-300">View Public Page &rarr;</a>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-900/30 border border-green-500 text-green-300 p-4 rounded-lg mb-6"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-900/30 border border-red-500 text-red-300 p-4 rounded-lg mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Forms Sidebar -->
            <div class="space-y-8">
                <!-- Add Book Form -->
                <div class="card">
                    <h2 class="text-xl font-semibold mb-4 text-amber-400">Upload New Book</h2>
                    <form action="admin_egw.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_book">
                        
                        <label class="block text-sm text-gray-400 mb-1">Title</label>
                        <input type="text" name="title" class="form-input" required>
                        
                        <label class="block text-sm text-gray-400 mb-1">Category</label>
                        <select name="category_id" class="form-input" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label class="block text-sm text-gray-400 mb-1">Description</label>
                        <textarea name="description" class="form-input" rows="3"></textarea>
                        
                        <label class="block text-sm text-gray-400 mb-1">Cover Image (JPG/PNG)</label>
                        <input type="file" name="cover_image" class="form-input" accept="image/*">
                        
                        <label class="block text-sm text-gray-400 mb-1">PDF File</label>
                        <input type="file" name="pdf_file" class="form-input" accept=".pdf">
                        
                        <button type="submit" class="btn-gold w-full mt-2">Upload Book</button>
                    </form>
                </div>

                <!-- Add Category Form -->
                <div class="card">
                    <h2 class="text-xl font-semibold mb-4 text-amber-400">Add Category</h2>
                    <form action="admin_egw.php" method="POST">
                        <input type="hidden" name="action" value="add_category">
                        <input type="text" name="category_name" class="form-input" placeholder="Category Name" required>
                        <button type="submit" class="btn-gold w-full">Add Category</button>
                    </form>
                </div>
            </div>

            <!-- Management Tables -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Books Table -->
                <div class="card overflow-x-auto">
                    <h2 class="text-xl font-semibold mb-4 text-amber-400">Manage Books (<?= count($books) ?>)</h2>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr>
                                <th>Cover</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td>
                                    <?php if ($book['cover_image']): ?>
                                        <img src="<?= htmlspecialchars($book['cover_image']) ?>" class="w-10 h-14 object-cover rounded">
                                    <?php else: ?>
                                        <div class="w-10 h-14 bg-slate-800 rounded"></div>
                                    <?php endif; ?>
                                </td>
                                <td class="font-medium"><?= htmlspecialchars($book['title']) ?></td>
                                <td><span class="bg-amber-400/10 text-amber-400 text-xs px-2 py-1 rounded"><?= htmlspecialchars($book['category_name']) ?></span></td>
                                <td class="text-gray-400"><?= date('M d, Y', strtotime($book['created_at'])) ?></td>
                                <td>
                                    <form action="admin_egw.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                        <input type="hidden" name="action" value="delete_book">
                                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        <button type="submit" class="btn-red">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($books)): ?>
                            <tr><td colspan="5" class="text-center text-gray-500 py-4">No books uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Categories Table -->
                <div class="card overflow-x-auto">
                    <h2 class="text-xl font-semibold mb-4 text-amber-400">Manage Categories (<?= count($categories) ?>)</h2>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-800/50">
                                <td class="text-gray-400">#<?= $cat['id'] ?></td>
                                <td class="font-medium"><?= htmlspecialchars($cat['category_name']) ?></td>
                                <td>
                                    <form action="admin_egw.php" method="POST" onsubmit="return confirm('Deleting a category will unassign books in this category. Continue?');">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn-red">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
