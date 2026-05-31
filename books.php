<?php
// books.php — Advent Library Books Page
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

$pageTitle       = 'Books — Advent Library';
$pageDescription = 'Explore our full collection of Christian and Adventist books — featured titles, devotionals, EGW writings, and more.';

$db = getDB();

// ── Featured books (up to 4) ───────────────────────────────────────────────
function getFeaturedBooks(PDO $db): array {
    try {
        return $db->query("
            SELECT b.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE b.is_featured = 1
            ORDER BY b.created_at DESC
            LIMIT 4
        ")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

// Fallback: latest 4 if no featured
function getLatestBooks(PDO $db, int $limit = 4): array {
    try {
        $stmt = $db->prepare("
            SELECT b.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            ORDER BY b.created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

// ── All categories with book counts ───────────────────────────────────────
function getCategories(PDO $db): array {
    try {
        return $db->query("
            SELECT c.*, COUNT(b.id) AS book_count
            FROM categories c
            LEFT JOIN books b ON b.category_id = c.id
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

$featured   = getFeaturedBooks($db);
if (empty($featured)) {
    $featured = getLatestBooks($db, 4);
}
$categories = getCategories($db);

// ── Totals for the hero banner ─────────────────────────────────────────────
$totalBooks = (int)($db->query("SELECT COUNT(*) FROM books")->fetchColumn() ?? 0);
$totalCats  = count($categories);

function truncateWords(string $text, int $words = 16): string {
    $text  = strip_tags($text);
    $parts = explode(' ', $text);
    return count($parts) > $words
        ? implode(' ', array_slice($parts, 0, $words)) . '…'
        : $text;
}

include __DIR__ . '/includes/header.php';
?>
<style>
/* ── Root tokens ── */
:root {
    --navy:   #1E3A5F;
    --gold:   #D4A017;
    --gold-l: #EBB52C;
    --green:  #2E7D32;
    --bg:     #F8FAFC;
    --bg2:    #F1F5F9;
    --white:  #FFFFFF;
    --text1:  #1F2937;
    --text2:  #4B5563;
    --muted:  #6B7280;
    --border: rgba(30,58,95,.10);
}

/* ── Page hero ── */
.books-hero {
    background: linear-gradient(135deg, var(--navy) 0%, #0f2240 100%);
    position: relative;
    overflow: hidden;
}
.books-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 70% 50%, rgba(212,160,23,.12) 0%, transparent 70%),
        url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.books-hero .cross-bg {
    position: absolute;
    right: 5%;
    top: 50%;
    transform: translateY(-50%);
    opacity: .04;
    pointer-events: none;
}

/* ── Section headings ── */
.section-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: rgba(212,160,23,.12);
    border: 1px solid rgba(212,160,23,.25);
    border-radius: 999px;
    padding: .3rem .9rem;
    font-size: .7rem;
    letter-spacing: .15em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 600;
}
.section-title {
    font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;
    font-size: clamp(1.8rem, 3.5vw, 2.6rem);
    font-weight: 700;
    color: var(--navy);
    line-height: 1.15;
}

/* ── Featured Book Cards ── */
.book-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    position: relative;
}
.book-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px -10px rgba(30,58,95,.15);
    border-color: rgba(212,160,23,.3);
}
.book-cover-wrap {
    position: relative;
    aspect-ratio: 3/4;
    background: linear-gradient(135deg, var(--navy) 0%, #0f2240 100%);
    overflow: hidden;
    flex-shrink: 0;
}
.book-cover-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .5s ease;
}
.book-card:hover .book-cover-wrap img { transform: scale(1.05); }

.book-cover-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .75rem;
    padding: 1.5rem;
}
.featured-badge {
    position: absolute;
    top: .75rem;
    left: .75rem;
    background: var(--gold);
    color: #0b1120;
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    padding: .25rem .6rem;
    border-radius: 999px;
    z-index: 2;
}
.cat-tag {
    position: absolute;
    bottom: .75rem;
    right: .75rem;
    color: #fff;
    font-size: .65rem;
    font-weight: 600;
    padding: .2rem .65rem;
    border-radius: 999px;
    z-index: 2;
    backdrop-filter: blur(4px);
}
.book-body {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}
.book-title {
    font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--navy);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.book-author {
    font-size: .78rem;
    color: var(--gold);
    font-weight: 600;
}
.book-desc {
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.55;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}
.book-actions {
    display: flex;
    gap: .5rem;
    margin-top: auto;
    padding-top: .75rem;
}
.btn-read {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    background: var(--navy);
    color: #fff;
    font-size: .78rem;
    font-weight: 600;
    padding: .55rem .75rem;
    border-radius: .5rem;
    transition: background .2s, transform .2s;
    white-space: nowrap;
}
.btn-read:hover { background: #2563EB; transform: translateY(-1px); }
.btn-download {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    background: var(--gold);
    color: #0b1120;
    font-size: .78rem;
    font-weight: 600;
    padding: .55rem .75rem;
    border-radius: .5rem;
    transition: background .2s, transform .2s;
    white-space: nowrap;
}
.btn-download:hover { background: var(--gold-l); transform: translateY(-1px); }
.btn-download-disabled {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    background: #F1F5F9;
    color: var(--muted);
    font-size: .78rem;
    font-weight: 600;
    padding: .55rem .75rem;
    border-radius: .5rem;
    cursor: not-allowed;
}

/* ── Category Grid ── */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.25rem;
}
@media (max-width: 640px) {
    .categories-grid { grid-template-columns: repeat(2, 1fr); gap: .75rem; }
}
.cat-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 1rem;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .6rem;
    text-decoration: none;
    transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}
.cat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity .3s ease;
}
.cat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px -8px rgba(0,0,0,.12);
}
.cat-card:hover::before { opacity: 1; }
.cat-card:hover .cat-arrow { opacity: 1; transform: translateX(0); }

.cat-icon-wrap {
    width: 3rem;
    height: 3rem;
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.cat-name {
    font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;
    font-size: 1rem;
    font-weight: 700;
    color: var(--navy);
    line-height: 1.25;
}
.cat-count {
    font-size: .72rem;
    color: var(--muted);
    font-weight: 500;
}
.cat-arrow {
    position: absolute;
    top: 1rem;
    right: 1rem;
    opacity: 0;
    transform: translateX(-4px);
    transition: opacity .25s, transform .25s;
    color: var(--gold);
}

/* ── No-books empty state ── */
.empty-state {
    text-align: center;
    padding: 5rem 2rem;
    color: var(--muted);
}

/* ── Reveal animation ── */
@keyframes revealUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}
.reveal { opacity:0; }
.reveal.in-view { animation: revealUp .6s ease both; }
</style>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- ══════════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════════ -->
<section class="books-hero text-white py-20 lg:py-28">
    <div class="cross-bg" aria-hidden="true">
        <svg width="400" height="560" viewBox="0 0 400 560" fill="none">
            <rect x="168" y="0" width="64" height="560" fill="white"/>
            <rect x="40" y="144" width="320" height="64" fill="white"/>
        </svg>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-2xl">
            <span class="section-badge mb-4 inline-flex">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Advent Library — Book Collection
            </span>
            <h1 class="font-display text-white" style="font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;font-size:clamp(2.4rem,5vw,3.8rem);font-weight:700;line-height:1.1">
                Discover <em style="color:var(--gold);font-style:italic">Spiritual</em><br>Literature
            </h1>
            <p class="mt-4 text-white/70 text-lg leading-relaxed max-w-xl">
                Browse <?= number_format($totalBooks) ?>+ curated Christian books across <?= $totalCats ?> categories — all free, all rooted in Adventist faith.
            </p>
            <div class="flex flex-wrap gap-3 mt-8">
                <a href="#categories" class="inline-flex items-center gap-2 bg-gold text-black font-semibold px-6 py-3 rounded-lg transition hover:bg-yellow-400 hover:-translate-y-0.5"
                   style="background:var(--gold);color:#0b1120">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h8M4 18h8"/></svg>
                    Browse Categories
                </a>
                <a href="#featured" class="inline-flex items-center gap-2 text-white border border-white/30 hover:border-white/60 px-6 py-3 rounded-lg transition font-medium hover:bg-white/5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Featured Books
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════
     FEATURED / LATEST BOOKS (4 cards)
══════════════════════════════════════════════ -->
<section id="featured" class="py-20" style="background:var(--bg)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 reveal">
            <span class="section-badge">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <?= !empty(array_filter(array_column($featured, 'is_featured'))) ? 'Featured Picks' : 'Latest Additions' ?>
            </span>
            <h2 class="section-title mt-3">
                <?= !empty(array_filter(array_column($featured, 'is_featured'))) ? 'Hand-Picked Featured Books' : 'Latest Books Added' ?>
            </h2>
            <p class="mt-3 text-base max-w-xl mx-auto" style="color:var(--text2)">
                Carefully selected titles to strengthen your faith, deepen your knowledge, and guide your spiritual walk.
            </p>
        </div>

        <?php if (empty($featured)): ?>
        <div class="empty-state reveal">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <p class="text-xl font-semibold mb-2" style="color:var(--navy)">No books yet</p>
            <p>Books added by the admin will appear here.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 reveal">
            <?php foreach ($featured as $book): ?>
            <article class="book-card">
                <!-- Cover -->
                <div class="book-cover-wrap">
                    <?php if ($book['is_featured'] ?? 0): ?>
                    <span class="featured-badge">⭐ Featured</span>
                    <?php endif; ?>

                    <?php if ($book['cover_image'] && file_exists(__DIR__ . '/' . $book['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($book['cover_image']) ?>?v=1"
                             alt="<?= htmlspecialchars($book['title']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="book-cover-placeholder">
                            <svg class="w-12 h-12 opacity-20" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <p class="text-white/50 text-xs text-center leading-snug"><?= htmlspecialchars($book['title']) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($book['cat_name']): ?>
                    <span class="cat-tag" style="background:<?= htmlspecialchars($book['cat_color'] ?? '#1E3A5F') ?>cc">
                        <?= htmlspecialchars($book['cat_name']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="book-body">
                    <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                    <?php if ($book['author']): ?>
                    <p class="book-author">by <?= htmlspecialchars($book['author']) ?></p>
                    <?php endif; ?>
                    <?php if ($book['description']): ?>
                    <p class="book-desc"><?= htmlspecialchars(truncateWords($book['description'], 20)) ?></p>
                    <?php endif; ?>

                    <div class="book-actions">
                        <?php if ($book['pdf_file'] && file_exists(__DIR__ . '/' . $book['pdf_file'])): ?>
                            <a href="<?= htmlspecialchars($book['pdf_file']) ?>" target="_blank" class="btn-read" title="Read Online">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Read
                            </a>
                            <a href="<?= htmlspecialchars($book['pdf_file']) ?>" download class="btn-download" title="Download PDF">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download
                            </a>
                        <?php else: ?>
                            <span class="btn-read opacity-50 cursor-not-allowed">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Read
                            </span>
                            <span class="btn-download-disabled">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                No PDF
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>


<!-- ══════════════════════════════════════════════
     BROWSE CATEGORIES
══════════════════════════════════════════════ -->
<section id="categories" style="background:var(--white);border-top:1px solid var(--border)" class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 reveal">
            <span class="section-badge">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Browse by Topic
            </span>
            <h2 class="section-title mt-3">Explore Our Categories</h2>
            <p class="mt-3 text-base max-w-xl mx-auto" style="color:var(--text2)">
                Every topic you need for your spiritual journey, organized and ready to explore.
            </p>
        </div>

        <?php if (empty($categories)): ?>
        <div class="empty-state reveal">
            <p>No categories yet. Admin can add them from the dashboard.</p>
        </div>
        <?php else: ?>
        <div class="categories-grid reveal">
            <?php foreach ($categories as $cat):
                $color    = $cat['color']     ?: '#1E3A5F';
                $icon     = $cat['icon']      ?: '📚';
                $slug     = $cat['slug']      ?: '';
                $count    = (int)$cat['book_count'];
                $catUrl   = $slug ? 'category.php?slug=' . urlencode($slug) : 'category.php?id=' . $cat['id'];
            ?>
            <a href="<?= htmlspecialchars($catUrl) ?>" class="cat-card" style="--cat-color:<?= htmlspecialchars($color) ?>">
                <!-- Top color accent bar -->
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?= htmlspecialchars($color) ?>;border-radius:1rem 1rem 0 0;opacity:.8"></div>

                <div class="cat-icon-wrap" style="background:<?= htmlspecialchars($color) ?>18">
                    <?php if ($cat['thumbnail'] && file_exists(__DIR__ . '/' . $cat['thumbnail'])): ?>
                        <img src="<?= htmlspecialchars($cat['thumbnail']) ?>" alt="" class="w-8 h-8 object-cover rounded">
                    <?php else: ?>
                        <span style="font-size:1.4rem;line-height:1"><?= htmlspecialchars($icon) ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <p class="cat-name" style="color:<?= htmlspecialchars($color) ?>"><?= htmlspecialchars($cat['name']) ?></p>
                    <p class="cat-count"><?= $count ?> <?= $count === 1 ? 'book' : 'books' ?></p>
                    <?php if ($cat['description']): ?>
                    <p style="font-size:.72rem;color:var(--muted);margin-top:.25rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                        <?= htmlspecialchars($cat['description']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Arrow icon -->
                <span class="cat-arrow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>


<!-- ══════════════════════════════════════════════
     CALL TO ACTION STRIP
══════════════════════════════════════════════ -->
<section class="py-16" style="background:linear-gradient(135deg,var(--navy) 0%,#0f2240 100%)">
    <div class="max-w-4xl mx-auto px-4 text-center text-white">
        <h2 class="font-display text-3xl font-bold mb-3" style="font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em;">
            Can't find what you're looking for?
        </h2>
        <p class="text-white/70 mb-8 text-lg">We're constantly adding new titles. Check back often or browse all categories above.</p>
        <a href="#categories" class="inline-flex items-center gap-2 text-black font-semibold px-8 py-3.5 rounded-xl transition hover:-translate-y-1"
           style="background:var(--gold)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Explore All Categories
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Scroll-reveal
const revEls = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
        entries.forEach((e, i) => {
            if (e.isIntersecting) {
                setTimeout(() => e.target.classList.add('in-view'), i * 80);
                io.unobserve(e.target);
            }
        });
    }, { threshold: 0.06 });
    revEls.forEach(el => io.observe(el));
} else {
    revEls.forEach(el => el.classList.add('in-view'));
}
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); t.scrollIntoView({ behavior:'smooth' }); }
    });
});
</script>

