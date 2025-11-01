/**
 * IMAGE GALLERY - Lightbox pour affichage des images
 */

class ImageGallery {
    constructor() {
        this.currentIndex = 0;
        this.images = [];
        this.createLightbox();
        this.bindEvents();
    }

    createLightbox() {
        const lightbox = document.createElement('div');
        lightbox.id = 'image-lightbox';
        lightbox.className = 'image-lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-overlay"></div>
            <div class="lightbox-container">
                <button class="lightbox-close">&times;</button>
                <button class="lightbox-prev"><i class="fas fa-chevron-left"></i></button>
                <button class="lightbox-next"><i class="fas fa-chevron-right"></i></button>
                <img src="" alt="" class="lightbox-image">
                <div class="lightbox-caption"></div>
                <div class="lightbox-counter"></div>
            </div>
        `;
        document.body.appendChild(lightbox);

        // Add CSS
        if (!document.getElementById('gallery-css')) {
            const style = document.createElement('style');
            style.id = 'gallery-css';
            style.textContent = `
                .image-lightbox {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    z-index: 10000;
                    display: none;
                    align-items: center;
                    justify-content: center;
                }
                .image-lightbox.active {
                    display: flex;
                }
                .lightbox-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.9);
                    backdrop-filter: blur(10px);
                }
                .lightbox-container {
                    position: relative;
                    max-width: 90%;
                    max-height: 90%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .lightbox-image {
                    max-width: 100%;
                    max-height: 80vh;
                    object-fit: contain;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
                    border-radius: 8px;
                }
                .lightbox-close {
                    position: absolute;
                    top: -50px;
                    right: 0;
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    font-size: 2rem;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .lightbox-close:hover {
                    background: rgba(255, 255, 255, 0.3);
                    transform: rotate(90deg);
                }
                .lightbox-prev,
                .lightbox-next {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    font-size: 1.5rem;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .lightbox-prev {
                    left: -70px;
                }
                .lightbox-next {
                    right: -70px;
                }
                .lightbox-prev:hover,
                .lightbox-next:hover {
                    background: rgba(255, 255, 255, 0.3);
                }
                .lightbox-caption {
                    position: absolute;
                    bottom: -50px;
                    left: 0;
                    right: 0;
                    text-align: center;
                    color: white;
                    font-size: 1rem;
                }
                .lightbox-counter {
                    position: absolute;
                    top: -50px;
                    left: 0;
                    color: white;
                    font-size: 0.9rem;
                }
                @media (max-width: 768px) {
                    .lightbox-prev { left: 10px; }
                    .lightbox-next { right: 10px; }
                    .lightbox-close { top: 10px; right: 10px; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    bindEvents() {
        const lightbox = document.getElementById('image-lightbox');
        const close = lightbox.querySelector('.lightbox-close');
        const prev = lightbox.querySelector('.lightbox-prev');
        const next = lightbox.querySelector('.lightbox-next');
        const overlay = lightbox.querySelector('.lightbox-overlay');

        close.onclick = () => this.close();
        overlay.onclick = () => this.close();
        prev.onclick = () => this.prev();
        next.onclick = () => this.next();

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowLeft') this.prev();
            if (e.key === 'ArrowRight') this.next();
        });

        // Initialize gallery items
        document.addEventListener('click', (e) => {
            const galleryItem = e.target.closest('[data-gallery]');
            if (galleryItem) {
                e.preventDefault();
                const gallery = galleryItem.dataset.gallery;
                this.open(gallery, galleryItem.dataset.index || 0);
            }
        });
    }

    open(galleryName, startIndex = 0) {
        // Gather all images from this gallery
        this.images = Array.from(document.querySelectorAll(`[data-gallery="${galleryName}"]`)).map(el => ({
            src: el.dataset.src || el.src || el.href,
            caption: el.dataset.caption || el.alt || ''
        }));

        this.currentIndex = parseInt(startIndex);
        this.show();
    }

    show() {
        if (this.images.length === 0) return;

        const lightbox = document.getElementById('image-lightbox');
        const image = lightbox.querySelector('.lightbox-image');
        const caption = lightbox.querySelector('.lightbox-caption');
        const counter = lightbox.querySelector('.lightbox-counter');

        const current = this.images[this.currentIndex];
        
        image.src = current.src;
        caption.textContent = current.caption;
        counter.textContent = `${this.currentIndex + 1} / ${this.images.length}`;

        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        const lightbox = document.getElementById('image-lightbox');
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
        this.show();
    }

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.show();
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new ImageGallery());
} else {
    new ImageGallery();
}
