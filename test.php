<?php
/**
 * Installation Test Script
 * Verify that all components are working correctly
 */

// Disable output buffering for immediate feedback
while (ob_get_level()) ob_end_clean();
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bot Installation Test</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .pass { color: green; }
        .fail { color: red; }
        .test { padding: 10px; border-bottom: 1px solid #ddd; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Installation Test</h1>
    
    <?php
    
    // Test 1: PHP Version
    echo '<div class="test">';
    echo '<h3>1. PHP Version</h3>';
    $phpVersion = phpversion();
    if (version_compare($phpVersion, '8.0.0', '>=')) {
        echo '<p class="pass">✅ PHP ' . $phpVersion . ' (OK)</p>';
    } else {
        echo '<p class="fail">❌ PHP ' . $phpVersion . ' (Requires 8.0+)</p>';
    }
    echo '</div>';
    
    // Test 2: Required Extensions
    echo '<div class="test">';
    echo '<h3>2. Required PHP Extensions</h3>';
    
    $extensions = ['curl', 'pdo', 'pdo_sqlite', 'sqlite3', 'dom', 'mbstring', 'json'];
    foreach ($extensions as $ext) {
        if (extension_loaded($ext)) {
            echo '<p class="pass">✅ ' . $ext . '</p>';
        } else {
            echo '<p class="fail">❌ ' . $ext . ' (Missing)</p>';
        }
    }
    echo '</div>';
    
    // Test 3: Config File
    echo '<div class="test">';
    echo '<h3>3. Configuration File</h3>';
    if (file_exists('config.php')) {
        require_once 'config.php';
        echo '<p class="pass">✅ config.php found</p>';
        
        if (TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') {
            echo '<p class="fail">⚠️ Bot token not configured!</p>';
        } else {
            echo '<p class="pass">✅ Bot token configured</p>';
        }
    } else {
        echo '<p class="fail">❌ config.php not found</p>';
    }
    echo '</div>';
    
    // Test 4: Required Files
    echo '<div class="test">';
    echo '<h3>4. Required Files</h3>';
    
    $files = [
        'index.php',
        'Database.php',
        'PrayerService.php',
        'LocationService.php',
        'TelegramBot.php',
        'Cities.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo '<p class="pass">✅ ' . $file . '</p>';
        } else {
            echo '<p class="fail">❌ ' . $file . ' (Missing)</p>';
        }
    }
    echo '</div>';
    
    // Test 5: Database Creation
    echo '<div class="test">';
    echo '<h3>5. Database</h3>';
    try {
        require_once 'Database.php';
        $db = new Database();
        echo '<p class="pass">✅ Database initialized successfully</p>';
        
        if (file_exists(DB_PATH)) {
            echo '<p class="pass">✅ Database file created: ' . DB_PATH . '</p>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">❌ Database error: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Test 6: Prayer Service
    echo '<div class="test">';
    echo '<h3>6. Prayer Service (Live Test)</h3>';
    try {
        require_once 'PrayerService.php';
        $prayerService = new PrayerService();
        $times = $prayerService->getPrayerTimes('toshkent');
        
        if ($times) {
            echo '<p class="pass">✅ Prayer times fetched successfully</p>';
            echo '<pre>' . print_r($times, true) . '</pre>';
        } else {
            echo '<p class="fail">⚠️ Could not fetch prayer times (Check internet connection)</p>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">❌ Prayer service error: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Test 7: Location Service
    echo '<div class="test">';
    echo '<h3>7. Location Service</h3>';
    try {
        require_once 'LocationService.php';
        $locationService = new LocationService();
        
        // Test with Tashkent coordinates
        $result = $locationService->findNearestCity(41.2995, 69.2401);
        
        if ($result['city'] === 'toshkent') {
            echo '<p class="pass">✅ Location service working correctly</p>';
            echo '<p>Nearest city: ' . $result['city'] . ' (Distance: ' . $result['distance'] . ' km)</p>';
        } else {
            echo '<p class="fail">⚠️ Location service returned unexpected result</p>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">❌ Location service error: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Test 8: File Permissions
    echo '<div class="test">';
    echo '<h3>8. File Permissions</h3>';
    if (is_writable(__DIR__)) {
        echo '<p class="pass">✅ Directory is writable</p>';
    } else {
        echo '<p class="fail">❌ Directory is not writable (chmod 755 required)</p>';
    }
    echo '</div>';
    
    ?>
    
    <h2>Summary</h2>
    <p>If all tests passed, your bot is ready! Next steps:</p>
    <ol>
        <li>Configure your bot token in <code>config.php</code></li>
        <li>Run <a href="setWebhook.php">setWebhook.php</a> to set the webhook</li>
        <li>Test your bot by sending /start in Telegram</li>
        <li>Delete test files (test.php, setWebhook.php, etc.) for security</li>
    </ol>
    
</body>
</html>
