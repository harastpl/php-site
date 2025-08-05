</div> 
<footer class="footer mt-5" style="font-size: 0.8rem; padding: 10px 0;">
    <div class="container" style="max-height: 50%;">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-2">
                <img src="assets/images/logo.png" alt="Logo" class="logo">
                <h5><?php echo defined('SITE_NAME') ? SITE_NAME : '3D Print Shop'; ?></h5>
                <p>Professional 3D printing services with precision in every layer.</p>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Services</h5>
                <p><a href="custom-order.php">Custom 3D Printing</a></p>
                <p><a href="products.php">Ready-made Products</a></p>
                <p><a href="index.php">Design Consultation</a></p>
                <p><a href="terms.php">Terms & Conditions</a></p>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Contact Info</h5>
                <p><strong>Email:</strong> <a href="mailto:team@volt3dge.com">team@volt3dge.com</a></p>
                <p><strong>Website:</strong> <a href="https://volt3dge.com" target="_blank">www.volt3dge.com</a></p>
                <p><a href ="contact.php">Contact US</a><p>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h5>Socials</h5>
                <a href="https://www.instagram.com/volt3dge/" target="_blank" class="fs-4 me-3"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/@volt3dge" target="_blank" class="fs-4"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
        <hr style="border-color: #333; margin: 10px 0;">
        <div class="row">
            <div class="col-12 text-center" style="margin-bottom: 5px;">
                <p>© <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : '3D Print Shop'; ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Script for hover-to-play image carousel on product cards
    document.addEventListener('DOMContentLoaded', function() {
        const productCards = document.querySelectorAll('.product-card-clickable');

        productCards.forEach(card => {
            const imageElement = card.querySelector('.product-card-image');
            if (!imageElement) return;

            const primaryImage = card.dataset.primaryImage;
            const gallery = JSON.parse(card.dataset.gallery);
            let intervalId = null;
            let currentIndex = 0;

            card.addEventListener('mouseenter', () => {
                if (gallery.length > 1) {
                    // Immediately change to the second image on hover
                    currentIndex = 1;
                    if(gallery[currentIndex]) {
                        imageElement.src = 'uploads/products/' + gallery[currentIndex];
                    }

                    // Then start the interval
                    intervalId = setInterval(() => {
                        imageElement.style.opacity = 0; // Fade out
                        setTimeout(() => {
                            currentIndex = (currentIndex + 1) % gallery.length;
                            imageElement.src = 'uploads/products/' + gallery[currentIndex];
                            imageElement.style.opacity = 1; // Fade in
                        }, 50); // CSS transition time
                    }, 1500); // 1-second interval
                    card.dataset.intervalId = intervalId;
                }
            });

            card.addEventListener('mouseleave', () => {
                const storedIntervalId = card.dataset.intervalId;
                if (storedIntervalId) {
                    clearInterval(storedIntervalId);
                    card.removeAttribute('data-interval-id');
                }
                if (primaryImage) {
                    imageElement.style.opacity = 1; // Ensure it's visible
                    imageElement.src = 'uploads/products/' + primaryImage;
                }
                currentIndex = 0;
            });
        });
    });
</script>
