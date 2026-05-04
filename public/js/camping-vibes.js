document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-gallery-thumb]').forEach((button) => {
        button.addEventListener('click', () => {
            const gallery = button.closest('.product-gallery');
            const main = gallery?.querySelector('[data-gallery-main]');

            if (!main) {
                return;
            }

            main.classList.remove('is-swapping');
            window.requestAnimationFrame(() => {
                main.src = button.dataset.galleryThumb;
                main.classList.add('is-swapping');
            });

            gallery.querySelectorAll('.product-thumbnail').forEach((thumbnail) => {
                thumbnail.classList.toggle('is-active', thumbnail === button);
            });
        });
    });

    const animatedElements = document.querySelectorAll('.product-card, .article-card, .panel, .order-row, .story-copy, .story-media, .promo-media, .promo > div, .product-gallery, .product-info');

    if (!('IntersectionObserver' in window)) {
        animatedElements.forEach((element) => element.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.08,
    });

    animatedElements.forEach((element) => {
        element.classList.add('motion-reveal');
        observer.observe(element);
    });
});
