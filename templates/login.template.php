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
    <main class="d-flex justify-center align-center" style="flex-grow: 1">
        <form method="post">
            <fieldset style="max-width: 320px">
                <legend>User Login</legend>
                <div class="text-center" style="margin-bottom: 1rem">
                    <img src="assets/icons/app-logo.png" alt="Site Logo" style="max-height: 4rem;">
                </div>
                <?php
                // Flash error messages here.
                $flash = get_flash_messages();
                if (!empty($flash['error'])) { ?>
                    <div style="color: red"><?= htmlentities($flash['error']) ?></div>
                <?php } ?>
                <div class="input">
                    <label for="username" class="required">Username</label>
                    <input type="text" class="input-field" id="username" name="username"
                           value="<?= htmlentities($username ?? '') ?>"
                           required>
                </div>
                <div class="input">
                    <label for="password" class="required">Password</label>
                    <input type="password" class="input-field" id="password" name="password" required>
                </div>
                <input type="hidden" name="_token" value="<?= get_csrf_token() ?>">
                <div class="input">
                    <button type="submit" class="btn primary" style="float: right">Login</button>
                </div>
            </fieldset>
        </form>
    </main>
    <footer>
        <hr>
        <p class="text-center font-sm">
            Copyright &copy; 2025 &mdash; Atikur Rahman Chitholian
        </p>
    </footer>
</div>
</body>
</html>
