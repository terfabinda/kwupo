<?php
header('Content-Type: application/xml; charset=utf-8');

// Database connection
require 'includes/config';

// Base URL
$base_url = 'https://kwupo.org.ng';

// Static pages (public, no login required)
$static_pages = [
    ['path' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['path' => '/about-us', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['path' => '/news-and-events', 'priority' => '0.7', 'changefreq' => 'weekly'],
    ['path' => '/gallery', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['path' => '/execcomittee', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['path' => '/contact', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ['path' => '/signup', 'priority' => '0.6', 'changefreq' => 'yearly'],
    ['path' => '/signin', 'priority' => '0.6', 'changefreq' => 'yearly'],
    ['path' => '/privacy-policy', 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['path' => '/terms', 'priority' => '0.4', 'changefreq' => 'yearly'],
];

// Fetch news/events
$news_pages = [];
$news_result = mysqli_query($conn, "SELECT news_id, created_at FROM news_events WHERE is_published = 1 ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($news_result)) {
    $news_pages[] = [
        'path' => '/news/' . $row['news_id'],
        'lastmod' => $row['created_at'],
        'priority' => '0.7',
        'changefreq' => 'weekly'
    ];
}

// Fetch press releases
$press_pages = [];
$press_result = mysqli_query($conn, "SELECT slug, created_at FROM press_releases ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($press_result)) {
    $press_pages[] = [
        'path' => '/press/' . $row['slug'],
        'lastmod' => $row['created_at'],
        'priority' => '0.6',
        'changefreq' => 'monthly'
    ];
}

// Combine all pages
$all_pages = array_merge($static_pages, $news_pages, $press_pages);
?>

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($all_pages as $page): ?>
  <url>
    <loc><?= htmlspecialchars($base_url . $page['path']) ?></loc>
    <?php if (!empty($page['lastmod'])): ?>
      <lastmod><?= date('Y-m-d', strtotime($page['lastmod'])) ?></lastmod>
    <?php else: ?>
      <lastmod>2025-10-24</lastmod>
    <?php endif; ?>
    <changefreq><?= $page['changefreq'] ?></changefreq>
    <priority><?= $page['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>