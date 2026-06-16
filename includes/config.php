<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');

$isHttpsRequest = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
);

$sessionPath = __DIR__ . '/../data/sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0775, true);
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

session_name('saraswati_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttpsRequest,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$envValue = static function (string $key, string $fallback = ''): string {
    $value = getenv($key);
    if ($value === false) {
        return $fallback;
    }

    // Accept quoted values from env files or panel settings.
    return trim((string) $value, " \t\n\r\0\x0B'\"");
};

$hostName = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$appEnv = strtolower($envValue('APP_ENV', 'development'));
$isProduction = $appEnv === 'production' || (!in_array($hostName, ['', 'localhost', '127.0.0.1'], true));

$devDbDefaults = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'saraswati_website',
    'user' => 'root',
    'pass' => '',
];

$prodDbDefaults = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'cpzunhsysc',
    'user' => 'cpzunhsysc',
    'pass' => '',
];

$dbDefaults = $isProduction ? $prodDbDefaults : $devDbDefaults;

$dbConfig = [
    'host' => $envValue('DB_HOST', $dbDefaults['host']),
    'port' => (int) $envValue('DB_PORT', (string) $dbDefaults['port']),
    'name' => $envValue('DB_NAME', $dbDefaults['name']),
    'user' => $envValue('DB_USER', $dbDefaults['user']),
    'pass' => $envValue('DB_PASS', $dbDefaults['pass']),
    'charset' => 'utf8mb4',
];

// Support common hosting variable names and optional local overrides.
$envDbName = getenv('DB_DATABASE');
if (is_string($envDbName) && trim($envDbName) !== '') {
    $dbConfig['name'] = trim($envDbName);
}

$envDbUser = getenv('DB_USERNAME');
if (is_string($envDbUser) && trim($envDbUser) !== '') {
    $dbConfig['user'] = trim($envDbUser);
}

$envDbPass = getenv('DB_PASSWORD');
if (is_string($envDbPass) && $envDbPass !== '') {
    $dbConfig['pass'] = $envDbPass;
}

$envDbCharset = getenv('DB_CHARSET');
if (is_string($envDbCharset) && trim($envDbCharset) !== '') {
    $dbConfig['charset'] = trim($envDbCharset);
}

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig) && isset($localConfig['db']) && is_array($localConfig['db'])) {
        $dbConfig = array_merge($dbConfig, $localConfig['db']);
    }
}

$site = [
    'name' => 'Maa Saraswati Sansthan',
    'domain' => 'maa-saraswati.co.in',
    'tagline_hi' => 'ज्ञान, विद्या और संस्कृति की अधिष्ठात्री – माँ सरस्वती',
    'tagline_en' => 'The divine source of knowledge, wisdom, and culture.',
    'colors' => [
        'primary' => '#FF9933',
        'secondary' => '#FFFFFF',
        'accent' => '#C9A227',
    ],
    'bank' => [
        'account_name' => 'Maa Saraswati Sansthan',
        'bank' => 'State Bank of India',
        'branch' => 'Lakhnadon',
        'account_number' => '42636837783',
        'ifsc' => 'SBIN0010170',
        'upi_id' => '42636837783@sbi',
        // If your QR contains a specific UPI URI, paste it here exactly.
        'upi_uri' => 'upi://pay?pa=42636837783@sbi&pn=Maa%20Saraswati%20Sansthan&cu=INR',
    ],
    'whatsapp_number' => '918770349351',
    'location_map_embed' => 'https://www.google.com/maps?q=Lakhnadon%2C%20Madhya%20Pradesh&output=embed',
    'phones' => [
        '+91-9425859039',
    ],
];

$security = [
    'csrf_ttl' => 7200,
    'login_rate_limit' => [
        'max_attempts' => 5,
        'window_seconds' => 900,
        'lock_seconds' => 900,
    ],
    'password_policy' => [
        'min_length' => 8,
        'require_upper' => true,
        'require_lower' => true,
        'require_number' => true,
        'require_symbol' => true,
    ],
    'uploads' => [
        'max_bytes' => 5 * 1024 * 1024,
    ],
];

