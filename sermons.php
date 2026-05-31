<?php
// sermons.php — Sermons Landing Page
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
$db = getDB();

$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$tab = $_GET['tab'] ?? 'written'; // 'written' or 'video'

$categories = $db->query("SELECT * FROM sermon_categories ORDER BY name ASC")->fetchAll();

$where = [];
$params = [];
if ($search) {
    $where[] = '(s.title LIKE :search OR s.preacher LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($category) {
    $where[] = 's.category_id = :category';
    $params[':category'] = $category;
}
$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch Data
if ($tab === 'written') {
    $sql = "SELECT s.*, c.name as cat_name FROM written_sermons s LEFT JOIN sermon_categories c ON s.category_id = c.id $whereClause ORDER BY s.created_at DESC LIMIT 20";
} else {
    $sql = "SELECT s.*, c.name as cat_name FROM video_sermons s LEFT JOIN sermon_categories c ON s.category_id = c.id $whereClause ORDER BY s.created_at DESC LIMIT 20";
}

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$sermons = $stmt->fetchAll();

function isNewSermon($date) {
    return strtotime($date) >= strtotime('-7 days');
}
?>
<?php
$pageTitle = 'Sermons — Advent Library';
include __DIR__ . '/includes/header.php';
?>
    <style type="text/tailwindcss">
        :root {
            --c-bg: theme('colors.soft-white');
            --c-surface: #ffffff;
            --c-border: rgba(30, 58, 95, 0.1);
            --c-gold: theme('colors.gold');
            --c-gold-pale: theme('colors.gold/10');
        }

        .hero {
            background: linear-gradient(to bottom, rgba(30,58,95,0.8), rgba(30,58,95,0.95)), url('https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1600&q=80') center/cover;
            padding: 8rem 1rem 4rem;
        }

        .search-bar { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: .75rem; }
        .search-input { background: transparent; color: theme('colors.navy'); outline: none; width: 100%; }
        .search-input::placeholder { color: theme('colors.slate.400'); }
        .search-select { background: var(--c-surface); color: theme('colors.slate.500'); border: none; border-left: 1px solid var(--c-border); outline: none; }
        .search-btn { background: var(--c-gold); color: #ffffff; font-weight: 600; border-radius: .5rem; transition: 0.2s; }
        .search-btn:hover { background: theme('colors.royal'); }

        .sermon-card {
            background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 1rem; overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .sermon-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.05), 0 0 0 1px rgba(212, 160, 23, 0.2); }
        .card-img-wrapper { position: relative; width: 100%; aspect-ratio: 16/9; overflow: hidden; }
        .card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .sermon-card:hover .card-img { transform: scale(1.05); }

        .badge-new { position: absolute; top: .75rem; left: .75rem; background: theme('colors.forest'); color: #fff; font-size: .6rem; font-weight: 700; padding: .25rem .5rem; border-radius: 999px; letter-spacing: 1px; }
        .badge-cat { font-size: .65rem; font-weight: 600; color: var(--c-gold); background: var(--c-gold-pale); padding: .2rem .6rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 1px; }

        .tab-btn { padding: 0.75rem 2rem; font-weight: 600; border-radius: 999px; transition: 0.3s; }
        .tab-active { background: var(--c-gold); color: #ffffff; box-shadow: 0 4px 15px rgba(212,160,23,0.3); }
        .tab-inactive { background: var(--c-surface); color: theme('colors.slate.500'); border: 1px solid var(--c-border); }
        .tab-inactive:hover { color: theme('colors.navy'); background: rgba(30,58,95,0.05); }
    </style>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Breadcrumbs -->
<div class="fixed top-20 left-0 w-full z-40 bg-[var(--c-bg)]/90 backdrop-blur border-b border-[var(--c-border)] py-3 px-4 sm:px-8">
    <div class="max-w-7xl mx-auto flex items-center text-xs font-medium text-slate-500 tracking-widest uppercase">
        <a href="index.php" class="hover:text-[var(--c-gold)] transition-colors">Home</a>
        <span class="mx-2">/</span>
        <span class="text-navy">Sermons</span>
    </div>
</div>

<section class="hero text-center">
    <div class="max-w-3xl mx-auto px-4">
        <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight mb-4">
            Spiritual <em class="text-[var(--c-gold)] italic">Nourishment</em>
        </h1>
        <p class="text-lg text-slate-100 leading-relaxed max-w-2xl mx-auto">
            Explore hundreds of biblical sermons. Watch our latest video messages or dive deep into written studies.
        </p>
    </div>
</section>

<!-- Search & Tabs -->
<section class="py-8 border-b border-[var(--c-border)] relative z-20">
    <div class="max-w-5xl mx-auto px-4">
        
        <div class="flex justify-center mb-10 space-x-4">
            <a href="sermons.php?tab=written&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="tab-btn <?= $tab === 'written' ? 'tab-active' : 'tab-inactive' ?>">Written Sermons</a>
            <a href="sermons.php?tab=video&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" class="tab-btn <?= $tab === 'video' ? 'tab-active' : 'tab-inactive' ?>">Video Sermons</a>
        </div>

        <form action="sermons.php" method="GET">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div class="search-bar flex flex-col sm:flex-row shadow-lg">
                <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search sermons or preachers…" class="search-input py-4 px-6">
                <select name="category" class="search-select py-4 px-4 sm:w-64">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($category == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="search-btn px-8 py-4 m-1">Search</button>
            </div>
        </form>
    </div>
</section>

<!-- Sermons Grid -->
<section class="py-16 min-h-[50vh]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (count($sermons) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($sermons as $s): ?>
                    <article class="sermon-card flex flex-col group">
                        
                        <div class="card-img-wrapper">
                            <?php if (isNewSermon($s['created_at'])): ?><span class="badge-new z-10">NEW</span><?php endif; ?>
                            
                            <?php if ($tab === 'video'): ?>
                                <img src="https://img.youtube.com/vi/<?= htmlspecialchars($s['youtube_id']) ?>/maxresdefault.jpg" onerror="this.src='https://img.youtube.com/vi/<?= htmlspecialchars($s['youtube_id']) ?>/hqdefault.jpg'" class="card-img" alt="Thumbnail">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center pl-1 shadow-lg shadow-red-600/40">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if ($s['cover_image']): ?>
                                    <img src="<?= htmlspecialchars($s['cover_image']) ?>" class="card-img" alt="Cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-slate-800 flex items-center justify-center text-[var(--c-gold)] opacity-30">
                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-3 flex justify-between items-center">
                                <span class="badge-cat"><?= htmlspecialchars($s['cat_name'] ?? 'Uncategorized') ?></span>
                                <span class="text-xs text-slate-500"><?= date('M d, Y', strtotime($s['created_at'])) ?></span>
                            </div>
                            <h3 class="font-display text-xl font-bold text-navy mb-1 group-hover:text-[var(--c-gold)] transition-colors line-clamp-2">
                                <?= htmlspecialchars($s['title']) ?>
                            </h3>
                            <p class="text-sm text-slate-500 mb-4 font-medium flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <?= htmlspecialchars($s['preacher']) ?>
                            </p>
                            
                            <?php if ($tab === 'written'): ?>
                                <p class="text-sm text-slate-600 line-clamp-2 mb-6 flex-grow"><?= htmlspecialchars($s['description']) ?></p>
                            <?php else: ?>
                                <div class="flex-grow"></div>
                            <?php endif; ?>

                            <a href="sermon.php?id=<?= $s['id'] ?>&type=<?= $tab ?>" class="inline-flex items-center justify-center gap-2 w-full bg-[var(--c-gold-pale)] border border-[var(--c-gold)]/30 text-[var(--c-gold)] hover:bg-[var(--c-gold)] hover:text-white font-bold py-3 rounded-lg transition-all text-sm uppercase tracking-wider mt-auto">
                                <?= $tab === 'written' ? 'Read Sermon' : 'Watch Video' ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20 bg-[var(--c-surface)] border border-[var(--c-border)] rounded-2xl">
                <svg class="w-16 h-16 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="font-display text-2xl font-bold text-navy mb-2">No Sermons Found</h3>
                <p class="text-slate-500">Try adjusting your search filters or check back later.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
