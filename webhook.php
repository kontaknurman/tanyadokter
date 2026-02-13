<?php
/**
 * GitHub Webhook Handler (No Git Required)
 *
 * Downloads ZIP from GitHub API and extracts to server.
 * Works without git installed. Supports private repos.
 *
 * Setup:
 * 1. GitHub repo > Settings > Webhooks > Add webhook
 * 2. Payload URL: https://your-domain.com/webhook.php
 * 3. Content type: application/json
 * 4. Secret: Same as WEBHOOK_SECRET below
 * 5. Events: Just the push event
 *
 * Untuk Private Repo:
 * 1. Buat Personal Access Token di GitHub > Settings > Developer settings > Personal access tokens
 * 2. Pilih "Fine-grained tokens" atau "Tokens (classic)"
 * 3. Scope yang diperlukan: repo (untuk classic) atau Contents: Read (untuk fine-grained)
 * 4. Copy token ke GITHUB_TOKEN di bawah
 *
 * @package BeritaML
 */

// ============================================
// CONFIGURATION - SESUAIKAN DENGAN KEBUTUHAN!
// ============================================

define('WEBHOOK_SECRET', '');  // Ganti dengan secret key dari GitHub Webhook
define('GITHUB_TOKEN', '');                         // Personal Access Token (kosongkan jika public repo)
define('GITHUB_USER', 'kontaknurman');              // Username GitHub
define('GITHUB_REPO', 'tanyadokter');                  // Nama repository
define('GITHUB_BRANCH', 'main');                    // Branch yang di-deploy (ganti sesuai kebutuhan)

// Paths - sesuaikan dengan struktur server
define('SOURCE_SUBFOLDER', 'health-wiki');               // Folder theme dalam repo
define('TARGET_PATH', '/wp-content/plugins/health-wiki');  // Path absolut ke folder theme di server
define('LOG_FILE', __DIR__ . '/webhook.log');
define('ENABLE_LOGGING', true);

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Log webhook activity
 *
 * @param string $message Message to log
 */
function webhook_log(string $message): void {
    if (!ENABLE_LOGGING) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";

    file_put_contents(LOG_FILE, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Validate GitHub webhook signature
 *
 * @param string $payload Raw request payload
 * @param string $signature X-Hub-Signature-256 header
 * @return bool
 */
function validate_signature(string $payload, string $signature): bool {
    // Skip validation if no secret configured
    if (empty(WEBHOOK_SECRET) || WEBHOOK_SECRET === 'your-secret-key-here') {
        webhook_log('Warning: No webhook secret configured - skipping signature validation');
        return true;
    }

    if (empty($signature)) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

    return hash_equals($expected, $signature);
}

// ============================================
// DEPLOYMENT FUNCTIONS
// ============================================

/**
 * Download ZIP from GitHub and extract to target
 *
 * @return array Result with success status
 */
function download_and_extract(): array {
    // GitHub API URL for downloading ZIP
    $zip_url = sprintf(
        'https://api.github.com/repos/%s/%s/zipball/%s',
        GITHUB_USER,
        GITHUB_REPO,
        GITHUB_BRANCH
    );

    $temp_zip = sys_get_temp_dir() . '/github_' . time() . '.zip';
    $temp_dir = sys_get_temp_dir() . '/github_extract_' . time();

    webhook_log("Downloading: {$zip_url}");

    // Build headers with auth token for private repo
    $headers = [
        "User-Agent: BeritaML-Webhook/1.0",
        "Accept: application/vnd.github+json",
    ];

    if (!empty(GITHUB_TOKEN)) {
        $headers[] = "Authorization: Bearer " . GITHUB_TOKEN;
        webhook_log("Using GitHub Token for authentication");
    }

    // Download ZIP using cURL for better error handling
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $zip_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $zip_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($zip_content === false || $http_code !== 200) {
        webhook_log("Error: Failed to download ZIP (HTTP {$http_code}): {$curl_error}");
        return [
            'success' => false,
            'error' => "Failed to download ZIP from GitHub (HTTP {$http_code})"
        ];
    }

    webhook_log("Downloaded: " . strlen($zip_content) . " bytes");

    // Save ZIP to temp file
    if (file_put_contents($temp_zip, $zip_content) === false) {
        webhook_log("Error: Failed to save ZIP to temp file");
        return ['success' => false, 'error' => 'Failed to save ZIP file'];
    }

    // Extract ZIP
    $zip = new ZipArchive();
    $open_result = $zip->open($temp_zip);

    if ($open_result !== true) {
        @unlink($temp_zip);
        webhook_log("Error: Failed to open ZIP (code: {$open_result})");
        return ['success' => false, 'error' => 'Failed to open ZIP file'];
    }

    // Create temp extract directory
    if (!@mkdir($temp_dir, 0755, true) && !is_dir($temp_dir)) {
        $zip->close();
        @unlink($temp_zip);
        webhook_log("Error: Failed to create temp directory");
        return ['success' => false, 'error' => 'Failed to create temp directory'];
    }

    if (!$zip->extractTo($temp_dir)) {
        $zip->close();
        @unlink($temp_zip);
        delete_directory($temp_dir);
        webhook_log("Error: Failed to extract ZIP");
        return ['success' => false, 'error' => 'Failed to extract ZIP file'];
    }

    $zip->close();
    @unlink($temp_zip);

    webhook_log("Extracted to temp dir: {$temp_dir}");

    // Find extracted folder (GitHub adds username-repo-hash prefix)
    $extracted_folder = null;
    $files = scandir($temp_dir);

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_dir($temp_dir . '/' . $file)) {
            $extracted_folder = $temp_dir . '/' . $file;
            break;
        }
    }

    if (!$extracted_folder) {
        delete_directory($temp_dir);
        webhook_log("Error: No folder found in ZIP");
        return ['success' => false, 'error' => 'No folder found in extracted ZIP'];
    }

    webhook_log("Extracted folder: {$extracted_folder}");

    // Build source path with subfolder
    $source_folder = $extracted_folder;

    if (!empty(SOURCE_SUBFOLDER)) {
        $source_folder = $extracted_folder . '/' . SOURCE_SUBFOLDER;

        if (!is_dir($source_folder)) {
            delete_directory($temp_dir);
            webhook_log("Error: Subfolder not found: " . SOURCE_SUBFOLDER);
            return [
                'success' => false,
                'error' => 'Subfolder not found in ZIP: ' . SOURCE_SUBFOLDER
            ];
        }

        webhook_log("Source subfolder: {$source_folder}");
    }

    // Validate target path
    if (empty(TARGET_PATH) || TARGET_PATH === '/path/to/wp-content/themes/berita') {
        delete_directory($temp_dir);
        webhook_log("Error: TARGET_PATH not configured");
        return ['success' => false, 'error' => 'TARGET_PATH not configured'];
    }

    // Copy files to target
    $copied = copy_directory($source_folder, TARGET_PATH);

    // Cleanup temp directory
    delete_directory($temp_dir);

    webhook_log("Copied {$copied} files to " . TARGET_PATH);

    return ['success' => true, 'files_copied' => $copied];
}