$mailConfig = [
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@maa-saraswati.co.in',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Maa Saraswati Sansthan',
    'smtp_host' => getenv('SMTP_HOST') ?: '',
    'smtp_port' => (int) (getenv('SMTP_PORT') ?: 25),
    'smtp_user' => getenv('SMTP_USER') ?: '',
    'smtp_pass' => getenv('SMTP_PASS') ?: '',
    'smtp_secure' => getenv('SMTP_SECURE') ?: '',
    'log_file' => __DIR__ . '/../data/mail.log',
];

$paymentConfig = [
    'callback_secret' => getenv('PAYMENT_CALLBACK_SECRET') ?: '',
];

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['name'],
        $dbConfig['charset']
    );
    $cfgPdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $cfgRows = $cfgPdo->query('SELECT setting_key, setting_value FROM admin_credentials')->fetchAll();
    $cfgMap = [];
    foreach ($cfgRows as $cfgRow) {
        $key = (string) ($cfgRow['setting_key'] ?? '');
        if ($key === '') {
            continue;
        }
        $cfgMap[$key] = (string) ($cfgRow['setting_value'] ?? '');
    }

    if (($cfgMap['MAIL_FROM_EMAIL'] ?? '') !== '') {
        $mailConfig['from_email'] = $cfgMap['MAIL_FROM_EMAIL'];
    }
    if (($cfgMap['MAIL_FROM_NAME'] ?? '') !== '') {
        $mailConfig['from_name'] = $cfgMap['MAIL_FROM_NAME'];
    }
    if (($cfgMap['SMTP_HOST'] ?? '') !== '') {
        $mailConfig['smtp_host'] = $cfgMap['SMTP_HOST'];
    }
    if (($cfgMap['SMTP_PORT'] ?? '') !== '') {
        $mailConfig['smtp_port'] = (int) $cfgMap['SMTP_PORT'];
    }
    if (($cfgMap['SMTP_USER'] ?? '') !== '') {
        $mailConfig['smtp_user'] = $cfgMap['SMTP_USER'];
    }
    if (($cfgMap['SMTP_PASS'] ?? '') !== '') {
        $mailConfig['smtp_pass'] = $cfgMap['SMTP_PASS'];
    }
    if (($cfgMap['PAYMENT_CALLBACK_SECRET'] ?? '') !== '') {
        $paymentConfig['callback_secret'] = $cfgMap['PAYMENT_CALLBACK_SECRET'];
    }
} catch (Throwable $e) {
    // Keep environment defaults when DB credentials are unavailable.
}

$translations = [
    'hi' => [
        'home' => 'होम',
        'about' => 'हमारे बारे में',
        'aarti' => 'आरती व मंत्र',
        'significance' => 'महत्व',
        'festivals' => 'त्योहार',
        'project' => 'मंदिर परियोजना',
        'gallery' => 'चित्र गैलरी',
        'videos' => 'वीडियो गैलरी',
        'donate' => 'दान करें',
        'contact' => 'संपर्क',
        'hero_heading' => 'माँ सरस्वती संस्थान में आपका स्वागत है',
        'hero_sub' => 'ज्ञान, सेवा और संस्कार के माध्यम से राष्ट्र निर्माण का संकल्प',
        'donate_now' => 'अभी दान करें',
        'upcoming_events' => 'आगामी कार्यक्रम',
        'about_short' => 'ट्रस्ट पिछले 8+ वर्षों से समाज और संस्कृति के लिए सक्रिय है।',
        'read_more' => 'और पढ़ें',
        'download_pdf' => 'PDF डाउनलोड करें',
        'play_audio' => 'ऑडियो चलाएं',
        'scan_donate' => 'स्कैन करके दान करें',
        'contact_us' => 'संपर्क करें',
        'submit' => 'भेजें',
        'name' => 'नाम',
        'email' => 'ईमेल',
        'message' => 'संदेश',
        'festival_reminder' => 'भव्य मां सरस्वती मंदिर निर्माण',
        'show_welcome_again' => 'वेलकम बैनर फिर दिखाएं',
    ],
    'en' => [
        'home' => 'Home',
        'about' => 'About',
        'aarti' => 'Aarti & Mantras',
        'significance' => 'Significance',
        'festivals' => 'Festivals',
        'project' => 'Temple Project',
        'gallery' => 'Image Gallery',
        'videos' => 'Video Gallery',
        'donate' => 'Donate',
        'contact' => 'Contact',
        'hero_heading' => 'Welcome to Maa Saraswati Sansthan',
        'hero_sub' => 'Committed to knowledge, service, and cultural upliftment.',
        'donate_now' => 'Donate Now',
        'upcoming_events' => 'Upcoming Events',
        'about_short' => 'The trust has been actively serving society and culture for 8+ years.',
        'read_more' => 'Read More',
        'download_pdf' => 'Download PDF',
        'play_audio' => 'Play Audio',
        'scan_donate' => 'Scan & Donate',
        'contact_us' => 'Contact Us',
        'submit' => 'Submit',
        'name' => 'Name',
        'email' => 'Email',
        'message' => 'Message',
        'festival_reminder' => 'Next festival: Basant Panchami',
        'show_welcome_again' => 'Show Welcome Again',
    ],
];

