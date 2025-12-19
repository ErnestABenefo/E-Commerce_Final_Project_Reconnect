<!-- Global Search Bar Component -->
<div class="search-container">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="globalSearch" placeholder="Search users, products, events, jobs..." autocomplete="off">
    </div>
    <div id="searchResults" class="search-results"></div>
</div>

<style>
/* Search Bar Styles */
.search-container {
    position: relative;
    flex: 1;
    max-width: 500px;
    margin: 0 20px;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box i {
    position: absolute;
    left: 15px;
    color: #666;
}

.search-box input {
    width: 100%;
    padding: 10px 15px 10px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 0.95rem;
    transition: all 0.3s;
    outline: none;
}

.search-box input:focus {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    background: white;
}

.search-results {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    max-height: 500px;
    overflow-y: auto;
    z-index: 10000;
    border: 1px solid #e0e0e0;
}

.search-results.active {
    display: block;
}

.search-category {
    padding: 15px 20px;
}

.search-category-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.search-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    color: inherit;
}

.search-item:hover {
    background: #f8f9fa;
}

.search-item-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #667eea;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.search-item-icon img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.search-item-content {
    flex: 1;
    min-width: 0;
}

.search-item-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-item-meta {
    font-size: 0.85rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-empty {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.search-empty i {
    font-size: 3rem;
    margin-bottom: 10px;
    opacity: 0.3;
}

@media (max-width: 1200px) {
    .search-container {
        max-width: 300px;
    }
}

@media (max-width: 992px) {
    .search-container {
        max-width: 250px;
    }
}

@media (max-width: 768px) {
    .search-container {
        order: 3;
        max-width: 100%;
        width: 100%;
        margin: 10px 0 0 0;
    }
}
</style>

<script>
// Global Search Functionality
(function() {
    let searchTimeout;
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                searchResults.innerHTML = '';
                return;
            }
            
            // Show loading
            searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-spinner fa-spin"></i><p>Searching...</p></div>';
            searchResults.classList.add('active');
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
    }

    async function performSearch(query) {
        try {
            const response = await fetch(`../actions/global_search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            displaySearchResults(data);
        } catch (error) {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-exclamation-circle"></i><p>Search failed</p></div>';
        }
    }

    function displaySearchResults(data) {
        const hasResults = Object.values(data).some(arr => arr.length > 0);
        
        if (!hasResults) {
            searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-search"></i><p>No results found</p></div>';
            searchResults.classList.add('active');
            return;
        }
        
        let html = '';
        
        // Users
        if (data.users && data.users.length > 0) {
            html += '<div class="search-category">';
            html += '<div class="search-category-title"><i class="fas fa-users"></i> People</div>';
            data.users.forEach(user => {
                const initials = user.name.split(' ').map(n => n[0]).join('');
                const photoHtml = user.photo 
                    ? `<img src="${user.photo}" alt="${escapeHtml(user.name)}">` 
                    : initials;
                const verifiedBadge = user.is_verified 
                    ? '<span style="display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:600;margin-left:6px;"><i class="fas fa-check-circle"></i> Verified</span>' 
                    : '';
                html += `
                    <a href="${user.url}" class="search-item">
                        <div class="search-item-icon">${photoHtml}</div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(user.name)}${verifiedBadge}</div>
                            <div class="search-item-meta">${escapeHtml(user.email)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Universities
        if (data.universities && data.universities.length > 0) {
            html += '<div class="search-category">';
            html += '<div class="search-category-title"><i class="fas fa-university"></i> Universities</div>';
            data.universities.forEach(uni => {
                html += `
                    <a href="${uni.url}" class="search-item">
                        <div class="search-item-icon"><i class="fas fa-university"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(uni.name)}</div>
                            <div class="search-item-meta">${escapeHtml(uni.location)} • ${escapeHtml(uni.type)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Products
        if (data.products && data.products.length > 0) {
            html += '<div class="search-category">';
            html += '<div class="search-category-title"><i class="fas fa-tag"></i> Marketplace</div>';
            data.products.forEach(product => {
                const imageHtml = product.image 
                    ? `<img src="${product.image}" alt="${escapeHtml(product.title)}">` 
                    : '<i class="fas fa-box"></i>';
                html += `
                    <a href="${product.url}" class="search-item">
                        <div class="search-item-icon">${imageHtml}</div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(product.title)}</div>
                            <div class="search-item-meta">GH₵${product.price} • ${escapeHtml(product.category)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Events
        if (data.events && data.events.length > 0) {
            html += '<div class="search-category">';
            html += '<div class="search-category-title"><i class="fas fa-calendar"></i> Events</div>';
            data.events.forEach(event => {
                const date = new Date(event.date);
                const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                html += `
                    <a href="${event.url}" class="search-item">
                        <div class="search-item-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(event.title)}</div>
                            <div class="search-item-meta">${dateStr} • ${escapeHtml(event.location)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Jobs
        if (data.jobs && data.jobs.length > 0) {
            html += '<div class="search-category">';
            html += '<div class="search-category-title"><i class="fas fa-briefcase"></i> Jobs</div>';
            data.jobs.forEach(job => {
                html += `
                    <a href="${job.url}" class="search-item">
                        <div class="search-item-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(job.title)}</div>
                            <div class="search-item-meta">${escapeHtml(job.company)} • ${escapeHtml(job.location)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        // Groups
        if (data.groups && data.groups.length > 0) {
            html += '<div class="search-category">';
            html += '<div class="search-category-title"><i class="fas fa-users"></i> Groups</div>';
            data.groups.forEach(group => {
                html += `
                    <a href="${group.url}" class="search-item">
                        <div class="search-item-icon"><i class="fas fa-users"></i></div>
                        <div class="search-item-content">
                            <div class="search-item-title">${escapeHtml(group.name)}</div>
                            <div class="search-item-meta">${escapeHtml(group.privacy)} group</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        searchResults.innerHTML = html;
        searchResults.classList.add('active');
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            searchResults.classList.remove('active');
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"'`]/g, function(match) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','`':'&#96;'}[match];
        });
    }
})();
</script>
