let currentTemplate = null;
let currentFile = null;
let activeTab = 'bank'; // Default tab

// Tab Listener
document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', e => {
        const target = e.target.getAttribute('data-bs-target'); // #pills-xxx
        activeTab = target.replace('#pills-', '');
        checkState();
    });
});

// File Logic
const dropZone = document.getElementById('dropZone');
document.getElementById('fileInput').addEventListener('change', function() {
    if (this.files[0]) {
        currentFile = this.files[0];
        document.getElementById('fileName').textContent = this.files[0].name;
        checkState();
    }
});

// Input Listeners (Add all new IDs)
const inputIds = [
    'bankBin', 'bankAccount', 'bankAmount', 'bankContent',
    'urlInput', 'textInput',
    'wifiSsid', 'wifiPass', 'wifiType',
    'phoneInput', 'smsPhone', 'smsMessage',
    'emailTo', 'emailSubject', 'emailBody'
];
inputIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', checkState);
});

// Select Template
function selectTemplate(el, tplName) {
    document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    currentTemplate = tplName;
    document.getElementById('selectedTemplateBadge').textContent = tplName;
    checkState();
}

// Validation Logic
function checkState() {
    const btn = document.getElementById('generateBtn');
    let ready = false;

    if (currentTemplate) {
        switch (activeTab) {
            case 'bank':
                if (document.getElementById('bankBin').value && document.getElementById('bankAccount').value) ready = true;
                break;
            case 'url':
                if (document.getElementById('urlInput').value.trim()) ready = true;
                break;
            case 'text':
                if (document.getElementById('textInput').value.trim()) ready = true;
                break;
            case 'wifi':
                if (document.getElementById('wifiSsid').value.trim()) ready = true;
                break;
            case 'phone':
                if (document.getElementById('phoneInput').value.trim()) ready = true;
                break;
            case 'sms':
                if (document.getElementById('smsPhone').value.trim()) ready = true;
                break;
            case 'email':
                if (document.getElementById('emailTo').value.trim()) ready = true;
                break;
            case 'upload':
                if (currentFile) ready = true;
                break;
        }
    }
    btn.disabled = !ready;
}

// Generate QR String for Standard Types
function getQrString() {
    switch (activeTab) {
        case 'url':
            return document.getElementById('urlInput').value.trim();
        case 'text':
            return document.getElementById('textInput').value.trim();
        case 'wifi':
            const ssid = document.getElementById('wifiSsid').value.trim();
            const pass = document.getElementById('wifiPass').value;
            const type = document.getElementById('wifiType').value;
            return `WIFI:S:${ssid};T:${type};P:${pass};;`;
        case 'phone':
            return `tel:${document.getElementById('phoneInput').value.trim()}`;
        case 'sms':
            const smsPhone = document.getElementById('smsPhone').value.trim();
            const smsMsg = document.getElementById('smsMessage').value;
            return `smsto:${smsPhone}:${smsMsg}`;
        case 'email':
            const mailTo = document.getElementById('emailTo').value.trim();
            const sub = document.getElementById('emailSubject').value;
            const body = document.getElementById('emailBody').value;
            return `mailto:${mailTo}?subject=${encodeURIComponent(sub)}&body=${encodeURIComponent(body)}`;
        default:
            return '';
    }
}

// Generic Helper to Render QR to DataURL
function generateQrDataUrl(text) {
    const container = document.getElementById('bankQrTemp');
    container.innerHTML = '';
    return new Promise((resolve, reject) => {
        const div = document.createElement('div');
        container.appendChild(div);
        new QRCode(div, {
            text: text,
            width: 400,
            height: 400,
            correctLevel: QRCode.CorrectLevel.L
        });
        setTimeout(() => {
            const canvas = div.querySelector('canvas') || div.querySelector('img');
            if (canvas) resolve(canvas.toDataURL ? canvas.toDataURL() : canvas.src);
            else reject('QR Render Error');
        }, 100);
    });
}

async function createBankQrFile() {
    const data = {
        bin: document.getElementById('bankBin').value,
        acc: document.getElementById('bankAccount').value,
        amount: document.getElementById('bankAmount').value,
        content: document.getElementById('bankContent').value
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const res = await fetch('api/generate_string.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (!json.success) throw new Error(json.error);
    
    return generateQrDataUrl(json.qrString);
}

async function generateQR() {
    if (!currentTemplate) return;
    const btn = document.getElementById('generateBtn');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = 'Generating...';
    document.getElementById('resultSection').classList.add('d-none');

    try {
        let imgData = null;
        
        if (activeTab === 'upload') {
            imgData = await new Promise(r => {
                const reader = new FileReader();
                reader.onload = e => r(e.target.result);
                reader.readAsDataURL(currentFile);
            });
        } else if (activeTab === 'bank') {
            imgData = await createBankQrFile();
        } else {
            // Standard Types
            const qrString = getQrString();
            if (!qrString) throw new Error("Empty Content");
            imgData = await generateQrDataUrl(qrString);
        }

        const fd = new FormData();
        fd.append('qr_image', imgData);

        if (activeTab === 'bank') {
            const bankSelect = document.getElementById('bankBin');
            fd.append('bank_name', bankSelect.options[bankSelect.selectedIndex].text);
            fd.append('acc_num', document.getElementById('bankAccount').value);
        } else {
            // For other types, pass empty placeholders
            fd.append('bank_name', '');
            fd.append('acc_num', '');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(`template/${currentTemplate}/index.php`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: fd
        });

        const json = await res.json();
        if (json.success) {
            const rImg = document.getElementById('resultImage');
            rImg.src = json.image;
            document.getElementById('resultSection').classList.remove('d-none');
            rImg.scrollIntoView({
                behavior: 'smooth'
            });
        } else {
            alert('Error: ' + json.error);
        }

    } catch (e) {
        alert('Error: ' + e.message);
        console.error(e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function downloadImage() {
    const a = document.createElement('a');
    a.href = document.getElementById('resultImage').src;
    a.download = `qr-anime-${Date.now()}.jpg`;
    a.click();
}

function dabiluxNuoiThanToggle() {
    const widget = document.getElementById('dabilux-nuoi-than-widget');
    if (widget) {
        widget.classList.toggle('active');
    }
}

document.addEventListener('click', function (event) {
    const widget = document.getElementById('dabilux-nuoi-than-widget');
    if (!widget) return;

    const isClickInside = widget.contains(event.target);

    if (!isClickInside && widget.classList.contains('active')) {
        widget.classList.remove('active');
    }
});