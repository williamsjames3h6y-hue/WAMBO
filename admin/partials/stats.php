<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl p-6 border border-blue-500/30">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 font-semibold">Total Users</h3>
            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>
        <p class="text-4xl font-bold text-white"><?php echo number_format($totalUsers); ?></p>
    </div>

    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-xl p-6 border border-green-500/30">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 font-semibold">Active Users</h3>
            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-4xl font-bold text-white"><?php echo number_format($activeUsers); ?></p>
    </div>

    <div class="bg-gradient-to-br from-orange-500/20 to-red-500/20 rounded-xl p-6 border border-orange-500/30">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 font-semibold">Total Tasks</h3>
            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-4xl font-bold text-white"><?php echo number_format($totalTasks); ?></p>
    </div>

    <div class="bg-gradient-to-br from-yellow-500/20 to-amber-500/20 rounded-xl p-6 border border-yellow-500/30">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 font-semibold">Total Earnings</h3>
            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-4xl font-bold text-white">$<?php echo number_format($totalEarnings, 2); ?></p>
    </div>
</div>

<div class="bg-yellow-900/30 rounded-xl p-6 border border-yellow-600">
    <h3 class="text-xl font-bold text-white mb-4">System Overview</h3>
    <p class="text-yellow-100">Platform is running smoothly with <?php echo $activeUsers; ?> active users in the last 7 days.</p>
</div>
