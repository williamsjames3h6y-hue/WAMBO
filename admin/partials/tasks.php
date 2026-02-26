<div class="mb-6">
    <button onclick="showAddTaskModal()" class="bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-lg">
        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add New Task
    </button>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($tasks as $task): ?>
    <div class="bg-yellow-900/30 rounded-xl border border-yellow-600 overflow-hidden hover:border-yellow-400/80 transition-all">
        <div class="aspect-video bg-slate-800 flex items-center justify-center">
            <img src="<?php echo htmlspecialchars($task['image_url']); ?>" alt="<?php echo htmlspecialchars($task['product_name'] ?? $task['brand_name']); ?>" class="w-full h-full object-cover" />
        </div>
        <div class="p-4">
            <h4 class="text-white font-bold text-lg mb-1"><?php echo htmlspecialchars($task['product_name'] ?? $task['brand_name']); ?></h4>
            <?php if (!empty($task['brand_name']) && !empty($task['product_name'])): ?>
            <p class="text-yellow-300 text-sm mb-2"><?php echo htmlspecialchars($task['brand_name']); ?></p>
            <?php endif; ?>
            <div class="flex justify-between items-center mb-3">
                <?php if (!empty($task['price'])): ?>
                <span class="text-white text-sm">$<?php echo number_format($task['price'], 2); ?></span>
                <?php endif; ?>
                <span class="text-emerald-400 font-semibold">Earn: $<?php echo number_format($task['earning_amount'] ?? 0, 2); ?></span>
                <span class="text-cyan-400 text-sm">Order: <?php echo $task['task_order']; ?></span>
                <span class="text-blue-400 text-sm">VIP <?php echo $task['vip_level_required']; ?>+</span>
            </div>
            <div class="flex space-x-2">
                <button onclick="editTask('<?php echo $task['id']; ?>')" class="flex-1 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 px-3 py-2 rounded-lg transition-all text-sm">Edit</button>
                <button onclick="deleteTask('<?php echo $task['id']; ?>')" class="flex-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 px-3 py-2 rounded-lg transition-all text-sm">Delete</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
