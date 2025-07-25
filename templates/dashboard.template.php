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
<div class="d-flex flex-col" style="min-height: 100vh;">
    <header>
        <nav class="navbar">
            <div class="d-flex align-center">
                <img src="assets/icons/app-logo.png" alt="Site Logo" style="max-height: 2rem">
            </div>
            <ul class="menus" style="flex-grow: 1; padding: 0 .5rem">
                <li class="menu"><a href="/" class="">Home</a></li>
            </ul>
            <div>
                <button type="button" class="btn" onclick="logout()">Logout</button>
            </div>
        </nav>
        <?php
        // Flash error messages here.
        $flash = get_flash_messages();
        if (!empty($flash['error'])) { ?>
            <div class="alert error flash"><?= htmlentities($flash['error']) ?></div>
        <?php }
        if (!empty($flash['success'])) { ?>
            <div class="alert success flash"><?= htmlentities($flash['success']) ?></div>
        <?php } ?>
    </header>
    <main style="flex-grow: 1">
        <?= $body_content ?? '' ?>
    </main>
    <footer>
        <hr>
        <p class="text-center font-sm">
            Copyright &copy; 2025 &mdash; Atikur Rahman Chitholian
        </p>
    </footer>
</div>
<script>
    function logout() {
        let ok = confirm('Are you sure to logout ?')
        if (!ok) {
            return
        }
        window.location.href = 'logout.php'
    }

    window.addEventListener('load', function () {
        setTimeout(() => {
            document.querySelectorAll('.flash').forEach(i => {
                i.remove()
            })
        }, 5000)
    })
</script>
</body>
</html>