$lang = $_GET['lang'] ?? 'hi';
if (!isset($translations[$lang])) {
    $lang = 'hi';
}

function t(string $key): string
{
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}

function site_url(string $path = ''): string
{
    return $path === '' ? '/' : '/' . ltrim($path, '/');
}

function current_year_festival_dates(): array
{
    $year = (int) date('Y');

    return [
        [
            'name' => 'Basant Panchami',
            'date' => date('d M Y', strtotime($year . '-02-03')),
            'note' => 'Main festival dedicated to Maa Saraswati',
        ],
        [
            'name' => 'Navratri (Saraswati Avahan)',
            'date' => date('d M Y', strtotime($year . '-10-03')),
            'note' => 'Invoking wisdom and devotion',
        ],
        [
            'name' => 'Vasant Utsav',
            'date' => date('d M Y', strtotime($year . '-02-10')),
            'note' => 'Celebration of spring, arts, and learning',
        ],
    ];
}

$defaultEvents = [
    [
        'title' => 'Bhoomi Pujan Smaran Samaroh',
        'date' => '19-20 Apr 2026',
        'location' => 'Lakhnadon',
    ],
    [
        'title' => 'Basant Panchami Mahotsav',
        'date' => 'February (Yearly)',
        'location' => 'Temple Premises',
    ],
];

$defaultGallery = [
    ['category' => 'Construction', 'file' => 'assets/images/sara1.jpeg', 'title' => 'Temple Construction Progress'],
    ['category' => 'Construction', 'file' => 'assets/images/sara2.jpeg', 'title' => 'Lakhnadon Site Work'],
    ['category' => 'Rituals', 'file' => 'assets/images/sara10.jpeg', 'title' => 'Sacred Ritual Ceremony'],
    ['category' => 'Rituals', 'file' => 'assets/images/sara11.jpeg', 'title' => 'Vedic Pujan Rituals'],
    ['category' => 'Events', 'file' => 'assets/images/sara14.jpeg', 'title' => 'Devotee Participation'],
    ['category' => 'Events', 'file' => 'assets/images/sara21.jpeg', 'title' => 'Community Gathering'],
];

$defaultVideos = [
    [
        'title' => 'Maa Saraswati Post Update',
        'youtube_id' => '',
        'youtube_url' => 'http://youtube.com/post/Ugkxcgce76F5FizO0G4ZqyMlD4eIt7H-vElp?si=_E9YBhzlcP1yjYxk',
        'description' => 'Latest official update from Maa Saraswati Sansthan YouTube post.',
    ],
    [
        'title' => 'Maa Saraswati Video',
        'youtube_id' => 'LOnDkWEv4tQ',
        'youtube_url' => 'https://youtu.be/LOnDkWEv4tQ',
        'description' => 'Featured video from Maa Saraswati Sansthan YouTube channel.',
    ],
];
