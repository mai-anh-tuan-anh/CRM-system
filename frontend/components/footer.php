<?php
/**
 * Footer Component
 * Phần footer chung cho tất cả các trang
 */
?>
    </div><!-- End main-content -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js (for dashboard charts) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Main JavaScript -->
    <script>
        // Define API_BASE_URL for all pages
        const API_BASE_URL = '/customer_management/backend/api';
        const FRONTEND_URL = '/customer_management/frontend';
    </script>
    <script src="/customer_management/frontend/assets/js/main.js"></script>
    
    <!-- Page specific JS -->
    <?php if (isset($pageJS)): ?>
    <script src="<?= $pageJS ?>"></script>
    <?php endif; ?>
    
    <?php if (isset($inlineJS)): ?>
    <script>
    <?= $inlineJS ?>
    </script>
    <?php endif; ?>
</body>
</html>
