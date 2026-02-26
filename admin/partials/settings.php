<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-yellow-900/30 rounded-xl border border-yellow-600 p-6">
        <h3 class="text-xl font-bold text-white mb-4">Site Information</h3>
        <form id="siteInfoForm" class="space-y-4">
            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Site Name</label>
                <input type="text" id="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'EarningsLLC'); ?>" class="w-full bg-yellow-900/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
            </div>
            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Site Description</label>
                <textarea id="site_description" rows="3" class="w-full bg-yellow-900/50 border border-yellow-600 rounded-lg px-4 py-2 text-white"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Support Email</label>
                <input type="email" id="support_email" value="<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>" class="w-full bg-yellow-900/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold py-3 rounded-lg transition-all">Update Site Info</button>
        </form>
    </div>

    <div class="bg-yellow-900/30 rounded-xl border border-yellow-600 p-6">
        <h3 class="text-xl font-bold text-white mb-4">Payment Settings</h3>
        <form id="paymentSettingsForm" class="space-y-4">
            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Minimum Withdrawal</label>
                <input type="number" step="0.01" id="min_withdrawal" value="<?php echo htmlspecialchars($settings['min_withdrawal'] ?? '10.00'); ?>" class="w-full bg-yellow-900/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
            </div>
            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Processing Fee (%)</label>
                <input type="number" step="0.01" id="processing_fee" value="<?php echo htmlspecialchars($settings['processing_fee'] ?? '2.00'); ?>" class="w-full bg-yellow-900/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold py-3 rounded-lg transition-all">Update Payment Settings</button>
        </form>
    </div>
</div>
