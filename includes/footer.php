    </main>
    <footer class="text-center mt-5 py-4 border-top bg-white">
        <p class="text-muted mb-1">Developed by <a href="https://www.linkedin.com/in/dexterity-wurayayi-967a64230?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" target="_blank">Dexterwura</a></p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    use Config\Settings;
    $settings = $settings ?? new Settings(__DIR__ . '/../storage/settings.json');
    $notifications = $settings->get('notifications', []);
    if (!empty($notifications)):
    ?>
    <script>
    (function(){
        var notes = <?php echo json_encode($notifications, JSON_UNESCAPED_SLASHES); ?>;
        if (!Array.isArray(notes)) return;
        var container = document.createElement('div');
        container.style.position = 'fixed';
        container.style.top = '16px';
        container.style.right = '16px';
        container.style.zIndex = '1080';
        document.body.appendChild(container);
        notes.slice(0, 3).forEach(function(n, i){
            setTimeout(function(){
                var div = document.createElement('div');
                div.className = 'alert alert-' + (n.type || 'info');
                div.textContent = n.message || '';
                div.style.minWidth = '260px';
                container.appendChild(div);
                setTimeout(function(){ div.remove(); }, 6000);
            }, i * 800);
        });
    })();
    </script>
    <?php endif; ?>
    </body>
    </html>

