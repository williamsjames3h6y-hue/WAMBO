<div class="bg-yellow-900/30 rounded-xl border border-yellow-600 overflow-hidden">
    <div class="p-4 border-b border-yellow-600 flex justify-between items-center">
        <div>
            <h3 class="text-xl font-bold text-white">User Management</h3>
            <p class="text-yellow-100 text-sm">Manage user accounts, balances, and VIP tiers</p>
        </div>
        <button onclick="openTrainingAccountModal()" class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 shadow-lg">
            <span>🎓</span>
            <span>Create Training Account</span>
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-yellow-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-yellow-200 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-yellow-200 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-yellow-200 uppercase tracking-wider">VIP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-yellow-200 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-yellow-200 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-yellow-700">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-yellow-800/20 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($user['full_name'] ?? 'Unknown'); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-yellow-100"><?php echo htmlspecialchars($user['email']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-400">
                            <?php echo htmlspecialchars($user['vip_name'] ?? 'VIP 1'); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-semibold">
                        $<?php echo number_format($user['balance'] ?? 0, 2); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="editUser('<?php echo $user['id']; ?>')" class="text-blue-400 hover:text-blue-300 mr-3">Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
