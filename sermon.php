<?php
// sermon.php — Single Sermon View
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'written'; // 'written' or 'video'

if (!$id) {
    header('Location: sermons.php');
    exit;
}

if ($type === 'written') {
    $db->prepare('UPDATE written_sermons SET views = views + 1 WHERE id = ?')->execute([$id]);
    $stmt = $db->prepare("SELECT s.*, c.name as cat_name FROM written_sermons s LEFT JOIN sermon_categories c ON s.category_id = c.id WHERE s.id = ?");
    $stmt->execute([$id]);
    $sermon = $stmt->fetch();
    
    // Fetch related
    if ($sermon) {
        $stmt_rel = $db->prepare("SELECT id, title, cover_image FROM written_sermons WHERE category_id = ? AND id != ? ORDER BY created_at DESC LIMIT 3");
        $stmt_rel->execute([$sermon['category_id'], $id]);
        $related = $stmt_rel->fetchAll();
    }
} else {
    $db->prepare('UPDATE video_sermons SET views = views + 1 WHERE id = ?')->execute([$id]);
    $stmt = $db->prepare("SELECT s.*, c.name as cat_name FROM video_sermons s LEFT JOIN sermon_categories c ON s.category_id = c.id WHERE s.id = ?");
    $stmt->execute([$id]);
    $sermon = $stmt->fetch();
    
    // Fetch related
    if ($sermon) {
        $stmt_rel = $db->prepare("SELECT id, title, youtube_id FROM video_sermons WHERE category_id = ? AND id != ? ORDER BY created_at DESC LIMIT 3");
        $stmt_rel->execute([$sermon['category_id'], $id]);
        $related = $stmt_rel->fetchAll();
    }
}

