</main>

<!-- Scroll to Top Button -->
<button id="scrollToTop" class="btn btn-primary rounded-circle position-fixed" style="bottom: 20px; right: 20px; width: 50px; height: 50px; z-index: 1000; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15);" title="Scroll to top">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<footer class="text-center mt-5 py-4 border-top bg-white">
    <div class="d-flex justify-content-center gap-2 mb-2">
        <?php $donate = $settings->get('donate_url', ''); if ($donate): ?>
            <a class="btn btn-success pill-btn" href="<?= htmlspecialchars($donate) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-heart me-1"></i> Donate</a>
        <?php endif; ?>
    </div>
    <p class="text-muted mb-1">&copy; Varsity Resource Centre. Developed by <a href="https://www.linkedin.com/in/dexterity-wurayayi-967a64230?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" target="_blank">Dexterwura</a></p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Scroll to Top Button Functionality
document.addEventListener('DOMContentLoaded', function() {
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    if (scrollToTopBtn) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
                scrollToTopBtn.style.opacity = '1';
            } else {
                scrollToTopBtn.style.opacity = '0';
                setTimeout(() => {
                    if (window.pageYOffset <= 300) {
                        scrollToTopBtn.style.display = 'none';
                    }
                }, 300);
            }
        });
        
        // Smooth scroll to top when clicked
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Add hover effects
        scrollToTopBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'all 0.3s ease';
        });
        
        scrollToTopBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    }
});
</script>

<?php
use Config\Settings;
use Database\DB;
// Notifications removed
?>
</body>
</html>