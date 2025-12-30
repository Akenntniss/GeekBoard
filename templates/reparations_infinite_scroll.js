<script>
/**
    * INFINITE SCROLL FOR REPAIRS
    * Automatically loads more repairs when user scrolls to bottom
    */
    (function() {
        'use strict';

    const InfiniteScroll = {
        currentPage: <?php echo isset($pagination['current_page']) ? $pagination['current_page'] : 1; ?>,
    isLoading: false,
    hasMore: <?php echo isset($pagination['has_more']) && $pagination['has_more'] ? 'true' : 'false'; ?>,

    init() {
        console.log('🚀 Infinite Scroll initialized - Page:', this.currentPage, 'Has more:', this.hasMore);

            // Add scroll listener
            window.addEventListener('scroll', () => this.handleScroll());

    // Add loader UI
    this.createLoader();
        },

    createLoader() {
            const loader = document.createElement('div');
    loader.id = 'infinite-scroll-loader';
    loader.style.cssText = `
    display: none;
    text-align: center;
    padding: 2rem;
    margin: 2rem 0;
    `;
    loader.innerHTML = `
    <div class="loading-dots" style="display: flex; gap: 8px; justify-content: center; margin-bottom: 20px;">
        <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: -0.32s;"></div>
        <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: -0.16s;"></div>
        <div class="dot" style="width: 12px; height: 12px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; animation: dotPulse 1.4s ease-in-out infinite both; animation-delay: 0s;"></div>
    </div>
    <p style="color: #64748b; font-size: 16px; font-weight: 500; margin: 0;">Chargement...</p>
    `;

    const container = document.querySelector('.repair-cards-container');
    if (container) {
        container.parentElement.appendChild(loader);
            }
        },

    handleScroll() {
            if (this.isLoading || !this.hasMore) return;

    // Check if near bottom
    const scrollPosition = window.innerHeight + window.scrollY;
    const pageHeight = document.documentElement.scrollHeight;
    const triggerDistance = 500; // pixels from bottom
            
            if (scrollPosition >= pageHeight - triggerDistance) {
        this.loadMore();
            }
        },

    async loadMore() {
            if (this.isLoading || !this.hasMore) return;

    this.isLoading = true;
    this.showLoader();

    try {
                const nextPage = this.currentPage + 1;

    // Build URL with current filters
    const params = new URLSearchParams(window.location.search);
    params.set('page', nextPage);

    const url = 'ajax/load_more_repairs.php?' + params.toString();

    console.log('📥 Loading page', nextPage, 'from:', url);

    const response = await fetch(url);
    const data = await response.json();

    if (data.success && data.html) {
        this.appendCards(data.html);
    this.currentPage = nextPage;
    this.hasMore = data.has_more;

    console.log('✅ Loaded', data.count, 'repairs. Has more:', data.has_more);

    // Re-initialize drag & drop for new cards
    if (typeof initCardDragAndDrop === 'function') {
        initCardDragAndDrop();
                    }
                } else {
        console.error('❌ Error:', data.error || 'Unknown error');
    this.hasMore = false;
                }
            } catch (error) {
        console.error('❌ Fetch error:', error);
    this.hasMore = false;
            } finally {
        this.isLoading = false;
    this.hideLoader();
            }
        },

    appendCards(html) {
            const container = document.querySelector('.repair-cards-container');
    if (!container) return;

    // Create temp container to parse HTML
    const temp = document.createElement('div');
    temp.innerHTML = html;

            // Append each card
            Array.from(temp.children).forEach(card => {
        container.appendChild(card);
            });
        },

    showLoader() {
            const loader = document.getElementById('infinite-scroll-loader');
    if (loader) loader.style.display = 'block';
        },

    hideLoader() {
            const loader = document.getElementById('infinite-scroll-loader');
    if (loader) loader.style.display = 'none';
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfiniteScroll.init());
    } else {
        InfiniteScroll.init();
    }

    // Expose globally for debugging
    window.InfiniteScroll = InfiniteScroll;
})();
</script>
