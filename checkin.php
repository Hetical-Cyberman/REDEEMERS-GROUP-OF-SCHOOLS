<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

require_staff_login();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check In - <?= e(SCHOOL_NAME) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="https://unpkg.com/html5-qrcode" defer></script>
    <style>
        /* ── SCAN POPUP ── */
        .scan-popup-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .scan-popup-overlay.active { display: flex; animation: fadeIn 0.18s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .scan-popup {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.3);
            min-width: 300px;
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            animation: popUp 0.22s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes popUp { from { transform: scale(0.7); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .scan-popup-header {
            padding: 30px 28px 22px;
            text-align: center;
        }
        .scan-popup-icon {
            font-size: 3.5rem;
            margin-bottom: 12px;
            display: block;
        }
        .scan-popup-title {
            font-size: 1.4rem;
            font-weight: 900;
            letter-spacing: 0.3px;
        }
        .scan-popup-sub {
            font-size: 0.88rem;
            margin-top: 4px;
            opacity: 0.8;
        }

        .scan-popup.success .scan-popup-header { background: #e8f8f0; }
        .scan-popup.success .scan-popup-title { color: #1a7a4a; }

        .scan-popup.duplicate .scan-popup-header { background: #fff8e1; }
        .scan-popup.duplicate .scan-popup-title { color: #b8620a; }

        .scan-popup.invalid .scan-popup-header { background: #fdf0ef; }
        .scan-popup.invalid .scan-popup-title { color: #c0392b; }

        .scan-popup-body {
            padding: 16px 24px 24px;
            background: #fff;
        }
        .scan-person-details {
            background: #f4f6fb;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 0.88rem;
        }
        .scan-person-details dl {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 5px 14px;
        }
        .scan-person-details dt { color: #5a6a7e; font-weight: 600; white-space: nowrap; }
        .scan-person-details dd { color: #1e2a3a; font-weight: 600; }
        .scan-timer-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 16px;
            overflow: hidden;
        }
        .scan-timer-bar-fill {
            height: 100%;
            border-radius: 2px;
            animation: shrink 5s linear forwards;
        }
        .scan-popup.success .scan-timer-bar-fill { background: #1a7a4a; }
        .scan-popup.duplicate .scan-timer-bar-fill { background: #b8620a; }
        .scan-popup.invalid .scan-timer-bar-fill { background: #c0392b; }
        @keyframes shrink { from { width: 100%; } to { width: 0%; } }
    </style>
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <h1 class="brand"><?= e(SCHOOL_NAME) ?></h1>
            <nav class="nav" aria-label="Main navigation">
                <a class="secondary" href="dashboard.php">Dashboard</a>
                <a class="secondary" href="register.php">Register</a>
                <a href="checkin.php">Check In</a>
                <a class="secondary" href="logout.php">Log Out</a>
            </nav>
        </header>

        <section class="scanner-layout">
            <div class="panel">
                <h2>School Event Check-In</h2>
                <div id="reader" aria-label="Camera QR scanner"></div>
            </div>

            <div class="panel scan-status" aria-live="polite">
                <span id="statusBadge" class="status-badge">Waiting for QR Code</span>
                <p id="statusMessage" class="message">Point the camera at a student or parent pass.</p>
                <dl id="studentDetails" class="details"></dl>
            </div>
        </section>
    </main>

    <!-- SCAN RESULT POPUP -->
    <div class="scan-popup-overlay" id="scanPopupOverlay">
        <div class="scan-popup" id="scanPopup">
            <div class="scan-popup-header">
                <span class="scan-popup-icon" id="popupIcon"></span>
                <div class="scan-popup-title" id="popupTitle"></div>
                <div class="scan-popup-sub" id="popupSub"></div>
            </div>
            <div class="scan-popup-body">
                <div class="scan-person-details" id="popupDetails" style="display:none">
                    <dl id="popupDetailsList"></dl>
                </div>
                <div class="scan-timer-bar">
                    <div class="scan-timer-bar-fill" id="timerBar"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
    let popupTimeout = null;
    let scanning = true;

    function showPopup(type, title, sub, person) {
        const overlay  = document.getElementById('scanPopupOverlay');
        const popup    = document.getElementById('scanPopup');
        const icon     = document.getElementById('popupIcon');
        const titleEl  = document.getElementById('popupTitle');
        const subEl    = document.getElementById('popupSub');
        const details  = document.getElementById('popupDetails');
        const list     = document.getElementById('popupDetailsList');
        const timerBar = document.getElementById('timerBar');

        // Reset classes
        popup.className = 'scan-popup ' + type;
        overlay.classList.add('active');

        const icons = { success: '✅', duplicate: '⚠️', invalid: '❌' };
        icon.textContent = icons[type] || '❓';
        titleEl.textContent = title;
        subEl.textContent = sub;

        // Reset timer animation
        timerBar.style.animation = 'none';
        timerBar.offsetHeight; // reflow
        timerBar.style.animation = 'shrink 5s linear forwards';

        if (person) {
            details.style.display = 'block';
            list.innerHTML = `
                <dt>Name:</dt><dd>${person.student_name || ''}</dd>
                <dt>Reg. ID:</dt><dd>${person.registration_id || ''}</dd>
                <dt>Class:</dt><dd>${person.class_name || ''}</dd>
                <dt>Event:</dt><dd>${person.event_name || ''}</dd>
                ${person.checkin_time ? `<dt>Time:</dt><dd>${person.checkin_time}</dd>` : ''}
            `;
        } else {
            details.style.display = 'none';
            list.innerHTML = '';
        }

        clearTimeout(popupTimeout);
        popupTimeout = setTimeout(closePopup, 5000);
    }

    function closePopup() {
        document.getElementById('scanPopupOverlay').classList.remove('active');
        scanning = true;
    }

    document.getElementById('scanPopupOverlay').addEventListener('click', function(e) {
        if (e.target === this) closePopup();
    });

    const verifyUrl = '<?= current_url_base() ?>/verify.php';

    function extractQrToken(decodedText) {
        const raw = String(decodedText || '').trim();
        try {
            const url = new URL(raw);
            return url.searchParams.get('token') || raw;
        } catch (error) {
            return raw;
        }
    }

    async function onScanSuccess(decodedText) {
        if (!scanning) return;
        scanning = false;

        const token = extractQrToken(decodedText);
        document.getElementById('statusBadge').textContent = 'Checking Pass';
        document.getElementById('statusMessage').textContent = 'Verifying scanned QR code...';

        try {
            const response = await fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json'
                },
                body: 'qr=' + encodeURIComponent(token),
            });

            const data = await response.json();
            const person = data.person || data.student || null;

            if (data.status === 'valid') {
                showPopup('success', 'SUCCESSFUL, ALLOW ENTRY', data.detail || 'Access granted.', person);
            } else if (data.status === 'already_checked_in') {
                showPopup('duplicate', 'USER ALREADY EXISTS', data.detail || 'This pass has already been used.', person);
            } else if (data.status === 'unauthorized') {
                showPopup('invalid', 'LOGIN REQUIRED', data.message || 'Please log in again before scanning.', null);
            } else {
                showPopup('invalid', 'NOT REGISTERED', data.detail || data.message || 'This QR code is not registered.', null);
            }
        } catch (error) {
            showPopup('invalid', 'SCAN ERROR', 'The scanner could not verify this QR. Try again or scan with the phone camera.', null);
        }
    }

    const html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        onScanSuccess,
        () => {}
    ).catch(err => {
        document.getElementById('statusMessage').textContent = 'Camera error: ' + err;
    });
    </script>
</body>
</html>
