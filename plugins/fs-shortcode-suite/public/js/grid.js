(() => {
    'use strict';

    if (typeof FSGridConfig === 'undefined') return;

    const state = {
        page: FSGridConfig.page || 1,
        perPage: FSGridConfig.perPage || 12,
        filters: FSGridConfig.filters || {},
        hasMore: FSGridConfig.hasMore || false,
        loading: false
    };

    const gridWrapper = document.querySelector('.fs-grid-wrapper');
    const loadMoreBtn = document.querySelector('.fs-grid-load-more');

    if (!gridWrapper) return;

    /* ==========================
       COLOR MAP
    ========================== */

    const colorMap = {
        negro: '#000000',
        blanco: '#ffffff',
        azul: '#1976d2',
        'azul marino': '#0d47a1',
        'azul royal': '#1565c0',
        rojo: '#d32f2f',
        naranja: '#f57c00',
        amarillo: '#fbc02d',
        'amarillo fluor': '#ffeb3b',
        verde: '#388e3c',
        'verde fluor': '#66bb6a',
        gris: '#9e9e9e',
        marron: '#5d4037',
        beige: '#d7ccc8',
        rosa: '#ec407a',
        fucsia: '#c2185b',
        morado: '#7b1fa2',
        plata: '#cfd8dc',
        oro: '#f9a825',
        turquesa: '#0097a7',
        coral: '#ff7043',
        bordeaux: '#6a1b1a',
        cuero: '#8d6e63',
        multicolor: 'linear-gradient(45deg, red, yellow, blue)'
    };

    /* ==========================
       INITIALIZE CARDS
    ========================== */

    const initializeCards = (scope = document) => {

        const cards = scope.querySelectorAll('.fs-card');

        cards.forEach(card => {

            if (card.dataset.initialized) return;

            try {
                card._data = JSON.parse(card.dataset.product);
            } catch (e) {
                return;
            }

            renderColorDots(card);

            card.dataset.initialized = 'true';
        });
    };

    /* ==========================
       COLOR DOTS
    ========================== */

    const renderColorDots = (card) => {

        const container = card.querySelector('.fs-card__colors');
        if (!container || !card._data?.colors) return;

        container.innerHTML = '';

        const colors = Object.keys(card._data.colors);
        if (!colors.length) return;

        colors.forEach((color, index) => {

            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'fs-color-dot';
            dot.dataset.color = color;

            dot.style.background =
                colorMap[color.toLowerCase()] || '#ccc';

            container.appendChild(dot);

            if (index === 0) {
                dot.classList.add('is-active');
                updateCard(card, color);
            }
        });
    };

    /* ==========================
       UPDATE CARD
    ========================== */

    const updateCard = (card, color) => {

        const colorData = card._data?.colors?.[color];
        if (!colorData) return;

        const images = colorData.images || [];
        const sizes  = colorData.sizes || {};

        const primary   = card.querySelector('.fs-card__image--primary');
        const secondary = card.querySelector('.fs-card__image--secondary');
        const priceEl   = card.querySelector('.fs-card__price');
        const sizesEl   = card.querySelector('.fs-card__sizes-count');

        /* FORCE repaint fix (soluciona bug hover) */
        card.classList.add('fs-force-repaint');
        void card.offsetWidth;
        card.classList.remove('fs-force-repaint');

        /* Images */

        if (primary) primary.src = images[0] || '';
        if (secondary) secondary.src = images[1] || images[0] || '';

        /* Price mínimo */

        const prices = Object.values(sizes)
            .map(s => s.price)
            .filter(Boolean);

        const minPrice = prices.length ? Math.min(...prices) : 0;

        if (priceEl) {
            priceEl.textContent = minPrice > 0
                ? minPrice.toFixed(2).replace('.', ',') + ' €'
                : '';
        }

        /* Sizes count */

        if (sizesEl) {
            const totalSizes = Object.keys(sizes).length;
            sizesEl.textContent =
                totalSizes > 0
                    ? `${totalSizes} ${totalSizes === 1 ? 'talla' : 'tallas'}`
                    : '';
        }

        /* Guardar color activo */
        card._activeColor = color;
    };

    /* ==========================
       EVENTS
    ========================== */

    document.addEventListener('click', (e) => {

        /* COLOR DOT */
        const dot = e.target.closest('.fs-color-dot');
        if (dot) {

            e.preventDefault();
            e.stopPropagation();

            const card = dot.closest('.fs-card');
            if (!card) return;

            const color = dot.dataset.color;
            if (!color) return;

            card.querySelectorAll('.fs-color-dot')
                .forEach(d => d.classList.remove('is-active'));

            dot.classList.add('is-active');

            updateCard(card, color);

            return;
        }

        /* SIZE CLICK (si decides renderizar tallas individuales luego) */
        const sizeBtn = e.target.closest('.fs-size-btn');
        if (sizeBtn) {

            e.preventDefault();
            e.stopPropagation();

            const card = sizeBtn.closest('.fs-card');
            if (!card) return;

            const color = card._activeColor;
            const size  = sizeBtn.dataset.size;

            if (!color || !size) return;

            const sizeData = card._data.colors[color].sizes[size];
            if (!sizeData) return;

            const priceEl = card.querySelector('.fs-card__price');
            if (priceEl) {
                priceEl.textContent =
                    sizeData.price.toFixed(2).replace('.', ',') + ' €';
            }

            return;
        }
    });

    /* ==========================
       LOAD MORE
    ========================== */

    const loadMore = async () => {

        if (state.loading || !state.hasMore) return;

        state.loading = true;

        try {

            const nextPage = state.page + 1;

            const params = new URLSearchParams({
                ...state.filters,
                page: nextPage,
                per_page: state.perPage
            });

            const response = await fetch(
                `${FSGridConfig.restUrl}?${params.toString()}`
            );

            const data = await response.json();

            appendProducts(data.items);

            state.page = nextPage;
            state.hasMore = data.has_more;

            if (!state.hasMore && loadMoreBtn) {
                loadMoreBtn.remove();
            }

        } catch (error) {
            console.error('Grid load error:', error);
        }

        state.loading = false;
    };

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', e => {
            e.preventDefault();
            loadMore();
        });
    }

    /* ==========================
       APPEND PRODUCTS
    ========================== */

    const appendProducts = (products) => {

        products.forEach(product => {

            const card = document.createElement('a');
            card.className = 'fs-card';
            card.href = product.permalink;
            card.dataset.product = JSON.stringify(product);

            card.innerHTML = `
                <div class="fs-card__image-wrapper">
                    <img class="fs-card__image fs-card__image--primary" src="">
                    <img class="fs-card__image fs-card__image--secondary" src="">
                </div>
                <div class="fs-card__content">
                    <h3 class="fs-card__title">${product.name}</h3>
                    <div class="fs-card__sizes-count"></div>
                    <div class="fs-card__price"></div>
                    <div class="fs-card__colors"></div>
                </div>
            `;

            gridWrapper.appendChild(card);
        });

        initializeCards(gridWrapper);
    };

    initializeCards();

})();
