document.addEventListener('DOMContentLoaded', function() {
    // Toggle status tugas (selesai/belum)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.task-checkbox')) {
            const checkbox = e.target.closest('.task-checkbox');
            const taskItem = checkbox.closest('.task-item');
            const taskId = taskItem.dataset.id;
            const isCompleted = checkbox.classList.contains('checked');
            const newStatus = isCompleted ? 'pending' : 'completed';
            
            const formData = new FormData();
            formData.append('action', 'update_task_status');
            formData.append('task_id', taskId);
            formData.append('status', newStatus);
            
            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    checkbox.classList.toggle('checked');
                    taskItem.classList.toggle('completed');
                    
                    // Update status text
                    const statusElement = taskItem.querySelector('.task-status');
                    if (statusElement) {
                        statusElement.textContent = newStatus === 'completed' ? 'Selesai' : 'Tertunda';
                        statusElement.className = `task-status status-${newStatus}`;
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Terjadi kesalahan: ' + error, 'error');
            });
        }
        
        // Hapus tugas
        if (e.target.closest('.delete-btn')) {
            const deleteBtn = e.target.closest('.delete-btn');
            const taskId = deleteBtn.dataset.id;
            
            if (confirm('Apakah Anda yakin ingin menghapus tugas ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete_task');
                formData.append('task_id', taskId);
                
                fetch('process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        const taskItem = deleteBtn.closest('.task-item');
                        taskItem.remove();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Terjadi kesalahan: ' + error, 'error');
                });
            }
        }
    });
// Filter tugas
const filterButtons = document.querySelectorAll('.filter-btn');

filterButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Hapus class active dari semua tombol
        filterButtons.forEach(btn => btn.classList.remove('active'));
        
        // Tambahkan class active ke tombol yang diklik
        this.classList.add('active');
        
        // Tentukan jenis filter dan nilainya
        let filterType = 'all';
        let filterValue = '';
        
        if (this.dataset.filter) {
            filterType = 'filter';
            filterValue = this.dataset.filter;
        } else if (this.dataset.category) {
            filterType = 'category';
            filterValue = this.dataset.category;
        }
        
        loadTasks(filterType, filterValue);
    });
});
    
    // Fungsi untuk memuat tugas
// Fungsi untuk memuat tugas
function loadTasks(filterType = 'all', filterValue = '') {
    const formData = new FormData();
    formData.append('action', 'get_tasks');
    formData.append('filter_type', filterType);
    formData.append('filter_value', filterValue);
    
    fetch('process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tasksList = document.getElementById('tasksList');
            tasksList.innerHTML = '';
            
            if (data.tasks.length === 0) {
                tasksList.innerHTML = '<p class="no-tasks">Tidak ada tugas yang ditemukan.</p>';
                return;
            }
            
            data.tasks.forEach(task => {
                const taskItem = document.createElement('div');
                taskItem.className = `task-item ${task.status === 'completed' ? 'completed' : ''}`;
                taskItem.dataset.id = task.id;
                
                // Format tanggal
                const dueDate = new Date(task.due_date);
                const formattedDate = dueDate.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
                
                // Dapatkan ikon berdasarkan kategori
                let categoryIcon = 'circle';
                switch(task.category) {
                    case 'Pekerjaan': categoryIcon = 'briefcase'; break;
                    case 'Personal': categoryIcon = 'home'; break;
                    case 'Belanja': categoryIcon = 'shopping-cart'; break;
                    case 'Kesehatan': categoryIcon = 'heart'; break;
                    case 'Pendidikan': categoryIcon = 'book'; break;
                }
                
                // Dapatkan teks prioritas
                let priorityText = 'Sedang';
                switch(task.priority) {
                    case 'high': priorityText = 'Tinggi'; break;
                    case 'low': priorityText = 'Rendah'; break;
                }
                
                taskItem.innerHTML = `
                    <div class="task-checkbox ${task.status === 'completed' ? 'checked' : ''}">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="task-content">
                        <h3 class="task-title">${escapeHtml(task.title)}</h3>
                        <p class="task-desc">${escapeHtml(task.description)}</p>
                        <div class="task-meta">
                            <span><i class="fas fa-calendar"></i> ${formattedDate}</span>
                            <span><i class="fas fa-${categoryIcon}"></i> ${task.category}</span>
                        </div>
                    </div>
                    <span class="task-priority priority-${task.priority}">${priorityText}</span>
                    <button class="delete-task-btn" data-id="${task.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                
                tasksList.appendChild(taskItem);
            });
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Terjadi kesalahan: ' + error, 'error');
    });
}
    
    // Fungsi untuk memperbarui statistik
    function updateStats() {
        const formData = new FormData();
        formData.append('action', 'get_stats');
        
        fetch('process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                
                // Perbarui statistik di sidebar
                document.querySelector('[data-filter="all"] .count').textContent = stats.total;
                document.querySelector('[data-filter="high"] .count').textContent = stats.high;
                document.querySelector('[data-filter="completed"] .count').textContent = stats.completed;
                document.querySelector('[data-filter="pending"] .count').textContent = stats.pending;
                
                // Perbarui kartu statistik
                document.querySelectorAll('.stat-card .value')[0].textContent = stats.total;
                document.querySelectorAll('.stat-card .value')[1].textContent = stats.completed;
                document.querySelectorAll('.stat-card .value')[2].textContent = stats.pending;
                document.querySelectorAll('.stat-card .value')[3].textContent = stats.high;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Fungsi untuk menampilkan notifikasi
    // Fungsi untuk menampilkan notifikasi
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Tampilkan notifikasi
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Sembunyikan notifikasi setelah 3 detik
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
    
    // Fungsi untuk escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});