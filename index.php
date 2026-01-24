<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Scan for available templates
$templates = [];
$dir = __DIR__ . '/template';
if (is_dir($dir)) {
    $scanned = array_diff(scandir($dir), array('..', '.'));
    foreach ($scanned as $folder) {
        if (is_dir($dir . '/' . $folder)) {
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
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">

    <!-- SEO Meta Tags -->
    <meta name="description" content="Tạo mã QR Code ngân hàng (VietQR) và QR hình ảnh đẹp mắt với nhiều mẫu template độc đáo phong cách Anime, Decor. Công cụ miễn phí, nhanh chóng tại QR Decor Studio.">
    <meta name="keywords" content="qr code, tạo qr code, vietqr, qr ngân hàng, qr decor, qr anime, tạo mã qr đẹp, qr code generator, qr template">
    <meta name="author" content="QR Decor Studio">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://qranime.com/">
    <meta property="og:title" content="QR Decor Studio - Tạo QR Code Ngân Hàng Đẹp Mắt">
    <meta property="og:description" content="Công cụ tạo mã QR ngân hàng và QR hình ảnh với hàng trăm mẫu template đẹp, phong cách Anime & Decor.">
    <meta property="og:image" content="https://qranime.com/assets/images/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://qranime.com/">
    <meta property="twitter:title" content="QR Decor Studio - Tạo QR Code Ngân Hàng Đẹp Mắt">
    <meta property="twitter:description" content="Công cụ tạo mã QR ngân hàng và QR hình ảnh với hàng trăm mẫu template đẹp, phong cách Anime & Decor.">
    <meta property="twitter:image" content="https://qranime.com/assets/images/og-image.jpg">

    <title>QR Decor Studio - Tạo Mã QR Đẹp</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- QRCode Lib -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div id="dabilux-nuoi-than-widget" class="dabilux-nuoi-than-container">

        <!-- Nội dung ẩn (QR Code) -->
        <div class="dabilux-nuoi-than-popup-content">
            <img src="https://img.vietqr.io/image/mbbank-066775-compact2.jpg?addInfo=donate%5C&accountName=Nguyen%20Trong%20Thao" alt="QR Code" class="dabilux-nuoi-than-qr-image">
            <p class="dabilux-nuoi-than-donate-text">Chủ web đói quá<br>xin được nuôi 🍜</p>
        </div>

        <!-- Nút kích hoạt -->
        <div class="dabilux-nuoi-than-trigger-wrapper" onclick="dabiluxNuoiThanToggle()">
            <img src="https://wordpress.thompbui.com/wp-content/uploads/2025/12/an-xin.png" alt="Support Me"
                class="dabilux-nuoi-than-trigger-btn">
            <span class="dabilux-nuoi-than-cta-text">Kích vào em đi</span>
        </div>

    </div>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="bg-primary text-white rounded px-2 py-1 me-2 fs-6"><i class="bi bi-qr-code"></i></span>
                QR Decor Studio
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="input-group d-none d-md-flex" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 bg-light shadow-none" placeholder="Search templates...">
                </div>
                <a href="#" class="btn btn-sm btn-outline-primary fw-bold">Login</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-4">
        <div class="row g-4">

            <!-- Left: Templates Grid -->
            <div class="col-lg-8">

                <!-- Section Header -->
                <div class="section-header shadow-sm">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-grid-fill text-primary"></i>
                        <h2>Templates Gallery</h2>
                    </div>
                    <span class="badge-count"><?php echo count($templates); ?> Styles</span>
                </div>

                <!-- Filters (Mockup) -->
                <div class="mb-4 d-flex gap-2 overflow-auto pb-2">
                    <button class="btn btn-sm btn-dark rounded-pill px-3">All</button>
                    <button class="btn btn-sm btn-light border rounded-pill px-3">Popular</button>
                    <button class="btn btn-sm btn-light border rounded-pill px-3">New</button>
                    <button class="btn btn-sm btn-light border rounded-pill px-3">Anime</button>
                </div>

                <!-- Grid -->
                <div class="row row-cols-2 row-cols-md-3 g-3">
                    <?php foreach ($templates as $tpl): ?>
                        <div class="col">
                            <div class="template-card h-100" onclick="selectTemplate(this, '<?php echo $tpl; ?>')">
                                <img src="template/<?php echo $tpl; ?>/bg.jpg" alt="<?php echo $tpl; ?>" loading="lazy">
                                <div class="card-body">
                                    <h6 class="template-title text-truncate"><?php echo ucfirst($tpl); ?></h6>
                                    <small class="text-muted" style="font-size: 0.75rem;">by Admin</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Right: Config Sidebar -->
            <div class="col-lg-4">
                <div class="config-box p-4">
                    <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-sliders2"></i> Configuration
                    </h5>

                    <!-- Tabs -->
                    <div class="overflow-auto pb-2 mb-4">
                        <ul class="nav nav-pills flex-nowrap" id="pills-tab" role="tablist" style="gap: 5px;">
                            <li class="nav-item">
                                <button class="nav-link small active text-nowrap" id="pills-bank-tab" data-bs-toggle="pill" data-bs-target="#pills-bank" type="button">
                                    <i class="bi bi-bank me-1"></i> Bank
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-url-tab" data-bs-toggle="pill" data-bs-target="#pills-url" type="button">
                                    <i class="bi bi-globe me-1"></i> Website
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-text-tab" data-bs-toggle="pill" data-bs-target="#pills-text" type="button">
                                    <i class="bi bi-fonts me-1"></i> Text
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-wifi-tab" data-bs-toggle="pill" data-bs-target="#pills-wifi" type="button">
                                    <i class="bi bi-wifi me-1"></i> Wifi
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-phone-tab" data-bs-toggle="pill" data-bs-target="#pills-phone" type="button">
                                    <i class="bi bi-telephone me-1"></i> Phone
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-sms-tab" data-bs-toggle="pill" data-bs-target="#pills-sms" type="button">
                                    <i class="bi bi-chat-text me-1"></i> SMS
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-email-tab" data-bs-toggle="pill" data-bs-target="#pills-email" type="button">
                                    <i class="bi bi-envelope me-1"></i> Email
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small text-nowrap" id="pills-upload-tab" data-bs-toggle="pill" data-bs-target="#pills-upload" type="button">
                                    <i class="bi bi-image me-1"></i> Image
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content mb-4">

                        <!-- Bank Tab -->
                        <div class="tab-pane fade show active" id="pills-bank">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Bank</label>
                                    <select class="form-select" id="bankBin">
                                        <option value="970422">MB Bank</option>
                                        <option value="970436">Vietcombank</option>
                                        <option value="970415">VietinBank</option>
                                        <option value="970418">BIDV</option>
                                        <option value="970405">Agribank</option>
                                        <option value="970423">TPBank</option>
                                        <option value="970407">Techcombank</option>
                                        <option value="970432">VPBank</option>
                                        <option value="970403">Sacombank</option>
                                        <option value="970416">ACB</option>
                                    </select>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-secondary">Account No.</label>
                                        <input type="text" class="form-control" id="bankAccount" placeholder="123456789">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-secondary">Amount</label>
                                        <input type="number" class="form-control" id="bankAmount" placeholder="Optional">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-secondary">Content</label>
                                        <input type="text" class="form-control" id="bankContent" placeholder="Optional">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- URL Tab -->
                        <div class="tab-pane fade" id="pills-url">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Website URL</label>
                                    <input type="url" class="form-control" id="urlInput" placeholder="https://example.com">
                                </div>
                            </div>
                        </div>

                        <!-- Text Tab -->
                        <div class="tab-pane fade" id="pills-text">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Your Text</label>
                                    <textarea class="form-control" id="textInput" rows="3" placeholder="Enter content here..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Wifi Tab -->
                        <div class="tab-pane fade" id="pills-wifi">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Network Name (SSID)</label>
                                    <input type="text" class="form-control" id="wifiSsid" placeholder="MyWiFi">
                                </div>
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Password</label>
                                    <input type="text" class="form-control" id="wifiPass" placeholder="Password">
                                </div>
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Encryption</label>
                                    <select class="form-select" id="wifiType">
                                        <option value="WPA">WPA/WPA2</option>
                                        <option value="WEP">WEP</option>
                                        <option value="nopass">No Encryption</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Phone Tab -->
                        <div class="tab-pane fade" id="pills-phone">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Phone Number</label>
                                    <input type="tel" class="form-control" id="phoneInput" placeholder="+84 912345678">
                                </div>
                            </div>
                        </div>

                        <!-- SMS Tab -->
                        <div class="tab-pane fade" id="pills-sms">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Phone Number</label>
                                    <input type="tel" class="form-control" id="smsPhone" placeholder="+84 912345678">
                                </div>
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Message</label>
                                    <textarea class="form-control" id="smsMessage" rows="2" placeholder="SMS content..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Email Tab -->
                        <div class="tab-pane fade" id="pills-email">
                            <div class="vstack gap-3">
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Email To</label>
                                    <input type="email" class="form-control" id="emailTo" placeholder="contact@example.com">
                                </div>
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Subject</label>
                                    <input type="text" class="form-control" id="emailSubject" placeholder="Inquiry">
                                </div>
                                <div>
                                    <label class="form-label small fw-bold text-secondary">Message</label>
                                    <textarea class="form-control" id="emailBody" rows="2" placeholder="Email content..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Tab -->
                        <div class="tab-pane fade" id="pills-upload">
                            <div class="drop-zone p-4 text-center" id="dropZone" onclick="document.getElementById('fileInput').click()">
                                <div class="mb-2"><i class="bi bi-cloud-arrow-up text-primary fs-3"></i></div>
                                <span class="d-block small fw-bold text-dark">Click to upload</span>
                                <small class="text-muted" style="font-size: 0.75rem;">JPG or PNG</small>
                                <input type="file" id="fileInput" accept="image/*" class="d-none">
                                <div id="fileName" class="small text-success fw-bold mt-2 text-truncate"></div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded border">
                        <span class="small text-muted fw-bold">Template:</span>
                        <span class="badge bg-primary rounded-pill" id="selectedTemplateBadge">None</span>
                    </div>

                    <button id="generateBtn" class="btn btn-primary w-100 mb-4" onclick="generateQR()" disabled>
                        <i class="bi bi-stars me-2"></i>Generate Now
                    </button>

                    <!-- Result (Initially Hidden) -->
                    <div id="resultSection" class="d-none text-center border-top pt-4">
                        <label class="form-label small fw-bold text-secondary d-block mb-3">Your Result</label>
                        <img id="resultImage" src="" class="mb-3">
                        <button class="btn btn-outline-dark btn-sm w-100" onclick="downloadImage()">
                            <i class="bi bi-download me-2"></i>Download Image
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- Bank QR Temp (Hidden) -->
    <div id="bankQrTemp" class="d-none"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>