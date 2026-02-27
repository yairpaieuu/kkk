<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f172a] text-white h-screen flex items-center justify-center">

    <div class="w-full max-w-md p-8 bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold mb-2">Verify Email</h1>
            <p class="text-gray-400 text-sm">We sent a code to <span class="text-blue-400"><?= $_SESSION['verify_email'] ?? 'your email' ?></span></p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-500/20 text-red-400 p-3 rounded-lg mb-4 text-center text-sm border border-red-500/30">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/verify-otp" class="space-y-6">
            <div class="flex justify-center gap-2">
                <!-- Single Input for Simplicity -->
                <input type="text" name="otp[]" maxlength="6" class="w-full bg-gray-900 border border-gray-700 text-white text-center text-2xl tracking-[1em] p-4 rounded-lg focus:border-blue-500 outline-none transition font-mono" placeholder="000000" autofocus>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-500/20">
                Verify Account
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500">
            Didn't receive code? <a href="/login" class="text-blue-400 hover:text-white">Try Login to Resend</a>
        </div>
    </div>

</body>
</html>