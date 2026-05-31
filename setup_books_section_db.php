<?php
// setup_books_section_db.php — Migrate categories table & seed book categories
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
$db = getDB();

echo "=== Advent Library — Books Section DB Migration ===\n\n";

// ── 1. Add new columns to categories ─────────────────────────────
$cols = [
    'slug'      => "VARCHAR(120) NOT NULL DEFAULT ''",
    'icon'      => "VARCHAR(10)  NOT NULL DEFAULT '📚'",
    'color'     => "VARCHAR(20)  NOT NULL DEFAULT '#1E3A5F'",
    'thumbnail' => "VARCHAR(300) NOT NULL DEFAULT ''",
    'sort_order'=> "INT NOT NULL DEFAULT 0",
];
foreach ($cols as $col => $def) {
    try {
        $db->exec("ALTER TABLE categories ADD COLUMN `$col` $def");
        echo "Added column: $col\n";
    } catch (PDOException $e) {
        echo "Column '$col' already exists (skipped)\n";
    }
}

// ── 2. Build slugs for existing categories without one ───────────
$rows = $db->query("SELECT id, name, slug FROM categories")->fetchAll();
$slugStmt = $db->prepare("UPDATE categories SET slug=? WHERE id=?");
foreach ($rows as $row) {
    if (empty($row['slug'])) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $row['name']), '-'));
        $slugStmt->execute([$slug, $row['id']]);
        echo "Set slug for '{$row['name']}': $slug\n";
    }
}

// ── 3. Ensure unique index on slug ────────────────────────────────
try {
    $db->exec("ALTER TABLE categories ADD UNIQUE KEY `uq_cat_slug` (`slug`)");
    echo "Added unique index on slug\n";
} catch (PDOException $e) {
    echo "Unique index on slug already exists (skipped)\n";
}

// ── 4. Upsert the full category set with icons & colours ─────────
$presets = [
    ['Spirit of Prophecy',    'spirit-of-prophecy',    '✨', '#6D28D9', 0],
    ['Bible Commentaries',    'bible-commentaries',    '📖', '#1D4ED8', 1],
    ['Sabbath School Lessons','sabbath-school-lessons', '🕊️', '#065F46', 2],
    ['Devotionals',           'devotionals',           '🙏', '#B45309', 3],
    ['Health Books',          'health-books',          '💚', '#15803D', 4],
    ['Family & Marriage',     'family-marriage',       '👨‍👩‍👧', '#B91C1C', 5],
    ['Prophecy',              'prophecy',              '🔮', '#7C3AED', 6],
    ['Youth Books',           'youth-books',           '⭐', '#0284C7', 7],
    ['Sermons',               'sermons',               '🎤', '#9A3412', 8],
    ['Bible Study Guides',    'bible-study-guides',    '📝', '#1E3A5F', 9],
    ['Theology',              'theology',              '✝️', '#374151', 10],
    ['Biography',             'biography',             '👤', '#78350F', 11],
    ['Ellen G. White',        'ellen-g-white',         '📜', '#4C1D95', 12],
    ['Health & Lifestyle',    'health-lifestyle',      '🌿', '#064E3B', 13],
    ['Evangelism',            'evangelism',            '🌍', '#1E3A5F', 14],
    ['Family',                'family',                '🏠', '#92400E', 15],
    ['Youth',                 'youth',                 '🌟', '#1D4ED8', 16],
    ['Church History',        'church-history',        '⛪', '#374151', 17],
    ['Prayer & Faith',        'prayer-faith',          '🙌', '#701A75', 18],
];

$upsert = $db->prepare("
    INSERT INTO categories (name, slug, icon, color, sort_order)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        slug  = IF(slug='' OR slug IS NULL, VALUES(slug), slug),
        icon  = IF(icon='📚' OR icon IS NULL, VALUES(icon), icon),
        color = IF(color='#1E3A5F' OR color IS NULL, VALUES(color), color),
        sort_order = VALUES(sort_order)
");
foreach ($presets as [$name, $slug, $icon, $color, $order]) {
    $upsert->execute([$name, $slug, $icon, $color, $order]);
}
echo "Upserted " . count($presets) . " categories with icons & slugs\n";

// ── 5. Ensure uploads/categories directory exists ─────────────────
$dir = __DIR__ . '/uploads/categories';
if (!is_dir($dir)) { mkdir($dir, 0755, true); echo "Created $dir\n"; }

echo "\n✅ Books section DB migration complete!\n";
