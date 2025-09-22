</main>
<footer class="text-center mt-5 py-4 border-top bg-white">
    <div class="d-flex justify-content-center gap-2 mb-2">
        <?php $donate = $settings->get('donate_url', ''); if ($donate): ?>
            <a class="btn btn-success pill-btn" href="<?= htmlspecialchars($donate) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-heart me-1"></i> Donate</a>
        <?php endif; ?>
    </div>
    <p class="text-muted mb-1">&copy; Varsity Resource Centre. Developed by <a href="https://www.linkedin.com/in/dexterity-wurayayi-967a64230?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" target="_blank">Dexterwura</a></p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php
use Config\Settings;
use Database\DB;
// Notifications removed
?>
</body>
</html>