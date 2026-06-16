<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

$pdo = db();
ensure_app_schema($pdo);
ensure_parent_schema($pdo);
$event = get_event_settings($pdo);

$registration = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surname    = trim((string)($_POST['surname'] ?? ''));
    $middleName = trim((string)($_POST['middle_name'] ?? ''));
    $firstName  = trim((string)($_POST['first_name'] ?? ''));
    $phone      = trim((string)($_POST['phone'] ?? ''));
    $altPhone   = trim((string)($_POST['alt_phone'] ?? ''));
    $email      = trim((string)($_POST['email'] ?? ''));
    $numWards   = trim((string)($_POST['num_wards'] ?? ''));
    $className  = trim((string)($_POST['class_name'] ?? ''));

    if ($surname === '')    $errors[] = 'Surname is required.';
    if ($firstName === '')  $errors[] = 'First name is required.';
    if ($phone === '')      $errors[] = 'Phone number is required.';
    if ($numWards === '' || !is_numeric($numWards) || (int)$numWards < 1) $errors[] = 'Number of wards must be at least 1.';
    if ($className === '')  $errors[] = 'Class of ward(s) is required.';

    if (empty($errors)) {
        $fullName = $surname . ' ' . ($middleName !== '' ? $middleName . ' ' : '') . $firstName;
        $regId    = generate_parent_registration_id($pdo);
        $qrToken  = generate_qr_token();
        $verifyUrl = current_url_base() . '/verify.php?token=' . rawurlencode($qrToken);

        $stmt = $pdo->prepare(
            'INSERT INTO parent_registrations
                (registration_id, qr_token, parent_name, phone, alt_phone, email, num_wards, class_name, event_name)
             VALUES
                (:registration_id, :qr_token, :parent_name, :phone, :alt_phone, :email, :num_wards, :class_name, :event_name)'
        );
        $stmt->execute([
            'registration_id' => $regId,
            'qr_token'        => $qrToken,
            'parent_name'     => $fullName,
            'phone'           => $phone,
            'alt_phone'       => $altPhone ?: null,
            'email'           => $email ?: null,
            'num_wards'       => (int)$numWards,
            'class_name'      => $className,
            'event_name'      => $event['event_name'],
        ]);

        $registration = [
            'registration_id' => $regId,
            'qr_token'        => $qrToken,
            'parent_name'     => $fullName,
            'phone'           => $phone,
            'class_name'      => $className,
            'num_wards'       => $numWards,
            'event_name'      => $event['event_name'],
            'verify_url'      => $verifyUrl,
            'qr_image_url'    => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($verifyUrl),
        ];
    }
}

