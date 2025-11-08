// Global Search Functionality
(function() {
    let searchTimeout;
    const searchInput = document.getElementById('globalSearchInput');
    const resultsDropdown = document.getElementById('searchResults');
    
    if (!searchInput || !resultsDropdown) return;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            resultsDropdown.classList.add('d-none');
        }
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            performSearch(this.value.trim());
        }
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.classList.add('d-none');
        }
    });
    
    function performSearch(query) {
        fetch('<?= base_url('search/ajax') ?>?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.results.length > 0) {
                    displayResults(data.results, query);
                } else {
                    resultsDropdown.classList.add('d-none');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                resultsDropdown.classList.add('d-none');
            });
    }
    
    function displayResults(results, query) {
        // Group by type
        const grouped = {};
        results.forEach(result => {
            const type = result.type || 'other';
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(result);
        });
        
        let html = '<div class="search-results-list">';
        
        for (const [type, items] of Object.entries(grouped)) {
            html += `<div class="search-result-group">
                <div class="search-result-header">${type.charAt(0).toUpperCase() + type.slice(1)}</div>`;
            
            items.slice(0, 3).forEach(item => {
                const title = getItemTitle(type, item);
                const url = getItemUrl(type, item.id);
                html += `<a href="${url}" class="search-result-item">
                    <strong>${highlightQuery(title, query)}</strong>
                    ${getItemSubtitle(type, item) ? '<small class="text-muted">' + getItemSubtitle(type, item) + '</small>' : ''}
                </a>`;
            });
            
            if (items.length > 3) {
                const pathParts = window.location.pathname.split('/').filter(p => p);
                pathParts.pop();
                const basePath = pathParts.length > 0 ? '/' + pathParts.join('/') + '/' : '/';
                const baseUrl = window.location.origin + basePath;
                html += `<a href="${baseUrl}search?q=${encodeURIComponent(query)}&module=${type}" class="search-result-more">
                    View ${items.length - 3} more...
                </a>`;
            }
            
            html += '</div>';
        }
        
        const pathParts = window.location.pathname.split('/').filter(p => p);
        pathParts.pop();
        const basePath = pathParts.length > 0 ? '/' + pathParts.join('/') + '/' : '/';
        const baseUrl = window.location.origin + basePath;
        html += `<a href="${baseUrl}search?q=${encodeURIComponent(query)}" class="search-result-view-all">View all results →</a>`;
        html += '</div>';
        
        resultsDropdown.innerHTML = html;
        resultsDropdown.classList.remove('d-none');
    }
    
    function getItemTitle(type, item) {
        switch(type) {
            case 'customer': return (item.name || '') + ' (' + (item.customer_code || '') + ')';
            case 'invoice': return (item.invoice_number || 'Invoice #' + item.id);
            case 'booking': return (item.booking_number || 'Booking #' + item.id);
            case 'item': return (item.name || '') + ' (' + (item.item_code || '') + ')';
            case 'vendor': return (item.name || '') + ' (' + (item.vendor_code || '') + ')';
            case 'transaction': return (item.reference || 'Transaction #' + item.id);
            case 'property': return (item.name || '') + ' (' + (item.property_code || '') + ')';
            default: return 'Item #' + (item.id || '');
        }
    }
    
    function getItemSubtitle(type, item) {
        switch(type) {
            case 'invoice': return item.total_amount ? formatCurrency(item.total_amount) : '';
            case 'booking': return item.total_amount ? formatCurrency(item.total_amount) : '';
            case 'transaction': return item.amount ? formatCurrency(item.amount) : '';
            case 'customer':
            case 'vendor': return item.email || '';
            default: return '';
        }
    }
    
    function getItemUrl(type, id) {
        const pathParts = window.location.pathname.split('/').filter(p => p);
        pathParts.pop();
        const basePath = pathParts.length > 0 ? '/' + pathParts.join('/') + '/' : '/';
        const baseUrl = window.location.origin + basePath;
        
        const urls = {
            customer: baseUrl + 'customers/view/' + id,
            invoice: baseUrl + 'receivables/invoices/view/' + id,
            booking: baseUrl + 'bookings/view/' + id,
            item: baseUrl + 'inventory/items/view/' + id,
            vendor: baseUrl + 'payables/vendors/view/' + id,
            transaction: baseUrl + 'ledger/transactions/view/' + id,
            property: baseUrl + 'properties/view/' + id
        };
        return urls[type] || '#';
    }
    
    function highlightQuery(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN'
        }).format(amount);
    }
})();

