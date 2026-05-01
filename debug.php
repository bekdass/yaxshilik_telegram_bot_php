<?php
// Xatolarni ekranga chiqarish
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "1. Config yuklanmoqda...\n";
require_once __DIR__ . '/config.php';

echo "2. Database klassi yuklanmoqda...\n";
require_once __DIR__ . '/Database.php';

echo "3. Bazaga ulanish sinalyapti...\n";
$db = new Database();

echo "4. Test user qo'shilmoqda (ID: 777777)...\n";
// Diqqat: Bu yerda null, null, null - bu ism, familiya, username
// Oxiridagi null, null - bu lokatsiya (hozircha shart emas)
$result = $db->addUser(777777, "TestUser", "TestFamiliya", "test_login", null, null);

if ($result) {
    echo "✅ Muvaffaqiyatli! Bazani tekshiring.\n";
} else {
    echo "❌ Xatolik! Log faylni o'qiymiz:\n";
    if (file_exists(__DIR__ . '/error.log')) {
        echo file_get_contents(__DIR__ . '/error.log');
    } else {
        echo "Error log fayli bo'sh yoki yaratilmadi.\n";
    }
}
?>
