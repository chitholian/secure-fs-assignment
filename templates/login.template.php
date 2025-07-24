<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= (isset($site_title) ? htmlentities($site_title) . " &mdash;" : '') . ' Secure FS' ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?= $body_content ?? '' ?>
<footer>
    <hr>
    <p class="text-center font-sm">
        Copyright &copy; 2025 &mdash; Atikur Rahman Chitholian
    </p>
</footer>
</body>
</html>