/**
 * Recursively copy directory
 *
 * @param string $source Source directory
 * @param string $dest Destination directory
 * @return int Number of files copied
 */
function copy_directory(string $source, string $dest): int {
    $count = 0;

    // Create destination if not exists
    if (!is_dir($dest)) {
        @mkdir($dest, 0755, true);
    }

    $dir = opendir($source);

    if ($dir === false) {
        webhook_log("Error: Cannot open source directory: {$source}");
        return 0;
    }

    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $src_path = $source . '/' . $file;
        $dest_path = $dest . '/' . $file;

        if (is_dir($src_path)) {
            $count += copy_directory($src_path, $dest_path);
        } else {
            if (@copy($src_path, $dest_path)) {
                @chmod($dest_path, 0644);
                $count++;
            } else {
                webhook_log("Warning: Failed to copy: {$file}");
            }
        }
    }

    closedir($dir);

    return $count;
}

/**
 * Recursively delete directory
 *
 * @param string $dir Directory to delete
 */
function delete_directory(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . '/' . $file;

        if (is_dir($path)) {
            delete_directory($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

/**
 * Send JSON response and exit
 *
 * @param int $code HTTP status code
 * @param string $message Response message
 * @param array $data Additional data
 */
function respond(int $code, string $message, array $data = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        array_merge(['message' => $message], $data),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}

// ============================================
// MAIN EXECUTION
// ============================================

webhook_log('========== Webhook Request ==========');
webhook_log('IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
webhook_log('Method: ' . $_SERVER['REQUEST_METHOD']);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    webhook_log('Error: Method not allowed');
    respond(405, 'Method Not Allowed');
}

// Get raw payload
$payload = file_get_contents('php://input');
webhook_log('Payload size: ' . strlen($payload) . ' bytes');

if (empty($payload)) {
    webhook_log('Error: Empty payload');
    respond(400, 'Empty payload');
}

// Validate GitHub signature
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!validate_signature($payload, $signature)) {
    webhook_log('Error: Invalid signature');
    respond(403, 'Invalid signature');
}

// Parse JSON payload
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    webhook_log('Error: Invalid JSON - ' . json_last_error_msg());
    respond(400, 'Invalid JSON payload');
}

// Check GitHub event type
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';
webhook_log('Event: ' . $event);

// Handle ping event (webhook setup verification)
if ($event === 'ping') {
    webhook_log('Ping OK - Webhook configured successfully');
    respond(200, 'Pong! Webhook configured for BeritaML.');
}

// Only process push events
if ($event !== 'push') {
    webhook_log('Skip: Not a push event');
    respond(200, 'Skipped: Not a push event', ['event' => $event]);
}

// Check branch
$ref = $data['ref'] ?? '';
$branch = str_replace('refs/heads/', '', $ref);
webhook_log('Branch: ' . $branch);

if ($branch !== GITHUB_BRANCH) {
    webhook_log('Skip: Push to different branch');
    respond(200, 'Skipped: Push to ' . $branch, ['expected_branch' => GITHUB_BRANCH]);
}

// Get commit info for logging
$commits = $data['commits'] ?? [];
$commit_count = count($commits);
$head_commit = $data['head_commit']['message'] ?? 'No message';
webhook_log("Commits: {$commit_count} - Latest: {$head_commit}");

// Execute deployment
webhook_log('Starting deployment...');
$start_time = microtime(true);

$result = download_and_extract();

$duration = round(microtime(true) - $start_time, 2);

if ($result['success']) {
    webhook_log("SUCCESS: Updated {$result['files_copied']} files in {$duration}s");
    respond(200, 'BeritaML theme updated successfully', [
        'files_copied' => $result['files_copied'],
        'duration' => $duration . 's',
        'branch' => $branch,
        'commits' => $commit_count
    ]);
} else {
    webhook_log("FAILED: {$result['error']}");
    respond(500, 'Deployment failed', [
        'error' => $result['error'],
        'duration' => $duration . 's'
    ]);
}
