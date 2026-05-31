<?php
// category.php — Dynamic Category Book Listing
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$db = getDB();

// ── Resolve category by slug or id ────────────────────────────────────────
$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id']   ?? 0);
$cat  = null;

if ($slug) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $cat = $stmt->fetch() ?: null;
}
if (!$cat && $id) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $cat = $stmt->fetch() ?: null;
}

// 404-style if not found
if (!$cat) {
    http_response_code(404);
    $pageTitle = 'Category Not Found — Advent Library';
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="min-h-screen flex items-center justify-center py-32 text-center px-4">';
    echo '<div><p class="text-6xl mb-4">📚</p><h1 class="text-3xl font-bold text-navy mb-3">Category Not Found</h1>';
    echo '<p class="text-gray-500 mb-6">The category you\'re looking for doesn\'t exist or has been removed.</p>';
    echo '<a href="books.php" class="inline-flex items-center gap-2 bg-navy text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">← Back to Books</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// ── Pagination & search ───────────────────────────────────────────────────
$perPage  = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;
$search   = trim($_GET['q'] ?? '');
$sortBy   = in_array($_GET['sort'] ?? '', ['title','author','created_at']) ? $_GET['sort'] : 'created_at';
$sortDir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Total books in this category (with optional search)
$whereSearch = $search ? "AND (b.title LIKE ? OR b.author LIKE ?)" : "";
$countSql    = "SELECT COUNT(*) FROM books b WHERE b.category_id = ? $whereSearch";
$countStmt   = $db->prepare($countSql);
$params      = [$cat['id']];
if ($search) { $params[] = "%$search%"; $params[] = "%$search%"; }
$countStmt->execute($params);
$total   = (int)$countStmt->fetchColumn();
$pages   = max(1, (int)ceil($total / $perPage));

// Fetch books
$booksSql = "
    SELECT b.*
    FROM books b
    WHERE b.category_id = ? $whereSearch
    ORDER BY b.$sortBy $sortDir
    LIMIT $perPage OFFSET $offset
";
$booksStmt = $db->prepare($booksSql);
$booksStmt->execute($params);
$books = $booksStmt->fetchAll();

// ── SEO ───────────────────────────────────────────────────────────────────
$catName        = htmlspecialchars($cat['name']);
$catColor       = $cat['color']       ?: '#1E3A5F';
$catIcon        = $cat['icon']        ?: '📚';
$catDesc        = $cat['description'] ?: "Browse our collection of {$cat['name']} books.";
$pageTitle      = $catName . ' Books — Advent Library';
$pageDescription= $catDesc;

// Build base URL for pagination
function buildUrl(array $extra = []): string {
    $params = array_merge($_GET, $extra);
    unset($params['page']); // always rebuild page
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}

include __DIR__ . '/includes/header.php';
?>
<style>
:root {
    --cat-color: <?= htmlspecialchars($catColor) ?>;
    --navy:   #1E3A5F;
    --gold:   #D4A017;
    --gold-l: #EBB52C;
    --bg:     #F8FAFC;
    --white:  #FFFFFF;
    --text1:  #1F2937;
    --text2:  #4B5563;
    --muted:  #6B7280;
    --border: rgba(30,58,95,.10);
}

/* Hero banner */
.cat-hero {
    background: linear-gradient(135deg, var(--cat-color) 0%, color-mix(in srgb, var(--cat-color), #000 30%) 100%);
    position: relative;
    overflow: hidden;
}
.cat-hero-pattern {
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}

/* Search bar */
.search-form {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
}
.search-input-wrap {
    flex: 1;
    min-width: 220px;
    position: relative;
}
.search-input-wrap svg {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.1rem;
    height: 1.1rem;
    color: var(--muted);
    pointer-events: none;
}
.search-input {
    width: 100%;
    padding: .75rem 1rem .75rem 2.75rem;
    border: 1px solid rgba(30,58,95,.15);
    border-radius: .75rem;
    font-size: .9rem;
    background: var(--white);
    color: var(--text1);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}
.search-input:focus {
    border-color: var(--cat-color);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--cat-color), transparent 85%);
}
.search-btn {
    padding: .75rem 1.5rem;
    background: var(--cat-color);
    color: #fff;
    border-radius: .75rem;
    font-weight: 600;
    font-size: .9rem;
    transition: opacity .2s, transform .2s;
    white-space: nowrap;
}
.search-btn:hover { opacity:.9; transform: translateY(-1px); }

/* Sort bar */
.sort-select {
    padding: .6rem 1rem;
    border: 1px solid var(--border);
    border-radius: .5rem;
    font-size: .85rem;
    background: var(--white);
    color: var(--text2);
    cursor: pointer;
    outline: none;
}

/* Book cards — horizontal on desktop, stacked on mobile */
.books-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}
@media (min-width: 640px) {
    .books-list { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 1024px) {
    .books-list { grid-template-columns: repeat(3, 1fr); }
}
@media (min-width: 1280px) {
    .books-list { grid-template-columns: repeat(4, 1fr); }
}

.book-item {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .3s, box-shadow .3s, border-color .3s;
}
.book-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px -8px rgba(0,0,0,.12);
    border-color: color-mix(in srgb, var(--cat-color), transparent 60%);
}
.book-cover {
    aspect-ratio: 3/4;
    background: linear-gradient(135deg, var(--cat-color), color-mix(in srgb, var(--cat-color), #000 30%));
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.book-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .4s ease;
}
.book-item:hover .book-cover img { transform: scale(1.06); }
.book-cover-ph {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.book-info {
    padding: 1.1rem 1rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: .4rem;
}
.book-name {
    font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;
    font-size: 1rem;
    font-weight: 700;
    color: var(--navy);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.book-by { font-size: .75rem; color: var(--cat-color); font-weight: 600; }
.book-excerpt {
    font-size: .78rem;
    color: var(--muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}
.book-btns {
    display: flex;
    gap: .4rem;
    margin-top: .75rem;
}
.btn-sm-read {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .25rem;
    background: var(--navy);
    color: #fff;
    font-size: .72rem;
    font-weight: 600;
    padding: .5rem .6rem;
    border-radius: .45rem;
    transition: background .2s;
    white-space: nowrap;
}
.btn-sm-read:hover { background: #2563EB; }
.btn-sm-dl {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .25rem;
    background: var(--cat-color);
    color: #fff;
    font-size: .72rem;
    font-weight: 600;
    padding: .5rem .6rem;
    border-radius: .45rem;
    transition: opacity .2s;
    white-space: nowrap;
}
.btn-sm-dl:hover { opacity:.85; }

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: 2.5rem;
}
.page-btn {
    min-width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: .5rem;
    font-size: .85rem;
    font-weight: 500;
    border: 1px solid var(--border);
    background: var(--white);
    color: var(--text2);
    transition: all .2s;
    padding: 0 .6rem;
}
.page-btn:hover { border-color: var(--cat-color); color: var(--cat-color); }
.page-btn.active {
    background: var(--cat-color);
    color: #fff;
    border-color: var(--cat-color);
    font-weight: 700;
}
.page-btn[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
</style>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ═══ CATEGORY HERO BANNER ═══════════════════════════════════ -->
<section class="cat-hero text-white py-16 lg:py-24">
    <div class="cat-hero-pattern" aria-hidden="true"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Breadcrumb -->
        <nav class="text-sm text-white/60 mb-6 flex items-center gap-1.5" aria-label="Breadcrumb">
            <a href="index.php" class="hover:text-white transition">Home</a>
            <span>›</span>
            <a href="books.php" class="hover:text-white transition">Books</a>
            <span>›</span>
            <span class="text-white"><?= $catName ?></span>
        </nav>

        <div class="flex items-center gap-5">
            <!-- Icon -->
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl flex-shrink-0"
                 style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px)">
                <?php if ($cat['thumbnail'] && file_exists(__DIR__ . '/' . $cat['thumbnail'])): ?>
                    <img src="<?= htmlspecialchars($cat['thumbnail']) ?>" alt="" class="w-10 h-10 object-cover rounded-lg">
                <?php else: ?>
                    <?= htmlspecialchars($catIcon) ?>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-white/60 text-xs uppercase tracking-widest font-semibold mb-1">Category</p>
                <h1 class="text-white font-bold leading-tight" style="font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;font-size:clamp(2rem,5vw,3.2rem)">
                    <?= $catName ?>
                </h1>
                <p class="text-white/70 mt-1 text-sm"><?= $total ?> <?= $total === 1 ? 'book' : 'books' ?> found</p>
            </div>
        </div>

        <?php if ($catDesc): ?>
        <p class="mt-5 text-white/65 text-base max-w-2xl leading-relaxed"><?= htmlspecialchars($catDesc) ?></p>
        <?php endif; ?>
    </div>
</section>


<!-- ═══ SEARCH & SORT BAR ══════════════════════════════════════ -->
<div style="background:var(--white);border-bottom:1px solid var(--border)" class="py-5 sticky top-0 z-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="GET" class="search-form">
            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
            <?php if (!$slug): ?><input type="hidden" name="id" value="<?= $cat['id'] ?>"> <?php endif; ?>

            <div class="search-input-wrap">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" class="search-input"
                       placeholder="Search books in <?= $catName ?>…"
                       value="<?= htmlspecialchars($search) ?>"
                       autocomplete="off">
            </div>

            <select name="sort" class="sort-select">
                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Newest First</option>
                <option value="title"      <?= $sortBy === 'title'      ? 'selected' : '' ?>>Title A–Z</option>
                <option value="author"     <?= $sortBy === 'author'     ? 'selected' : '' ?>>Author A–Z</option>
            </select>
            <select name="dir" class="sort-select">
                <option value="desc" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>↓ Desc</option>
                <option value="asc"  <?= $sortDir === 'ASC'  ? 'selected' : '' ?>>↑ Asc</option>
            </select>

            <button type="submit" class="search-btn">Search</button>
            <?php if ($search): ?>
            <a href="<?= buildUrl(['q'=>'','page'=>1]) ?>&page=1" class="search-btn" style="background:#6B7280">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>


<!-- ═══ BOOK GRID ═══════════════════════════════════════════════ -->
<section style="background:var(--bg)" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if ($search): ?>
        <p class="text-sm mb-6" style="color:var(--muted)">
            Showing <strong style="color:var(--text1)"><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?>
            for "<strong style="color:var(--navy)"><?= htmlspecialchars($search) ?></strong>"
        </p>
        <?php endif; ?>

        <?php if (empty($books)): ?>
        <div class="text-center py-24" style="color:var(--muted)">
            <p class="text-5xl mb-4">📭</p>
            <p class="text-xl font-semibold mb-2" style="color:var(--navy)">No books found</p>
            <p class="mb-6"><?= $search ? "Try a different search term." : "No books have been added to this category yet." ?></p>
            <?php if ($search): ?>
            <a href="<?= buildUrl(['q'=>'','page'=>1]) ?>&page=1" class="inline-flex items-center gap-2 text-white px-6 py-3 rounded-lg transition"
               style="background:var(--cat-color)">Clear Search</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="books-list">
            <?php foreach ($books as $book): ?>
            <article class="book-item">
                <!-- Cover -->
                <div class="book-cover">
                    <?php if ($book['cover_image'] && file_exists(__DIR__ . '/' . $book['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="book-cover-ph">
                            <span style="font-size:2.5rem;opacity:.3"><?= htmlspecialchars($catIcon) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($book['is_featured']): ?>
                    <span style="position:absolute;top:.6rem;left:.6rem;background:var(--gold);color:#0b1120;font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:.2rem .55rem;border-radius:999px">⭐ Featured</span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="book-info">
                    <h2 class="book-name"><?= htmlspecialchars($book['title']) ?></h2>
                    <?php if ($book['author']): ?>
                    <p class="book-by">by <?= htmlspecialchars($book['author']) ?></p>
                    <?php endif; ?>
                    <?php if ($book['description']): ?>
                    <p class="book-excerpt"><?= htmlspecialchars($book['description']) ?></p>
                    <?php endif; ?>

                    <div class="book-btns">
                        <?php if ($book['pdf_file'] && file_exists(__DIR__ . '/' . $book['pdf_file'])): ?>
                            <a href="<?= htmlspecialchars($book['pdf_file']) ?>" target="_blank" rel="noopener" class="btn-sm-read">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Read
                            </a>
                            <a href="<?= htmlspecialchars($book['pdf_file']) ?>" download class="btn-sm-dl">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download
                            </a>
                        <?php else: ?>
                            <span class="btn-sm-read" style="opacity:.45;cursor:not-allowed">No PDF</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- ═══ PAGINATION ══════════════════════════════════ -->
        <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Page navigation">
            <!-- Prev -->
            <?php if ($page > 1): ?>
            <a href="<?= buildUrl() ?>&page=<?= $page-1 ?>" class="page-btn" aria-label="Previous">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <?php else: ?>
            <span class="page-btn" disabled aria-hidden="true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </span>
            <?php endif; ?>

            <?php
            // Smart pagination: show first, last, and window around current
            $window = 2;
            $shown  = [];
            for ($p = 1; $p <= $pages; $p++) {
                if ($p === 1 || $p === $pages || abs($p - $page) <= $window) {
                    $shown[] = $p;
                }
            }
            $prev = null;
            foreach ($shown as $p):
                if ($prev !== null && $p - $prev > 1): ?>
                    <span class="page-btn" style="border:none;background:none">…</span>
                <?php endif;
                $prev = $p;
            ?>
            <a href="<?= buildUrl() ?>&page=<?= $p ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"
               aria-current="<?= $p === $page ? 'page' : 'false' ?>"><?= $p ?></a>
            <?php endforeach; ?>

            <!-- Next -->
            <?php if ($page < $pages): ?>
            <a href="<?= buildUrl() ?>&page=<?= $page+1 ?>" class="page-btn" aria-label="Next">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <?php else: ?>
            <span class="page-btn" disabled aria-hidden="true">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
            <?php endif; ?>
        </nav>

        <p class="text-center text-xs mt-3" style="color:var(--muted)">
            Page <?= $page ?> of <?= $pages ?> · <?= $total ?> books total
        </p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

