<!-- Edit User Modal -->
<div id="editUserModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-yellow-900 rounded-2xl max-w-md w-full p-6 border border-yellow-600">
        <h3 class="text-2xl font-bold text-white mb-6">Edit User</h3>
        <form id="editUserForm" class="space-y-4">
            <input type="hidden" id="edit_user_id" name="user_id">

            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Balance Adjustment</label>
                <div class="flex space-x-2">
                    <input type="number" step="0.01" id="balance_amount" placeholder="Amount" class="flex-1 bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
                    <select id="balance_operation" class="bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
                        <option value="add">Add</option>
                        <option value="subtract">Subtract</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">VIP Tier</label>
                <select id="vip_tier_id" class="w-full bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
                    <?php foreach ($vipTiers as $tier): ?>
                    <option value="<?php echo $tier['id']; ?>"><?php echo htmlspecialchars($tier['name']); ?> (Level <?php echo $tier['level']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="button" onclick="updateUserBalance()" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg transition-all">Update Balance</button>
                <button type="button" onclick="updateUserVIP()" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-lg transition-all">Update VIP</button>
            </div>
            <button type="button" onclick="closeEditUserModal()" class="w-full bg-yellow-700 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg transition-all">Close</button>
        </form>
    </div>
</div>

<!-- Task Modal -->
<div id="taskModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-yellow-900 rounded-2xl max-w-2xl w-full p-6 border border-yellow-600 max-h-[90vh] overflow-y-auto">
        <h3 id="taskModalTitle" class="text-2xl font-bold text-white mb-6">Add Task</h3>
        <form id="taskForm" class="space-y-4">
            <input type="hidden" id="task_id" name="task_id">

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-yellow-100 text-sm font-semibold mb-2">Product Name *</label>
                    <input type="text" id="task_product_name" required class="w-full bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white" placeholder="e.g., iPhone 15 Pro">
                </div>

                <div>
                    <label class="block text-yellow-100 text-sm font-semibold mb-2">Brand Name</label>
                    <input type="text" id="task_brand_name" class="w-full bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white" placeholder="e.g., Apple">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-yellow-100 text-sm font-semibold mb-2">Product Price ($)</label>
                    <input type="number" step="0.01" id="task_price" class="w-full bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white" placeholder="999.00">
                </div>

                <div>
                    <label class="block text-yellow-100 text-sm font-semibold mb-2">Earning Amount ($) *</label>
                    <input type="number" step="0.01" id="earning_amount" required class="w-full bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white" placeholder="2.10">
                </div>
            </div>

            <div>
                <label class="block text-yellow-100 text-sm font-semibold mb-2">Image URL *</label>
                <input type="text" id="task_image_url" placeholder="/public/AI.jpg or https://..." class="w-full bg-yellow-800/50 border border-yellow-600 rounded-lg px-4 py-2 text-white">
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" id="saveTaskBtn" class="flex-1 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold py-3 rounded-lg transition-all">Save Task</button>
                <button type="button" onclick="closeTaskModal()" class="flex-1 bg-yellow-700 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg transition-all">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Training Account Modal -->
<div id="trainingAccountModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-gradient-to-br from-yellow-900 to-amber-900 rounded-xl border border-yellow-600 max-w-md w-full shadow-2xl" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-yellow-600 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <span>🎓</span>
                <span>Create Training Account</span>
            </h3>
            <button onclick="closeTrainingAccountModal()" class="text-yellow-300 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="trainingAccountForm" onsubmit="createTrainingAccount(event)" class="p-6 space-y-4">
            <div>
                <label class="block text-yellow-200 text-sm font-medium mb-2">Full Name</label>
                <input type="text" name="full_name" required class="w-full px-4 py-2 bg-yellow-800/30 border border-yellow-600 rounded-lg text-white placeholder-yellow-400 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter full name">
            </div>
            <div>
                <label class="block text-yellow-200 text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 bg-yellow-800/30 border border-yellow-600 rounded-lg text-white placeholder-yellow-400 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter email">
            </div>
            <div>
                <label class="block text-yellow-200 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 bg-yellow-800/30 border border-yellow-600 rounded-lg text-white placeholder-yellow-400 focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Enter password (min 6 characters)">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-lg">
                    Create Account
                </button>
                <button type="button" onclick="closeTrainingAccountModal()" class="px-6 py-3 bg-yellow-800/30 hover:bg-yellow-800/50 text-yellow-200 rounded-lg font-semibold transition-all border border-yellow-600">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
