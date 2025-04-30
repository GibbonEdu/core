function notify(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            ${message}
        </div>
        <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
    `;
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function generateStatsHTML(data) {
    return `
        <div class="stats-container">
            <div class="stats-summary">
                <h4>Training Data Summary</h4>
                <p>Total Items: ${data.total || 0}</p>
                <p>Categories: ${data.categories?.length || 0}</p>
                <p>Last Updated: ${data.lastUpdate || 'Never'}</p>
            </div>
            <div class="stats-details">
                <h4>Category Breakdown</h4>
                ${generateCategoryBreakdown(data.categories)}
            </div>
        </div>
    `;
}

function generateCategoryBreakdown(categories = []) {
    if (!categories.length) return '<p>No categories available</p>';
    
    return `
        <ul class="category-list">
            ${categories.map(cat => `
                <li>
                    <span class="category-name">${cat.name}</span>
                    <span class="category-count">${cat.count}</span>
                </li>
            `).join('')}
        </ul>
    `;
} 