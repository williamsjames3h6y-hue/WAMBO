function editUser(userId) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

async function updateUserBalance() {
    const userId = document.getElementById('edit_user_id').value;
    const amount = document.getElementById('balance_amount').value;
    const operation = document.getElementById('balance_operation').value;

    if (!amount) {
        alert('Please enter an amount');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_user_balance');
    formData.append('user_id', userId);
    formData.append('amount', amount);
    formData.append('operation', operation);

    const response = await fetch('/api/admin_handler.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    if (data.success) {
        alert('Balance updated successfully!');
        location.reload();
    } else {
        alert('Error: ' + data.error);
    }
}

async function updateUserVIP() {
    const userId = document.getElementById('edit_user_id').value;
    const vipTierId = document.getElementById('vip_tier_id').value;

    const formData = new FormData();
    formData.append('action', 'update_user_vip');
    formData.append('user_id', userId);
    formData.append('vip_tier_id', vipTierId);

    const response = await fetch('/api/admin_handler.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    if (data.success) {
        alert('VIP tier updated successfully!');
        location.reload();
    } else {
        alert('Error: ' + data.error);
    }
}

function showAddTaskModal() {
    document.getElementById('taskModalTitle').textContent = 'Add Task';
    document.getElementById('taskForm').reset();
    document.getElementById('task_id').value = '';
    document.getElementById('taskModal').classList.remove('hidden');
}

function editTask(taskId) {
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    document.getElementById('task_id').value = taskId;
    document.getElementById('taskModal').classList.remove('hidden');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
}

document.getElementById('taskForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const imageUrl = document.getElementById('task_image_url').value;
    if (!imageUrl) {
        alert('Please provide an image URL');
        return;
    }

    const taskId = document.getElementById('task_id').value;
    const formData = new FormData();

    formData.append('action', taskId ? 'update_task' : 'add_task');
    if (taskId) formData.append('task_id', taskId);
    formData.append('product_name', document.getElementById('task_product_name').value);
    formData.append('brand_name', document.getElementById('task_brand_name').value || '');
    formData.append('price', document.getElementById('task_price').value || '0');
    formData.append('earning_amount', document.getElementById('earning_amount').value);
    formData.append('image_url', imageUrl);

    try {
        const response = await fetch('/api/admin_handler.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            alert(taskId ? 'Task updated successfully!' : 'Task added successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

async function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_task');
    formData.append('task_id', taskId);

    const response = await fetch('/api/admin_handler.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    if (data.success) {
        alert('Task deleted successfully!');
        location.reload();
    } else {
        alert('Error: ' + data.error);
    }
}

document.getElementById('siteInfoForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    await updateSettings('site_info');
});

document.getElementById('paymentSettingsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    await updateSettings('payment');
});

async function updateSettings(settingsType) {
    const formData = new FormData();
    formData.append('action', 'update_settings');
    formData.append('settings_type', settingsType);

    if (settingsType === 'site_info') {
        formData.append('site_name', document.getElementById('site_name').value);
        formData.append('site_description', document.getElementById('site_description').value);
        formData.append('support_email', document.getElementById('support_email').value);
    } else if (settingsType === 'payment') {
        formData.append('min_withdrawal', document.getElementById('min_withdrawal').value);
        formData.append('processing_fee', document.getElementById('processing_fee').value);
    }

    const response = await fetch('/api/admin_handler.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    if (data.success) {
        alert('Settings updated successfully!');
        location.reload();
    } else {
        alert('Error: ' + data.error);
    }
}

function openTrainingAccountModal() {
    document.getElementById('trainingAccountModal').classList.remove('hidden');
}

function closeTrainingAccountModal() {
    document.getElementById('trainingAccountModal').classList.add('hidden');
    document.getElementById('trainingAccountForm').reset();
}

async function createTrainingAccount(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    formData.append('action', 'create_training_account');

    try {
        const response = await fetch('/api/admin_handler.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert('Training account created successfully!\\n\\nEmail: ' + data.email);
            closeTrainingAccountModal();
            location.reload();
        } else {
            alert('Error: ' + (data.error || data.message));
        }
    } catch (error) {
        alert('Error creating training account: ' + error.message);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('trainingAccountModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeTrainingAccountModal();
            }
        });
    }
});
