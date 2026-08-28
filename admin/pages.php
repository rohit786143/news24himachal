<?php
/**
 * Admin Static Pages CMS (About Us, Disclaimer, Privacy Policy, Terms)
 * Himachal News - Khabar 24
 */

$adminTitle = 'Static Pages';
$adminHeading = 'Static Pages Management (CMS)';

require_once __DIR__ . '/includes/header.php';

// Fetch all CMS pages
$pages = $pdo->query("SELECT * FROM `pages` ORDER BY `id` ASC")->fetchAll();

// Map slugs to frontend view files
$frontendLinks = [
    'about' => '/about.php',
    'disclaimer' => '/disclaimer.php',
    'privacy-policy' => '/privacy-policy.php',
    'terms' => '/terms.php',
];
?>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i class="fas fa-file-contract"></i> All Static Pages
        </h2>
        <a href="/admin/page-edit.php" class="topbar-btn" style="background: var(--accent-green); border-color: var(--accent-green);">
            <i class="fas fa-plus"></i> Add New Page
        </a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Page Title</th>
                    <th>URL Slug</th>
                    <th>Meta Description</th>
                    <th>Last Updated</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td>
                            <strong style="color: var(--text-heading); font-size: 0.95rem;"><?= sanitize($p['title']) ?></strong>
                            <div style="font-size: 0.75rem; color: var(--text-dim);">
                                <?= mb_strlen(strip_tags($p['content']), 'UTF-8') ?> characters
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-blue">
                                <?= sanitize($p['slug']) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.82rem; color: var(--text-muted); max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= sanitize($p['meta_description'] ?? 'No description') ?>
                        </td>
                        <td style="font-size: 0.8rem; color: var(--text-dim);">
                            <?= date('d M Y, h:i A', strtotime($p['updated_at'])) ?>
                        </td>
                        <td>
                            <div class="action-btns" style="justify-content: flex-end;">
                                <?php $liveUrl = $frontendLinks[$p['slug']] ?? ('/page.php?slug=' . urlencode($p['slug'])); ?>
                                <a href="<?= $liveUrl ?>" target="_blank" class="btn-icon" title="View Live Website">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="/admin/page-edit.php?id=<?= $p['id'] ?>" class="btn-icon btn-icon-edit" title="Edit Content">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Helpful guidance box -->
<div class="panel" style="background: #F0F9FF; border: 1px solid #BAE6FD;">
    <div class="panel-body">
        <h4 style="color: var(--accent-blue); font-size: 0.95rem; margin-bottom: 6px; font-weight: 700;">
            <i class="fas fa-circle-info"></i> How Static Pages Work
        </h4>
        <p style="font-size: 0.84rem; color: var(--text-muted);">
            Manage and edit content for <strong>About Us, Disclaimer, Privacy Policy, Terms & Conditions</strong> and other static pages. Changes take effect immediately on the live website.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