$ss3Classes = ['SS3A', 'SS3B', 'SS3C', 'SS3D', 'SS3E', 'SS3F', 'SS3 Science', 'SS3 Art', 'SS3 Commercial'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parent Registration - Redeemers International Group of Schools</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1a3a6b;
            --primary-dark: #0f2347;
            --accent: #c8a84b;
            --white: #ffffff;
            --light: #f4f6fb;
            --text: #1e2a3a;
            --text-muted: #5a6a7e;
            --border: #dde3ee;
            --error: #c0392b;
            --success: #1a7a4a;
            --radius: 12px;
            --shadow: 0 4px 24px rgba(26,58,107,0.10);
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--light); color: var(--text); min-height: 100vh; }

        /* HEADER */
        .site-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: var(--white);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.18);
        }
        .logo-badge {
            width: 48px; height: 48px; background: var(--accent);
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 20px; font-weight: 900;
            color: var(--primary-dark); flex-shrink: 0;
        }
        .header-text h1 { font-size: 1.1rem; font-weight: 800; }
        .header-text p { font-size: 0.78rem; opacity: 0.75; }
        .back-link {
            margin-left: auto;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 6px;
            transition: background 0.15s;
        }
        .back-link:hover { background: rgba(255,255,255,0.1); }

        /* PAGE HERO */
        .page-hero {
            background: linear-gradient(135deg, var(--primary) 0%, #2a5298 100%);
            color: var(--white);
            text-align: center;
            padding: 40px 24px;
        }
        .page-hero h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; }
        .page-hero p { opacity: 0.85; font-size: 0.95rem; }

        /* MAIN */
        .main { max-width: 720px; margin: 0 auto; padding: 40px 20px 60px; }

        /* FORM CARD */
        .form-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .form-card-header {
            background: var(--primary);
            color: var(--white);
            padding: 20px 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .form-card-header h3 { font-size: 1.1rem; font-weight: 700; }
        .form-body { padding: 28px; }

        .note-box {
            background: #fffbf0;
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 0.88rem;
            color: #7a5a00;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .form-section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin: 24px 0 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .form-section-title:first-of-type { margin-top: 0; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row.three { grid-template-columns: 1fr 1fr 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }

        label { font-size: 0.88rem; font-weight: 600; color: var(--text); }
        label .required { color: var(--error); margin-left: 2px; }
        label .optional { font-size: 0.75rem; font-weight: 400; color: var(--text-muted); margin-left: 4px; }

        input, select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text);
            background: var(--white);
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26,58,107,0.08);
        }
        input::placeholder { color: #b0b8c8; }

        .errors {
            background: #fdf0ef;
            border: 1px solid #e8b4b0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .errors p { color: var(--error); font-size: 0.88rem; margin-bottom: 4px; }
        .errors p:last-child { margin-bottom: 0; }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: background 0.15s, transform 0.1s;
            letter-spacing: 0.5px;
        }
        .submit-btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .submit-btn:active { transform: translateY(0); }

        /* SUCCESS POPUP */
        .popup-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .popup-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            max-width: 520px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.25s ease;
        }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .popup-header {
            background: linear-gradient(135deg, var(--success) 0%, #22a06b 100%);
            color: var(--white);
            padding: 28px 28px 22px;
            text-align: center;
        }
        .popup-check {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin: 0 auto 12px;
        }
        .popup-header h2 { font-size: 1.4rem; font-weight: 800; }
        .popup-header p { font-size: 0.88rem; opacity: 0.88; margin-top: 4px; }
        .popup-body { padding: 24px 28px; }
        .qr-section { text-align: center; margin-bottom: 20px; }
        .qr-section img {
            width: 200px; height: 200px;
            border: 3px solid var(--border);
            border-radius: 10px;
            padding: 8px;
        }
        .reg-details { background: var(--light); border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .reg-details dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; font-size: 0.88rem; }
        .reg-details dt { color: var(--text-muted); font-weight: 600; white-space: nowrap; }
        .reg-details dd { color: var(--text); font-weight: 600; }
        .download-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s;
        }
        .download-btn:hover { background: var(--primary-dark); }
        .close-popup-btn {
            width: 100%;
            padding: 11px;
            background: var(--white);
            color: var(--text-muted);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            margin-top: 10px;
            font-family: inherit;
            transition: background 0.15s;
        }
        .close-popup-btn:hover { background: var(--light); }

        @media (max-width: 600px) {
            .form-row, .form-row.three { grid-template-columns: 1fr; }
            .form-body { padding: 20px; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="logo-badge">R</div>
    <div class="header-text">
        <h1>Redeemers International Group of Schools</h1>
        <p>Parent Registration Portal</p>
    </div>
    <a href="index.php" class="back-link">&#8592; Back to Home</a>
</header>

<div class="page-hero">
    <h2>👨‍👩‍👧 Parent Registration</h2>
    <p>Register your ward(s) for <strong><?= e($event['event_name']) ?></strong> and receive your unique QR pass</p>
</div>

<main class="main">
    <?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $err): ?>
            <p>&#9888; <?= e($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header">
            <span style="font-size:1.3rem">📝</span>
            <h3>Registration Form</h3>
        </div>
        <div class="form-body">
            <div class="note-box">
                ℹ️ <span><strong>Note:</strong> This registration is for <strong>SS3 students only</strong>. Fields marked <span style="color:#c0392b">*</span> are compulsory.</span>
            </div>

            <form method="post" action="parent_register.php" id="regForm">
                <div class="form-section-title">Parent / Guardian Information</div>
                <div class="form-row three">
                    <div class="form-group">
                        <label>Surname <span class="required">*</span></label>
                        <input name="surname" placeholder="e.g. Okafor" required value="<?= e($_POST['surname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Middle Name <span class="optional">(optional)</span></label>
                        <input name="middle_name" placeholder="e.g. Chukwuemeka" value="<?= e($_POST['middle_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input name="first_name" placeholder="e.g. Ngozi" required value="<?= e($_POST['first_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-section-title" style="margin-top:20px">Contact Details</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input name="phone" type="tel" placeholder="e.g. 08012345678" required value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Alternative Phone <span class="optional">(optional)</span></label>
                        <input name="alt_phone" type="tel" placeholder="e.g. 07098765432" value="<?= e($_POST['alt_phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full">
                        <label>Email Address <span class="optional">(optional)</span></label>
                        <input name="email" type="email" placeholder="e.g. parent@email.com" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-section-title" style="margin-top:20px">Ward(s) Information</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>No. of Ward(s) Attending <span class="required">*</span></label>
                        <select name="num_wards" required>
                            <option value="">-- Select --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= (($_POST['num_wards'] ?? '') == $i) ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class of Ward(s) <span class="required">*</span></label>
                        <select name="class_name" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($ss3Classes as $cls): ?>
                                <option value="<?= e($cls) ?>" <?= (($_POST['class_name'] ?? '') === $cls) ? 'selected' : '' ?>><?= e($cls) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="submit-btn">✅ REGISTER</button>
            </form>
        </div>
    </div>
</main>

<?php if ($registration !== null): ?>
<!-- SUCCESS POPUP -->
<div class="popup-overlay" id="successPopup">
    <div class="popup-box">
        <div class="popup-header">
            <div class="popup-check">✅</div>
            <h2>REGISTRATION COMPLETE!</h2>
            <p>Your QR pass has been generated successfully</p>
        </div>
        <div class="popup-body">
            <div class="qr-section">
                <img id="qrImage" src="<?= e($registration['qr_image_url']) ?>" alt="QR Code">
                <p style="margin-top:8px;font-size:0.82rem;color:var(--text-muted)">Present this QR code at the event entrance</p>
            </div>
            <div class="reg-details">
                <dl>
                    <dt>Reg. ID:</dt><dd><?= e($registration['registration_id']) ?></dd>
                    <dt>Name:</dt><dd><?= e($registration['parent_name']) ?></dd>
                    <dt>Phone:</dt><dd><?= e($registration['phone']) ?></dd>
                    <dt>Wards:</dt><dd><?= e($registration['num_wards']) ?></dd>
                    <dt>Class:</dt><dd><?= e($registration['class_name']) ?></dd>
                    <dt>Event:</dt><dd><?= e($registration['event_name']) ?></dd>
                </dl>
            </div>
            <button class="download-btn" onclick="downloadQRPDF()">
                📥 DOWNLOAD QR CODE (PDF)
            </button>
            <button class="close-popup-btn" onclick="closePopup()">Close & Register Another</button>
        </div>
    </div>
</div>

<script>
const regData = {
    id: <?= json_encode($registration['registration_id']) ?>,
    name: <?= json_encode($registration['parent_name']) ?>,
    phone: <?= json_encode($registration['phone']) ?>,
    wards: <?= json_encode($registration['num_wards']) ?>,
    className: <?= json_encode($registration['class_name']) ?>,
    event: <?= json_encode($registration['event_name']) ?>,
    qrUrl: <?= json_encode($registration['qr_image_url']) ?>
};

async function downloadQRPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a5' });

    // Header background
    doc.setFillColor(26, 58, 107);
    doc.rect(0, 0, 148, 40, 'F');

    // School name
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(13);
    doc.setFont('helvetica', 'bold');
    doc.text('REDEEMERS INTERNATIONAL', 74, 14, { align: 'center' });
    doc.text('GROUP OF SCHOOLS', 74, 21, { align: 'center' });

    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text('EVENT ENTRY PASS', 74, 30, { align: 'center' });

    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text(regData.event, 74, 37, { align: 'center' });

    // QR code
    try {
        const img = document.getElementById('qrImage');
        const canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        canvas.getContext('2d').drawImage(img, 0, 0);
        const imgData = canvas.toDataURL('image/png');
        doc.addImage(imgData, 'PNG', 34, 48, 80, 80);
    } catch(e) {
        doc.setTextColor(100);
        doc.text('[QR Code]', 74, 88, { align: 'center' });
    }

    // Details
    doc.setTextColor(30, 42, 58);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'bold');
    doc.text('REGISTRATION DETAILS', 74, 138, { align: 'center' });

    doc.setDrawColor(200);
    doc.line(14, 141, 134, 141);

    const details = [
        ['Registration ID:', regData.id],
        ['Parent Name:', regData.name],
        ['Phone:', regData.phone],
        ['No. of Wards:', regData.wards.toString()],
        ['Class:', regData.className],
    ];

    let y = 150;
    details.forEach(([label, value]) => {
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(90, 106, 126);
        doc.text(label, 16, y);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(30, 42, 58);
        doc.text(value, 70, y);
        y += 9;
    });

    // Footer
    doc.setFillColor(26, 58, 107);
    doc.rect(0, 195, 148, 15, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text('Please present this QR pass at the event entrance.', 74, 203, { align: 'center' });
    doc.text('Redeemers International Group of Schools', 74, 208, { align: 'center' });

    doc.save('RIGS-Event-Pass-' + regData.id + '.pdf');
}

function closePopup() {
    document.getElementById('successPopup').remove();
}
</script>
<?php endif; ?>

</body>
</html>
