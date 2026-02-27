<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: white; }
        .glass-bar { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen px-4 pb-24 md:pb-0 relative">

    <!-- REGISTER CARD -->
    <div class="w-full max-w-sm bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl shadow-2xl p-8 z-10">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold tracking-tight mb-2">Create Account</h1>
            <p class="text-gray-400 text-sm">Join us to start shopping</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 text-sm p-3 rounded-lg mb-6 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/register" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Username</label>
                <input type="text" name="username" required class="w-full bg-gray-900/50 border border-gray-700 text-white rounded-xl px-4 py-3 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder-gray-600" placeholder="johndoe">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Email Address</label>
                <input type="email" name="email" required class="w-full bg-gray-900/50 border border-gray-700 text-white rounded-xl px-4 py-3 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder-gray-600" placeholder="name@example.com">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1 ml-1">Password</label>
                <input type="password" name="password" required class="w-full bg-gray-900/50 border border-gray-700 text-white rounded-xl px-4 py-3 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition placeholder-gray-600" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-500/20 transition-all transform active:scale-95 mt-2">
                Sign Up
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-gray-500">
            Already have an account? 
            <a href="/login" class="text-blue-400 hover:text-blue-300 font-semibold transition">Sign In</a>
        </div>
    </div>

    <!-- DEVELOPER CREDIT -->
    <div class="mt-8 text-center">
        <p class="text-[10px] text-gray-600 uppercase tracking-widest">
            Developed By 
            <a href="https://areativedigital.com/" target="_blank" class="text-gray-500 hover:text-blue-400 transition font-bold">Areative</a>
        </p>
    </div>

    <!-- MOBILE FOOTER (ONLY VISIBLE ON MOBILE) -->
    <div class="glass-bar fixed bottom-0 left-0 w-full z-50 md:hidden pb-safe border-t border-white/10 bg-[#0f172a]/90 backdrop-blur-xl">
        <div class="grid grid-cols-4 h-16">
            <a href="/" class="flex flex-col items-center justify-center text-gray-400 hover:text-blue-400 transition active:scale-95">
                <i class="fa-solid fa-house mb-1 text-xl"></i>
                <span class="text-[10px] font-medium">Home</span>
            </a>
            <a href="/shop" class="flex flex-col items-center justify-center text-gray-400 hover:text-blue-400 transition active:scale-95">
                <i class="fa-solid fa-store mb-1 text-xl"></i>
                <span class="text-[10px] font-medium">Shop</span>
            </a>
            <a href="/cart" class="flex flex-col items-center justify-center text-gray-400 hover:text-blue-400 transition relative active:scale-95">
                <i class="fa-solid fa-cart-shopping mb-1 text-xl"></i>
                <span class="text-[10px] font-medium">Cart</span>
            </a>
            <a href="/login" class="flex flex-col items-center justify-center text-blue-400 transition active:scale-95">
                <i class="fa-solid fa-right-to-bracket mb-1 text-xl"></i>
                <span class="text-[10px] font-medium">Login</span>
            </a>
        </div>
    </div>

</body>
</html>