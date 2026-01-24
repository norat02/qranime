<?php
require_once 'auth.php';
checkLogin();

$templateDir = __DIR__ . '/../template';
$message = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. ADD NEW TEMPLATE
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['tpl_name']));
        $newDir = $templateDir . '/' . $name;

        if (is_dir($newDir)) {
            $message = '<div class="alert alert-danger">Template name already exists!</div>';
        } else {
            mkdir($newDir, 0777, true);

            // Upload BG
            if (isset($_FILES['tpl_bg']) && $_FILES['tpl_bg']['error'] === 0) {
                move_uploaded_file($_FILES['tpl_bg']['tmp_name'], $newDir . '/bg.jpg');
            }

            // Copy Default Index PHP (We use a simple skeleton here)
            // For now, we COPY logic from 'new' template as a base, or create a simple one
            // Let's create a generic index.php ensuring paths are dynamically handled if possible
            // Or simpler: We assume user will modify it. Let's copy from 'blue' as a safe base.
            $baseTpl = $templateDir . '/blue/index.php';
            if (file_exists($baseTpl)) {
                copy($baseTpl, $newDir . '/index.php');
            } else {
                // Fallback simple content if 'blue' doesn't exist
                file_put_contents($newDir . '/index.php', '<?php include "../../test_text.php"; ?>');
            }

            $message = '<div class="alert alert-success">Template created successfully!</div>';
        }
    }

    // 2. DELETE TEMPLATE
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $tplToDelete = $_POST['tpl_name'];
        $targetDir = $templateDir . '/' . $tplToDelete;
        if (is_dir($targetDir) && $tplToDelete !== '.' && $tplToDelete !== '..') {
            deleteDir($targetDir);
            $message = '<div class="alert alert-success">Template deleted!</div>';
        }
    }
}

// Scan Templates
$templates = [];
if (is_dir($templateDir)) {
    $scanned = array_diff(scandir($templateDir), array('..', '.'));
    foreach ($scanned as $folder) {
        if (is_dir($templateDir . '/' . $folder)) {
            $templates[] = $folder;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Manager - QR Decor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .tpl-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .card-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            opacity: 0;
            transition: 0.2s;
        }

        .card:hover .card-actions {
            opacity: 1;
        }
    </style>
</head>

<body class="bg-light p-4">

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-grid-1x2-fill text-primary"></i> Template Manager</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg"></i> Add New
            </button>
        </div>

        <?php echo $message; ?>

        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-5 g-3">
            <?php foreach ($templates as $tpl): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm position-relative">
                        <div class="card-body p-2 text-center" style="cursor: pointer;" onclick="location.href='edit.php?tpl=<?php echo $tpl; ?>'">
                            <img src="../template/<?php echo $tpl; ?>/bg.jpg?v=<?php echo time(); ?>" class="tpl-thumb mb-2" onerror="this.src='https://via.placeholder.com/150'">
                            <h6 class="card-title fw-bold text-capitalize mb-0"><?php echo $tpl; ?></h6>
                        </div>
                        <div class="card-footer bg-white border-top-0 p-2">
                            <div class="d-flex gap-2">
                                <a href="edit.php?tpl=<?php echo $tpl; ?>" class="btn btn-outline-primary btn-sm w-50"><i class="bi bi-pencil"></i> Edit</a>
                                <form method="POST" onsubmit="return confirm('Delete this template?');" class="w-50">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="tpl_name" value="<?php echo $tpl; ?>">
                                    <button class="btn btn-danger btn-sm w-100"><i class="bi bi-trash"></i> Del</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Template Name (Folder)</label>
                        <input type="text" name="tpl_name" class="form-control" required placeholder="e.g. pink_anime">
                        <div class="form-text">No spaces, special chars.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Background Image</label>
                        <input type="file" name="tpl_bg" class="form-control" required accept="image/*">
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle"></i> A default `index.php` logic from 'blue' template will be copied.
                        You may need to edit it manually via FTP/Code Editor for positioning.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>