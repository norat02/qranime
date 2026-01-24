<?php
require_once 'auth.php';
checkLogin();

if (!isset($_GET['tpl'])) {
    die("Missing template name");
}
$tpl = $_GET['tpl'];
$tplDir = __DIR__ . '/../template/' . $tpl;
$configFile = $tplDir . '/config.json';
$bgFile = '../template/' . $tpl . '/bg.jpg';

// Default Config
$defaultConfig = [
    'qr' => ['x' => 100, 'y' => 200, 'size' => 300],
    'bank_name' => ['x' => 100, 'y' => 550, 'size' => 20, 'color' => '#000000', 'align' => 'left', 'font' => 'default'],
    'acc_num' => ['x' => 100, 'y' => 600, 'size' => 20, 'color' => '#000000', 'align' => 'left', 'font' => 'default'],
    'acc_name' => ['x' => 100, 'y' => 650, 'size' => 20, 'color' => '#000000', 'align' => 'left', 'font' => 'default']
];

$config = $defaultConfig;
if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if ($loaded) {
        $config = array_replace_recursive($defaultConfig, $loaded);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Template: <?php echo htmlspecialchars($tpl); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #e9ecef;
            height: 100vh;
            overflow: hidden;
        }

        .editor-container {
            display: flex;
            height: 100%;
        }

        .preview-area {
            flex: 1;
            position: relative;
            overflow: auto;
            background: #333;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .sidebar {
            width: 350px;
            background: white;
            border-left: 1px solid #ddd;
            overflow-y: auto;
            padding: 20px;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Canvas Layer */
        #canvasParams {
            position: relative;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }

        #canvasParams img {
            display: block;
            max-width: none;
        }

        /* Show full size usually */

        /* Draggables */
        .drag-box {
            position: absolute;
            border: 2px dashed rgba(255, 0, 0, 0.7);
            background: rgba(255, 255, 255, 0.3);
            cursor: grab;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: red;
            user-select: none;
            text-shadow: 0 0 2px white;
        }

        .drag-box:active {
            cursor: grabbing;
            border-color: red;
            background: rgba(255, 255, 0, 0.2);
        }

        .qr-box {
            background: rgba(0, 255, 0, 0.2);
            border-color: green;
            color: green;
        }
    </style>
</head>

