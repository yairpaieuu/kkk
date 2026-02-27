<?php
// 1. Enable Error Reporting to debug 500 Errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Try to load the Database Config
// Adjust these paths based on common setups
$possiblePaths = [
    __DIR__ . '/app/config/database.php', // Standard
    __DIR__ . '/config/database.php',     // Root config
    __DIR__ . '/../app/config/database.php', // If outside public_html
    __DIR__ . '/../config/database.php'      // If outside public_html
];

$dbLoaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $dbLoaded = true;
        break;
    }
}

if (!$dbLoaded) {
    die("<h1>Error: Could not find database.php</h1><p>Please edit this file and check line 10 to ensure the path is correct.</p>");
}

// 3. Start Logic
session_start();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_clean'])) {
    try {
        if (!class_exists('Database')) {
            throw new Exception("Database class not found. Check config file.");
        }

        $database = new Database();
        $db = $database->getConnection();

        // 1. Disable Foreign Key Checks (Crucial for Truncate)
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // 2. Clear Tables
        $db->exec("TRUNCATE TABLE purchase_items");
        $db->exec("TRUNCATE TABLE purchases");

        // 3. Optional: Reset Stock
        if (isset($_POST['reset_stock'])) {
            $db->exec("UPDATE products SET stock = 0 WHERE type = 'physical'");
            $message = "All purchase history deleted AND stock reset to 0.";
        } else {
            $message = "All purchase history deleted successfully.";
        }

        // 4. Re-enable Foreign Key Checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean History</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">

    <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl border border-red-500/30 max-w-md w-full text-center">
        
        <div class="mb-6"><span class="text-6xl">🗑️</span></div>

        <h1 class="text-2xl font-bold text-red-500 mb-2">Clean Purchase Data</h1>
        <p class="text-gray-400 mb-6 text-sm">
            This works by running SQL <code>TRUNCATE</code> on purchase tables.
        </p>

        <?php if ($message): ?>
            <div class="bg-green-600/20 text-green-400 p-4 rounded-lg mb-6 border border-green-600/50">
                <?= htmlspecialchars($message) ?>
            </div>
            <a href="/" class="block w-full bg-gray-700 hover:bg-gray-600 py-3 rounded-xl transition font-bold mb-4">Go Back Home</a>
        <?php else: ?>

            <form method="POST">
                <div class="bg-black/20 p-4 rounded-xl mb-6 text-left">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="reset_stock" value="1" class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-red-600 focus:ring-red-500">
                        <span class="text-sm text-gray-300">
                            Also reset all <strong>Physical Product Stock</strong> to 0?
                        </span>
                    </label>
                </div>

                <button type="submit" name="confirm_clean" 
                    onclick="return confirm('Are you absolutely sure? This cannot be undone.')"
                    class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-red-600/20 transition transform hover:scale-105">
                    Delete Data
                </button>
            </form>

        <?php endif; ?>

    </div>

</body>
</html>