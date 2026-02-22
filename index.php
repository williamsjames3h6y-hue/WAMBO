<?php
require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Load VIP tiers
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare('SELECT * FROM vip_tiers ORDER BY level ASC');
$stmt->execute();
$tiers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EarningsLLC - Professional Data Annotation Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        @keyframes zoom-in {
            from { opacity: 0; transform: scale(1.1); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slide-fade {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
        .animate-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }
        .animate-scroll { animation: scroll 20s linear infinite; }
        .animate-scroll:hover { animation-play-state: paused; }
        .animate-zoom-in { animation: zoom-in 0.8s ease-out; }
        .animate-slide-fade { animation: slide-fade 0.6s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="bg-slate-900/80 backdrop-blur-md shadow-lg sticky top-0 z-40 border-b border-emerald-500/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3 group">
                    <img src="/logo.jpg" alt="EarningsLLC Logo" class="w-12 h-12 rounded-lg object-cover shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <span class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
                        EarningsLLC
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="login.php" class="bg-slate-800 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-slate-700 transition-all border border-emerald-500/30 hover:border-emerald-500 hover:scale-105">
                        Log in
                    </a>
                    <a href="register.php" class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-6 py-2.5 rounded-lg font-semibold hover:from-emerald-500 hover:to-emerald-400 transition-all shadow-lg hover:shadow-emerald-500/50 hover:scale-105">
                        Sign up
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-20 w-96 h-96 bg-emerald-500 rounded-full filter blur-3xl animate-float"></div>
            <div class="absolute bottom-10 right-20 w-96 h-96 bg-cyan-500 rounded-full filter blur-3xl animate-float" style="animation-delay: 1.5s;"></div>
        </div>
        <div class="grid lg:grid-cols-2 gap-12 items-center relative z-10">
            <div class="animate-slide-fade">
                <h1 class="text-6xl md:text-7xl font-bold text-white mb-8 leading-tight drop-shadow-lg">
                    Earn Money With
                    <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent block mt-2">
                        Brand Identification Tasks
                    </span>
                </h1>
                <p class="text-2xl text-slate-300 mb-12 leading-relaxed">
                    Identify brand names from images and earn up to $5000+ per week with VIP 5.
                    Start free, upgrade for higher weekly earnings and more tasks.
                    Your path to earning starts here.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="register.php" class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-10 py-5 rounded-lg font-semibold hover:from-emerald-500 hover:to-emerald-400 transition-all duration-300 text-xl flex items-center justify-center space-x-2 shadow-xl shadow-emerald-500/30 hover:shadow-2xl hover:shadow-emerald-500/50 hover:scale-105 group">
                        <span>Start Earning Free</span>
                        <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a href="#how-it-works" class="bg-slate-800 text-white px-10 py-5 rounded-lg font-semibold hover:bg-slate-700 transition-all duration-300 text-xl border border-emerald-500/30 hover:border-emerald-500 hover:scale-105">
                        How It Works
                    </a>
                </div>
            </div>
            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-3xl transform rotate-3 opacity-30 blur-sm"></div>
                <img src="/5.jpg" alt="AI Data Annotation" class="relative rounded-3xl shadow-2xl w-full hover:scale-105 transition-transform duration-500 border border-emerald-500/30">
            </div>
        </div>
    </section>

    <!-- Trusted Partners -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 overflow-hidden">
        <div class="text-center mb-16">
            <h2 class="text-5xl font-bold text-white mb-4 drop-shadow-lg">Trusted By Industry Leaders</h2>
            <p class="text-xl text-slate-300 max-w-3xl mx-auto mb-12">
                Partnering with the world's most innovative technology companies
            </p>
            <div class="relative overflow-hidden py-8">
                <div class="flex animate-scroll">
                    <div class="flex space-x-12 items-center px-6">
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/CHATGPT.webp" alt="ChatGPT" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/GOOGLE_GEMINI.png" alt="Google Gemini" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/GOOGLE.webp" alt="Google" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/SCALE_AI.png" alt="Scale AI" class="max-w-full max-h-full object-contain">
                        </div>
                    </div>
                    <div class="flex space-x-12 items-center px-6">
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/CHATGPT.webp" alt="ChatGPT" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/GOOGLE_GEMINI.png" alt="Google Gemini" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/GOOGLE.webp" alt="Google" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-shrink-0 w-48 h-32 flex items-center justify-center bg-white/5 backdrop-blur-sm rounded-xl p-6 hover:bg-white/10 transition-all duration-300 hover:scale-110 border border-emerald-500/20">
                            <img src="/SCALE_AI.png" alt="Scale AI" class="max-w-full max-h-full object-contain">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Grid -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <div class="relative overflow-hidden rounded-3xl shadow-2xl group">
                <img src="/6.jpg" alt="Business Partnership" class="w-full h-80 object-cover transform group-hover:scale-110 transition-all duration-700 ease-out">
                <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-gray-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                    <h3 class="text-white font-bold text-3xl mb-3 drop-shadow-lg">Trusted Partnerships</h3>
                    <p class="text-gray-100 text-lg drop-shadow-md">Building lasting relationships with transparency</p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-3xl shadow-2xl group">
                <img src="/7.jpg" alt="Global Network" class="w-full h-80 object-cover transform group-hover:scale-110 transition-all duration-700 ease-out">
                <div class="absolute inset-0 bg-gradient-to-t from-cyan-900/80 via-cyan-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                    <h3 class="text-white font-bold text-3xl mb-3 drop-shadow-lg">Global Network</h3>
                    <p class="text-gray-100 text-lg drop-shadow-md">Connected worldwide for maximum opportunities</p>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="relative overflow-hidden rounded-3xl shadow-2xl group">
                <img src="/8.jpg" alt="Advanced Technology" class="w-full h-80 object-cover transform group-hover:scale-110 transition-all duration-700 ease-out">
                <div class="absolute inset-0 bg-gradient-to-t from-blue-900/80 via-blue-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                    <h3 class="text-white font-bold text-3xl mb-3 drop-shadow-lg">Cutting-Edge Platform</h3>
                    <p class="text-gray-100 text-lg drop-shadow-md">Powered by advanced AI and secure infrastructure</p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-3xl shadow-2xl group">
                <img src="/9.jpg" alt="Data Analytics" class="w-full h-80 object-cover transform group-hover:scale-110 transition-all duration-700 ease-out">
                <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/80 via-emerald-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                    <h3 class="text-white font-bold text-3xl mb-3 drop-shadow-lg">Smart Analytics</h3>
                    <p class="text-gray-100 text-lg drop-shadow-md">Real-time insights and performance tracking</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Small Images Grid -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="relative overflow-hidden rounded-2xl shadow-2xl group border border-emerald-500/20">
                <img src="/2.jpg" alt="Professional Workspace" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/80 via-emerald-900/40 to-transparent flex items-end p-6">
                    <div>
                        <h3 class="text-white font-bold text-2xl mb-2 drop-shadow-lg">Simple Tasks</h3>
                        <p class="text-slate-200 text-base drop-shadow-md">Easy data optimization jobs for everyone</p>
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl shadow-2xl group border border-emerald-500/20">
                <img src="/3.jpg" alt="Data Analysis" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-cyan-900/80 via-cyan-900/40 to-transparent flex items-end p-6">
                    <div>
                        <h3 class="text-white font-bold text-2xl mb-2 drop-shadow-lg">Daily Earnings</h3>
                        <p class="text-slate-200 text-base drop-shadow-md">Get paid for completing tasks every day</p>
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl shadow-2xl group border border-emerald-500/20">
                <img src="/4.jpg" alt="AI Technology" class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/80 via-emerald-900/40 to-transparent flex items-end p-6">
                    <div>
                        <h3 class="text-white font-bold text-2xl mb-2 drop-shadow-lg">VIP Levels</h3>
                        <p class="text-slate-200 text-base drop-shadow-md">Higher tiers unlock better commissions</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Start Earning Section -->
    <section class="bg-slate-800/50 py-20 relative overflow-hidden border-y border-emerald-500/20">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-10 w-72 h-72 bg-emerald-500 rounded-full filter blur-3xl animate-float"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-cyan-500 rounded-full filter blur-3xl animate-float" style="animation-delay: 1s;"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="order-2 lg:order-1">
                    <img src="/1.jpg" alt="Big Data Processing" class="rounded-2xl shadow-2xl w-full hover:shadow-3xl hover:scale-105 transition-all duration-500 border border-emerald-500/30">
                </div>
                <div class="order-1 lg:order-2">
                    <h2 class="text-5xl md:text-6xl font-bold text-white mb-8 drop-shadow-lg">Start Earning Today</h2>
                    <p class="text-2xl text-slate-300 mb-8 leading-relaxed">
                        Join thousands of members earning daily commissions through brand identification tasks.
                        Simple work, reliable income, and flexible schedules.
                    </p>
                    <ul class="space-y-5">
                        <li class="flex items-start group">
                            <svg class="w-7 h-7 text-emerald-400 mr-4 flex-shrink-0 mt-1 group-hover:scale-125 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-slate-200 text-xl group-hover:text-white transition-colors">Weekly earnings from $100+ to $5000+ based on VIP level</span>
                        </li>
                        <li class="flex items-start group">
                            <svg class="w-7 h-7 text-emerald-400 mr-4 flex-shrink-0 mt-1 group-hover:scale-125 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-slate-200 text-xl group-hover:text-white transition-colors">Complete 35 to unlimited brand identification tasks per day</span>
                        </li>
                        <li class="flex items-start group">
                            <svg class="w-7 h-7 text-emerald-400 mr-4 flex-shrink-0 mt-1 group-hover:scale-125 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-slate-200 text-xl group-hover:text-white transition-colors">Fast payouts and secure platform</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- VIP Tiers -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20" id="pricing">
        <div class="text-center mb-16">
            <h2 class="text-5xl md:text-6xl font-bold text-white mb-6 drop-shadow-lg">Choose Your VIP Level</h2>
            <p class="text-2xl text-slate-300 max-w-3xl mx-auto">
                Higher levels unlock better weekly earnings and more daily tasks
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-6">
            <?php foreach ($tiers as $index => $tier): ?>
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border-2 transition-all duration-500 hover:scale-105 hover:shadow-2xl hover:shadow-emerald-500/30 group <?php echo $tier['level'] == 3 ? 'border-emerald-500 relative ring-2 ring-emerald-500/30' : 'border-emerald-500/30 hover:border-emerald-500'; ?>" style="animation-delay: <?php echo $index * 100; ?>ms;">
                <?php if ($tier['level'] == 3): ?>
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-center py-3 text-sm font-semibold">
                    POPULAR
                </div>
                <?php endif; ?>

                <div class="p-6">
                    <div class="inline-block px-4 py-2 rounded-full bg-gradient-to-r from-gray-500 to-gray-600 text-white text-sm font-semibold mb-4 group-hover:scale-110 transition-transform shadow-lg">
                        <?php echo htmlspecialchars($tier['name']); ?>
                    </div>

                    <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-lg group-hover:bg-emerald-500/20 transition-colors">
                        <div class="text-3xl font-bold text-emerald-400 mb-1">
                            <?php echo number_format($tier['commission_rate'], 1); ?>%
                        </div>
                        <div class="text-sm text-slate-400">Commission Rate</div>
                    </div>

                    <ul class="space-y-3 mb-6">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-emerald-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-slate-300 text-sm">
                                <?php echo $tier['max_tasks_per_day'] == 999999 ? 'Unlimited' : $tier['max_tasks_per_day']; ?> tasks/day
                            </span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-emerald-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-slate-300 text-sm font-semibold">
                                $<?php echo number_format($tier['weekly_earning_limit']); ?>+ per week
                            </span>
                        </li>
                        <?php
                        $features = json_decode($tier['features'], true);
                        if ($features && count($features) > 0):
                        ?>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-emerald-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-slate-300 text-sm"><?php echo htmlspecialchars($features[0]); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <a href="register.php" class="w-full block text-center py-3 rounded-lg font-semibold text-base transition-all duration-300 <?php echo $tier['level'] == 3 ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:shadow-lg hover:shadow-emerald-500/50 hover:scale-105' : 'bg-slate-700 text-white hover:bg-slate-600 border border-emerald-500/30 hover:border-emerald-500'; ?>">
                        <?php echo $tier['level'] == 1 ? 'Start Free' : ($tier['requires_support_contact'] ? 'Contact Support' : 'Get Started'); ?>
                    </a>

                    <?php if ($tier['requires_support_contact']): ?>
                    <p class="text-xs text-slate-400 text-center mt-2">
                        Requires support approval
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-gradient-to-r from-emerald-600 to-cyan-600 py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 right-10 w-96 h-96 bg-white rounded-full filter blur-3xl animate-float"></div>
            <div class="absolute bottom-10 left-10 w-80 h-80 bg-cyan-300 rounded-full filter blur-3xl animate-float" style="animation-delay: 1s;"></div>
        </div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-5xl md:text-6xl font-bold text-white mb-8 drop-shadow-lg">Ready to Start Earning?</h2>
            <p class="text-2xl text-white/90 mb-12 leading-relaxed drop-shadow-md">
                Join thousands of members already earning daily with brand identification tasks
            </p>
            <a href="register.php" class="bg-white text-emerald-600 px-12 py-5 rounded-lg font-semibold hover:bg-slate-50 transition-all duration-300 text-xl inline-flex items-center space-x-2 hover:shadow-2xl hover:scale-105 group shadow-lg">
                <span>Start Free with VIP 1</span>
                <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 via-slate-900 to-gray-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div class="md:col-span-1">
                    <div class="flex items-center space-x-3 mb-6">
                        <img src="/logo.jpg" alt="EarningsLLC Logo" class="w-14 h-14 rounded-lg object-cover shadow-lg">
                        <span class="text-2xl font-bold">EarningsLLC</span>
                    </div>
                    <p class="text-gray-400 text-base mb-6 leading-relaxed">
                        Earn daily commissions through brand identification tasks
                    </p>
                </div>

                <div>
                    <h3 class="text-xl font-bold mb-5">Resources</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">FAQ</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">Tutorials</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">Community</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xl font-bold mb-5">Company</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">Careers</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">Blog</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">Press Kit</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xl font-bold mb-5">Contact</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:contact@earningsllc.online" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">
                                contact@earningsllc.online
                            </a>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:+1234567890" class="text-gray-400 hover:text-emerald-400 transition-colors text-base">
                                +1 (234) 567-890
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <p class="text-gray-500 text-sm">
                        &copy; 2025 EarningsLLC. All rights reserved.
                    </p>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-500 hover:text-emerald-400 text-sm transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-500 hover:text-emerald-400 text-sm transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-500 hover:text-emerald-400 text-sm transition-colors">Accessibility</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