<body>

    <div class="editor-container">

        <!-- Preview Area -->
        <div class="preview-area" id="panArea">
            <div id="canvasParams">
                <img src="<?php echo $bgFile; ?>" id="bgImg" onload="initEditor()">
                <!-- Elements will be injected here -->
            </div>
        </div>

        <!-- Controls Sidebar -->
        <div class="sidebar">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Edit: <?php echo $tpl; ?></h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>

            <form id="configForm">
                <input type="hidden" name="tpl" value="<?php echo $tpl; ?>">

                <!-- QR Config -->
                <div class="card mb-3">
                    <div class="card-header bg-success text-white py-1">QR Code</div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="small">X</label>
                                <input type="number" class="form-control form-control-sm" id="qr_x" name="qr[x]" value="<?php echo $config['qr']['x']; ?>">
                            </div>
                            <div class="col-4">
                                <label class="small">Y</label>
                                <input type="number" class="form-control form-control-sm" id="qr_y" name="qr[y]" value="<?php echo $config['qr']['y']; ?>">
                            </div>
                            <div class="col-4">
                                <label class="small">Size</label>
                                <input type="number" class="form-control form-control-sm" id="qr_size" name="qr[size]" value="<?php echo $config['qr']['size']; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Name Config -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-1">Bank Name</div>
                    <div class="card-body p-2">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="small">X</label>
                                <input type="number" class="form-control form-control-sm" id="bank_name_x" name="bank_name[x]" value="<?php echo $config['bank_name']['x']; ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Y</label>
                                <input type="number" class="form-control form-control-sm" id="bank_name_y" name="bank_name[y]" value="<?php echo $config['bank_name']['y']; ?>">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Size</label>
                                <input type="number" class="form-control form-control-sm" id="bank_name_size" name="bank_name[size]" value="<?php echo $config['bank_name']['size']; ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Color</label>
                                <input type="color" class="form-control form-control-sm form-control-color w-100" id="bank_name_color" name="bank_name[color]" value="<?php echo $config['bank_name']['color']; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acc Number Config -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white py-1">Account Number</div>
                    <div class="card-body p-2">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="small">X</label>
                                <input type="number" class="form-control form-control-sm" id="acc_num_x" name="acc_num[x]" value="<?php echo $config['acc_num']['x']; ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Y</label>
                                <input type="number" class="form-control form-control-sm" id="acc_num_y" name="acc_num[y]" value="<?php echo $config['acc_num']['y']; ?>">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Size</label>
                                <input type="number" class="form-control form-control-sm" id="acc_num_size" name="acc_num[size]" value="<?php echo $config['acc_num']['size']; ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Color</label>
                                <input type="color" class="form-control form-control-sm form-control-color w-100" id="acc_num_color" name="acc_num[color]" value="<?php echo $config['acc_num']['color']; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary w-100 fw-bold py-2" onclick="saveConfig()">
                    <i class="bi bi-save"></i> Save Changes
                </button>
                <div id="statusMsg" class="mt-2 text-center small fw-bold"></div>
            </form>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('canvasParams');
        const inputs = {
            qr: {
                x: document.getElementById('qr_x'),
                y: document.getElementById('qr_y'),
                size: document.getElementById('qr_size'),
                el: null
            },
            bank_name: {
                x: document.getElementById('bank_name_x'),
                y: document.getElementById('bank_name_y'),
                size: null,
                el: null
            },
            acc_num: {
                x: document.getElementById('acc_num_x'),
                y: document.getElementById('acc_num_y'),
                size: null,
                el: null
            },
        };

        function initEditor() {
            createDraggable('qr', 'QR Code', 'qr-box');
            createDraggable('bank_name', 'Bank Name', '');
            createDraggable('acc_num', 'Account No', '');
            updateVisuals();
        }

        function createDraggable(key, label, extraClass) {
            const el = document.createElement('div');
            el.className = `drag-box ${extraClass}`;
            el.textContent = label;
            el.id = `box_${key}`;

            let isDragging = false;
            let startX, startY, initialLeft, initialTop;

            el.addEventListener('mousedown', e => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                initialLeft = parseInt(el.style.left || 0);
                initialTop = parseInt(el.style.top || 0);
                el.style.cursor = 'grabbing';
            });

            window.addEventListener('mousemove', e => {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;

                let newX = initialLeft + dx;
                let newY = initialTop + dy;

                // Update DOM position
                el.style.left = newX + 'px';
                el.style.top = newY + 'px';

                // Update Inputs
                if (inputs[key].x) inputs[key].x.value = newX;
                if (inputs[key].y) inputs[key].y.value = newY;
            });

            window.addEventListener('mouseup', () => {
                isDragging = false;
                el.style.cursor = 'grab';
            });

            canvas.appendChild(el);
            inputs[key].el = el;

            // Listen to Input Changes to update visual
            if (inputs[key].x) inputs[key].x.addEventListener('input', updateVisuals);
            if (inputs[key].y) inputs[key].y.addEventListener('input', updateVisuals);
            if (inputs[key].size) inputs[key].size.addEventListener('input', updateVisuals);
        }

        function updateVisuals() {
            for (const key in inputs) {
                const group = inputs[key];
                if (!group.el) continue;

                const x = parseInt(group.x.value) || 0;
                const y = parseInt(group.y.value) || 0;

                group.el.style.left = x + 'px';
                group.el.style.top = y + 'px';

                if (key === 'qr') {
                    const s = parseInt(group.size.value) || 100;
                    group.el.style.width = s + 'px';
                    group.el.style.height = s + 'px';
                } else {
                    // For text, just padding
                    group.el.style.padding = '5px 10px';
                    group.el.style.whiteSpace = 'nowrap';
                }
            }
        }

        async function saveConfig() {
            const btn = document.querySelector('button[onclick="saveConfig()"]');
            const msg = document.getElementById('statusMsg');
            btn.disabled = true;
            msg.textContent = 'Saving...';
            msg.className = 'mt-2 text-center small fw-bold text-muted';

            const fd = new FormData(document.getElementById('configForm'));
            try {
                const res = await fetch('save_config.php', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();
                if (json.success) {
                    msg.textContent = 'Saved Successfully!';
                    msg.className = 'mt-2 text-center small fw-bold text-success';
                } else {
                    msg.textContent = 'Error: ' + json.error;
                    msg.className = 'mt-2 text-center small fw-bold text-danger';
                }
            } catch (e) {
                msg.textContent = 'Connect Error';
                msg.className = 'mt-2 text-center small fw-bold text-danger';
            }
            btn.disabled = false;
        }
    </script>

</body>

</html>