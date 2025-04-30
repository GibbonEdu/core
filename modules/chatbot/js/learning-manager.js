class LearningManager {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.totalItems = 0;
        this.filters = {
            search: '',
            category: '',
            status: ''
        };
        
        this.initializeEventListeners();
        this.loadStats();
        this.loadData();
    }

    initializeEventListeners() {
        // Search input
        document.getElementById('searchInput').addEventListener('input', (e) => {
            this.filters.search = e.target.value;
            this.currentPage = 1;
            this.loadData();
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', (e) => {
            this.filters.category = e.target.value;
            this.currentPage = 1;
            this.loadData();
        });

        // Status filter
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.currentPage = 1;
            this.loadData();
        });

        // Add/Edit form submission
        document.getElementById('learningForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveItem();
        });

        // CSV upload
        document.getElementById('csvUpload').addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.uploadCSV(e.target.files[0]);
            }
        });

        // Export button
        document.getElementById('exportBtn').addEventListener('click', () => {
            window.location.href = './api/learning.php?action=export';
        });

        // Pagination controls
        document.getElementById('prevPage').addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadData();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (this.currentPage * this.itemsPerPage < this.totalItems) {
                this.currentPage++;
                this.loadData();
            }
        });
    }

    async loadStats() {
        try {
            const response = await fetch('./api/learning.php?action=stats');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('totalQuestions').textContent = data.totalQuestions;
                document.getElementById('approvedItems').textContent = data.approvedItems;
                document.getElementById('lastUpdate').textContent = new Date(data.lastUpdate).toLocaleString();
            }
        } catch (error) {
            this.showNotification('Error loading statistics', 'error');
        }
    }

    async loadData() {
        try {
            const queryParams = new URLSearchParams({
                action: 'list',
                page: this.currentPage,
                limit: this.itemsPerPage,
                search: this.filters.search,
                category: this.filters.category,
                status: this.filters.status
            });

            const response = await fetch(`./api/learning.php?${queryParams}`);
            const data = await response.json();
            
            if (data.success) {
                this.totalItems = data.total;
                this.renderTable(data.items);
                this.updatePagination();
                this.updateShowingInfo();
            }
        } catch (error) {
            this.showNotification('Error loading data', 'error');
        }
    }

    renderTable(items) {
        const tbody = document.getElementById('learningTableBody');
        tbody.innerHTML = '';

        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.question}</td>
                <td>${item.answer}</td>
                <td>${item.category}</td>
                <td>${item.approved ? 'Yes' : 'No'}</td>
                <td>
                    <button onclick="learningManager.editItem(${item.id})" class="ml-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="learningManager.deleteItem(${item.id})" class="ml-2 text-red-500">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    updatePagination() {
        const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
        document.getElementById('currentPage').textContent = this.currentPage;
        document.getElementById('totalPages').textContent = totalPages;
        
        document.getElementById('prevPage').disabled = this.currentPage === 1;
        document.getElementById('nextPage').disabled = this.currentPage === totalPages;
    }

    updateShowingInfo() {
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
        document.getElementById('showingInfo').textContent = 
            `Showing ${start} to ${end} of ${this.totalItems} items`;
    }

    async saveItem() {
        const form = document.getElementById('learningForm');
        const formData = new FormData(form);
        const data = {
            question: formData.get('question'),
            answer: formData.get('answer'),
            category: formData.get('category'),
            approved: formData.get('approved') === 'true'
        };

        if (form.dataset.itemId) {
            data.id = parseInt(form.dataset.itemId);
        }

        try {
            const response = await fetch('./api/learning.php?action=save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                this.showNotification('Item saved successfully', 'success');
                this.closeModal();
                this.loadData();
                this.loadStats();
            }
        } catch (error) {
            this.showNotification('Error saving item', 'error');
        }
    }

    async editItem(id) {
        try {
            const response = await fetch(`./api/learning.php?action=get&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                const form = document.getElementById('learningForm');
                form.dataset.itemId = id;
                form.elements.question.value = data.item.question;
                form.elements.answer.value = data.item.answer;
                form.elements.category.value = data.item.category;
                form.elements.approved.value = data.item.approved ? 'true' : 'false';
                
                this.openModal();
            }
        } catch (error) {
            this.showNotification('Error loading item', 'error');
        }
    }

    async deleteItem(id) {
        if (confirm('Are you sure you want to delete this item?')) {
            try {
                const response = await fetch(`./api/learning.php?action=delete&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Item deleted successfully', 'success');
                    this.loadData();
                    this.loadStats();
                }
            } catch (error) {
                this.showNotification('Error deleting item', 'error');
            }
        }
    }

    async uploadCSV(file) {
        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch('./api/learning.php?action=upload', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.showNotification('CSV imported successfully', 'success');
                this.loadData();
                this.loadStats();
            } else {
                this.showNotification(data.message || 'Error importing CSV', 'error');
            }
        } catch (error) {
            this.showNotification('Error uploading CSV', 'error');
        }

        // Reset file input
        document.getElementById('csvUpload').value = '';
    }

    openModal() {
        document.getElementById('learningModal').classList.remove('hidden');
    }

    closeModal() {
        const form = document.getElementById('learningForm');
        form.reset();
        delete form.dataset.itemId;
        document.getElementById('learningModal').classList.add('hidden');
    }

    showNotification(message, type) {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.classList.remove('hidden');
        
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 3000);
    }
}

// Initialize the learning manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.learningManager = new LearningManager();
}); 