if (!$sermon) {
    header('Location: sermons.php');
    exit;
}
?>
<?php
$pageTitle = $sermon['title'] . ' — Advent Library';
include __DIR__ . '/includes/header.php';
?>
    <!-- html2pdf for PDF generation -->
    <?php if ($type === 'written'): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <?php endif; ?>

    <style type="text/tailwindcss">
        :root {
            --c-bg: theme('colors.soft-white');
            --c-surface: #ffffff;
            --c-border: rgba(30, 58, 95, 0.1);
            --c-gold: theme('colors.gold');
        }

        .sermon-content { font-size: 1.1rem; line-height: 1.8; color: theme('colors.slate.700'); }
        .sermon-content p { margin-bottom: 1.5rem; }
        .sermon-content h1, .sermon-content h2, .sermon-content h3 { font-family: 'Montserrat', sans-serif; text-transform: uppercase; letter-spacing: -0.025em; color: theme('colors.navy'); margin-top: 2rem; margin-bottom: 1rem; font-weight: 700; }
        
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 1rem; border: 1px solid var(--c-border); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.05); }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }
        
        /* Specific print styles for the PDF */
        #pdf-content { background-color: white; color: black; padding: 40px; }
        #pdf-content h1 { color: black; font-size: 32px; font-weight: bold; margin-bottom: 10px; font-family: Georgia, serif; }
        #pdf-content .meta { color: #666; font-size: 14px; margin-bottom: 30px; font-family: sans-serif; }
        #pdf-content .body-text { font-size: 14px; line-height: 1.6; font-family: Georgia, serif; }
        #pdf-content .body-text p { margin-bottom: 15px; }
    </style>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="py-24 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <a href="sermons.php?tab=<?= $type ?>" class="inline-flex items-center text-slate-500 hover:text-[var(--c-gold)] text-sm font-medium mb-8 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Sermons
        </a>

        <!-- Header -->
        <header class="mb-10 text-center">
            <span class="inline-block px-3 py-1 rounded-full bg-[var(--c-gold)]/10 text-[var(--c-gold)] text-xs font-bold uppercase tracking-widest mb-4">
                <?= htmlspecialchars($sermon['cat_name'] ?? 'Sermon') ?>
            </span>
            <h1 class="font-display text-4xl sm:text-5xl font-bold text-navy leading-tight mb-4">
                <?= htmlspecialchars($sermon['title']) ?>
            </h1>
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm text-slate-500 font-medium">
                <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> <?= htmlspecialchars($sermon['preacher']) ?></span>
                <span>•</span>
                <span><?= date('F d, Y', strtotime($sermon['created_at'])) ?></span>
                <span>•</span>
                <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> <?= $sermon['views'] ?> views</span>
            </div>
        </header>

        <!-- Actions -->
        <div class="flex items-center justify-center gap-4 mb-10 pb-8 border-b border-[var(--c-border)]">
            <?php if ($type === 'written'): ?>
                <button onclick="downloadPDF()" class="bg-[var(--c-surface)] hover:bg-soft-white border border-[var(--c-border)] text-navy px-6 py-2.5 rounded-lg text-sm font-bold flex items-center gap-2 transition-all shadow-sm">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download as PDF
                </button>
            <?php endif; ?>
            <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied!');" class="bg-[var(--c-surface)] hover:bg-soft-white border border-[var(--c-border)] text-navy px-6 py-2.5 rounded-lg text-sm font-bold flex items-center gap-2 transition-all shadow-sm">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                Share
            </button>
        </div>

        <!-- Content Area -->
        <?php if ($type === 'video'): ?>
            <div class="video-container mb-16">
                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($sermon['youtube_id']) ?>?autoplay=1&rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        <?php else: ?>
            <?php if ($sermon['cover_image']): ?>
                <img src="<?= htmlspecialchars($sermon['cover_image']) ?>" class="w-full h-auto rounded-xl mb-12 shadow-2xl border border-[var(--c-border)]" alt="Cover">
            <?php endif; ?>
            
            <article class="sermon-content bg-[var(--c-surface)] p-8 sm:p-12 rounded-2xl border border-[var(--c-border)] shadow-xl relative z-10">
                <?= nl2br(htmlspecialchars($sermon['content'])) ?>
            </article>

            <!-- Hidden template used strictly for PDF generation to ensure clean black/white formatting -->
            <div id="hidden-pdf-template" style="display: none;">
                <div id="pdf-content">
                    <h1><?= htmlspecialchars($sermon['title']) ?></h1>
                    <div class="meta">
                        Preached by: <strong><?= htmlspecialchars($sermon['preacher']) ?></strong><br>
                        Date: <?= date('F d, Y', strtotime($sermon['created_at'])) ?><br>
                        Category: <?= htmlspecialchars($sermon['cat_name'] ?? 'Uncategorized') ?>
                    </div>
                    <hr style="margin-bottom:20px; border:0; border-top:1px solid #ccc;">
                    <div class="body-text">
                        <?= nl2br(htmlspecialchars($sermon['content'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Related Sermons -->
        <?php if (!empty($related)): ?>
            <div class="mt-24">
                <h3 class="font-display text-2xl font-bold text-navy mb-8 border-b border-[var(--c-border)] pb-4">Related Sermons</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <?php foreach ($related as $rel): ?>
                        <a href="sermon.php?id=<?= $rel['id'] ?>&type=<?= $type ?>" class="group block bg-[var(--c-surface)] border border-[var(--c-border)] rounded-xl overflow-hidden hover:border-[var(--c-gold)] transition-colors shadow-sm">
                            <div class="aspect-video w-full overflow-hidden bg-slate-100">
                                <?php if ($type === 'video'): ?>
                                    <img src="https://img.youtube.com/vi/<?= htmlspecialchars($rel['youtube_id']) ?>/mqdefault.jpg" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                <?php else: ?>
                                    <?php if ($rel['cover_image']): ?>
                                        <img src="<?= htmlspecialchars($rel['cover_image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h4 class="font-bold text-sm text-navy group-hover:text-[var(--c-gold)] transition-colors line-clamp-2"><?= htmlspecialchars($rel['title']) ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php if ($type === 'written'): ?>
<script>
    function downloadPDF() {
        const element = document.getElementById('pdf-content');
        
        // Temporarily display to render, then hide
        document.getElementById('hidden-pdf-template').style.display = 'block';
        
        var opt = {
            margin:       1,
            filename:     '<?= addslashes($sermon['title']) ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        // Generate PDF
        html2pdf().set(opt).from(element).save().then(() => {
            document.getElementById('hidden-pdf-template').style.display = 'none';
        });
    }
</script>
<?php endif; ?>

</body>
</html>

