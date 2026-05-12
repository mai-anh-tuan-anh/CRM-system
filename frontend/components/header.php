<?php
/**
 * Header Component
 * Phần head chung cho tất cả các trang
 */
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= $pageTitle ?? 'Hệ thống CRM' ?></title>

    <!-- Favicon -->
    <link id="dynamicFavicon" rel="icon" type="image/x-icon"
        href="/customer_management/frontend/assets/images/favicon.ico">
    <script>
    // Load favicon from settings
    fetch('/customer_management/backend/api/settings.php?group=company')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data && data.data.favicon_url) {
                document.getElementById('dynamicFavicon').href = data.data.favicon_url;
            }
        })
        .catch(err => console.log('Không thể load favicon:', err));
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts - Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/customer_management/frontend/assets/css/style.css" rel="stylesheet">

    <!-- Page specific CSS -->
    <?php if (isset($pageCSS)): ?>
    <link href="<?= $pageCSS ?>" rel="stylesheet">
    <?php endif; ?>
</head>

<body>