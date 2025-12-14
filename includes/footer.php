<?php
// Определяем базовый путь
$is_in_pages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$base_path = $is_in_pages ? '../' : '';
?>
    </div> <!-- Закрываем div из wrapper -->
</section>

<!-- JavaScript -->
<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.js'></script>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js'></script>
<script src="<?= $base_path ?>js/script.js"></script>

<!-- Инициализация скриптов -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Простой обработчик для поиска
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    const basePath = '<?= isset($pages_path) ? $pages_path : "pages/" ?>';
                    window.location.href = basePath + 'tv_clients.php?search=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // Для мобильных - закрытие сайдбара при клике на ссылку
    if (window.innerWidth < 768) {
        const sidebarLinks = document.querySelectorAll('.sidebar a[href]');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.remove('show-sidebar');
                }
            });
        });
    }
});
</script>

</body>
</html>
