<?php
require_once __DIR__ . '/includes/init.php';

ensure_user_login();
$site_title = 'Home';
$user = get_logged_in_user();

if (isset($_POST['action'])) {
    validate_csrf();

    if ($_POST['action'] == 'upload') {
        $file = $_FILES['file'];

        $err = null;
        if (!$file) {
            $err = 'Please upload a file';
        } elseif ($file['error']) {
            $err = 'Failed to upload the file';
        }

        $filename = $file['name'];
        if (!empty($_POST['name'])) {
            $name = trim($_POST['name']);
            if (strlen($name) > 255) {
                $err = 'Custom name is too long';
            } elseif (strlen($name)) {
                $filename = $name;
            }
        }

        if ($err) {
            flash_message('error', $err);
        }

        $mime_type = mime_content_type($file['tmp_name']);
        // Check allowed mime types.
        if (!preg_match('#^application/pdf|image/.*|text/(plain|csv)$#', $mime_type)) {
            flash_message('error', 'Only PDF, image, text and CSV files are allowed');
            go_back();
            exit();
        }
        $now = time();
        $upload_location = date('Y/m', $now);
        $stored_path = $upload_location . DIRECTORY_SEPARATOR . date('His-', $now) . rand(100000, 999999) . '.' . get_mime_to_extension($mime_type);

        // Upload the file to some directories based on upload date,
        // it will help mitigating file limit exceeded errors per directory.
        // Create the upload location if not exists
        $storage_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $upload_location;
        if (!is_dir($storage_path) && !@mkdir($storage_path, 0755, true)) {
            flash_message('error', 'Failed to create upload directory');
            go_back();
            exit();
        }
        // Move the uploaded file to target storage location.
        $uploaded = move_uploaded_file($file['tmp_name'], UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored_path);
        if (!$uploaded) {
            flash_message('error', 'Failed to store uploaded file');
        } else {
            // Insert row to files table.
            $file_id = generate_uuid();
            $db = MyDB::getInstance();
            $db->execute("
INSERT INTO files (id, stored_path, file_name, mime_type, file_size, created_by, created_at)
VALUES (:id, :stored_path, :file_name, :mime_type, :file_size, :created_by, NOW())", [
                'id' => $file_id,
                'stored_path' => $stored_path,
                'file_name' => $filename,
                'mime_type' => $mime_type,
                'file_size' => $file['size'],
                'created_by' => $user['id'],
            ]);

            // Generate a random download token for the uploaded file.
            $token = generate_token(128);
            $db->execute("
INSERT INTO download_tokens (token, file_id, created_at)
VALUES (:token, :file_id, NOW())", [
                'token' => $token,
                'file_id' => $file_id,
            ]);
            flash_message('success', 'File uploaded successfully');
            // Redirect back to the referer page.
            go_back();
            exit();
        }
    } elseif ($_POST['action'] == 'generate-link') {
        if (!isset($_POST['file_id'])) {
            flash_message('error', 'Specify a file to generate link for');
            go_back();
            exit();
        }
        $file_id = $_POST['file_id'];
        $db = MyDB::getInstance();
        $file = $db->execute("
SELECT f.*, t.token, t.created_at AS token_created_at, t.last_used_at
FROM files f LEFT JOIN download_tokens t ON t.file_id = f.id 
   AND t.created_at = (
       SELECT MAX(created_at) 
       FROM download_tokens 
       WHERE file_id = f.id
   )
WHERE f.id = ? AND f.created_by = ?", [
            $file_id,
            $user['id'],
        ])->fetch();
        if (empty($file)) {
            flash_message('error', 'File not found');
            go_back();
            exit();
        }

        // Prevent multiple valid token creation.
        // Check if existing token expired or used-up.
        // If not expired and not used already, do not create another token.
        if (!empty($file['token_created_at'])) {
            $expired = is_link_expired($file['token_created_at'], DOWNLOAD_LINK_EXPIRY);
            if (!$expired && empty($file['last_used_at'])) {
                flash_message('error', 'A valid token already exists');
                go_back();
                exit();
            }
        }

        // Delete existing unused links first. It may not detect expired links, instead, will say token is invalid.
        // $db->execute("DELETE FROM download_tokens WHERE file_id = ? AND last_used_at IS NULL", [
        //    $file['id'],
        //]);

        // Insert new token.
        $token = generate_token(128);
        $db->execute("INSERT INTO download_tokens (token, file_id, created_at) VALUES (:token, :file_id, NOW())", [
            'token' => $token,
            'file_id' => $file_id,
        ]);
        flash_message('success', 'Download link generated successfully');
        go_back();
        exit();
    } elseif ($_POST['action'] == 'delete') {
        if (!isset($_POST['file_id'])) {
            flash_message('error', 'Specify a file to delete');
            go_back();
            exit();
        }
        $file_id = $_POST['file_id'];
        $db = MyDB::getInstance();
        // Make sure to check creator also.
        $file = $db->execute("SELECT * FROM files WHERE id = :id AND created_by = :uid", [
            'id' => $file_id,
            'uid' => $user['id'],
        ])->fetch();
        if (!$file) {
            flash_message('error', 'File not found');
            go_back();
            exit();
        }
        // Delete the file from the disk first.
        $stored_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['stored_path'];
        if (file_exists($stored_path)) {
            if (!@unlink($stored_path)) {
                flash_message('error', 'Failed to delete file from server');
                go_back();
                exit();
            }
        }
        $db->execute("DELETE FROM files WHERE id = :id", [
            'id' => $file_id,
        ]);
        flash_message('success', 'File deleted successfully');
        go_back();
        exit();
    }
}

// get uploaded files.
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = intval($_GET['limit'] ?? 10);
if ($limit < 1) $limit = 10;
$offset = ($page - 1) * $limit;

$db = MyDB::getInstance();
$q = $db->execute("
SELECT f.*, t.token, t.created_at AS token_created_at, t.last_used_at
FROM files f
LEFT JOIN download_tokens t ON t.file_id = f.id 
   AND t.created_at = (
       SELECT MAX(created_at) 
       FROM download_tokens 
       WHERE file_id = f.id
   )
WHERE f.created_by = :uid
ORDER BY f.created_at DESC, f.file_name
LIMIT $offset, $limit", [
    'uid' => $user['id'],
]);

$files = $q->fetchAll();
$total = $db->execute("SELECT COUNT(*) FROM files WHERE created_by = :uid", [
    'uid' => $user['id'],
])->fetchColumn();

ob_start();
?>
    <form method="post" enctype="multipart/form-data" class="d-flex justify-center">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="_token" value="<?= get_csrf_token() ?>">
        <fieldset>
            <legend>Upload New File</legend>
            <div class="d-flex" style="flex-wrap: wrap; align-items: end; justify-content: stretch">
                <div class="input">
                    <label for="file" class="required">Select a File</label>
                    <input type="file" id="file" name="file" class="input-field" accept="application/pdf,image/*,text/plain,text/csv"
                           required>
                </div>
                <div class="input">
                    <label for="name">Customized Name</label>
                    <input type="text" maxlength="255" class="input-field" id="name" name="name"/>
                </div>
                <div class="input">
                    <button type="submit" class="btn">Upload</button>
                </div>
            </div>
        </fieldset>
    </form>

    <!-- Files -->
    <div class="d-flex flex-col">
        <h1 style="font-size: 1.1rem; margin: 0 .25rem">Uploaded Files</h1>
        <div style="overflow: auto" class="d-flex">
            <table class="files-table" style="flex-grow: 1">
                <thead>
                <tr>
                    <th>#</th>
                    <th class="text-left">File Name</th>
                    <th>Size</th>
                    <th>Mime Type</th>
                    <th>Uploaded At</th>
                    <th>Download Link</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (empty($files)) { ?>
                    <tr>
                        <td class="text-center" colspan="7" style="color: red"> No Data Found</td>
                    </tr>
                <?php }
                foreach ($files as $i => $file) { ?>
                    <tr>
                        <td class="text-center"><?= $offset + $i + 1 ?></td>
                        <td><?= htmlentities($file['file_name']) ?></td>
                        <td class="text-center nowrap"><?= human_readable_size($file['file_size']) ?></td>
                        <td class="nowrap"><?= htmlentities($file['mime_type']) ?></td>
                        <td class="text-center nowrap"><?= htmlentities($file['created_at']) ?></td>
                        <td class="text-center nowrap" data-dl-token="<?= $file['token'] ?>">
                            <?php
                            if (isset($file['last_used_at'])) { ?>
                                <span style="color: red" class="nowrap">Used at <?= $file['last_used_at'] ?></span>
                            <?php } elseif ($file['token_created_at'] && is_link_expired($file['token_created_at'], DOWNLOAD_LINK_EXPIRY)) { ?>
                                <span style="color: red">Expired</span>
                            <?php } elseif ($file['token_created_at']) {
                                $expires_at = get_expiry_time($file['token_created_at'], DOWNLOAD_LINK_EXPIRY); ?>
                                <button class="btn" type="button" style="margin: 1px" onclick="showDownloadLink(this)">
                                    Show
                                </button>
                                <?php if ($expires_at) echo "<br>Expires at $expires_at"; ?>
                            <?php } else { ?>
                                Not Available
                            <?php } ?>
                        </td>
                        <td class="text-left" data-file-id="<?= $file['id'] ?>">
                            <button class="btn danger" type="button" style="margin: 1px" onclick="deleteFile(this)">
                                Delete
                            </button>
                            <a target="_blank" style="margin: 1px;" role="button" title="Download file"
                               href="<?= 'download.php?file_id=' . $file['id'] ?>">
                                <button class="btn">Download</button>
                            </a>
                            <?php if (isset($file['last_used_at']) || empty($file['token_created_at']) || is_link_expired($file['token_created_at'], DOWNLOAD_LINK_EXPIRY)) { ?>
                                <button class="btn" type="button" style="margin: 1px" onclick="generateLink(this)">
                                    Regenerate Link
                                </button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="d-flex justify-center" style="margin: .5rem; gap: .25rem">
            <?php
            $params = $_GET;
            if ($page > 1) {
                $params['page'] = $page - 1; ?>
                <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($params) ?>" class="btn">
                    &laquo; Prev Page
                </a>
            <?php }
            if ($page < ($total / $limit)) {
                $params['page'] = $page + 1; ?>
                <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($params) ?>" class="btn">
                    Next Page &raquo;
                </a>
            <?php } ?>
        </div>
    </div>
    <form id="action-form" method="post" style="display: none">
        <input type="hidden" name="action" id="action">
        <input type="hidden" name="_token" value="<?= get_csrf_token() ?>">
        <input type="hidden" name="file_id" id="file_id">
    </form>

    <!-- Modal to show download link and copy it -->
    <div class="modal download-link-modal" style="display: none">
        <div class="modal-content">
            <fieldset>
                <legend>Download Link</legend>
                <div class="input d-flex align-center">
                    <input type="text" class="input-field" id="dl-link">
                    <button class="btn" style="margin-left: .5rem" onclick="copyLink()">Copy</button>
                </div>
                <div class="input">
                    <button class="btn danger" type="button" onclick="closeModal()">Close</button>
                </div>
            </fieldset>
        </div>
    </div>
    <script>
        document.querySelector('#file').addEventListener('change', (event) => {
            let newFileName = ''
            if (event.target.files?.length) {
                newFileName = event.target.files[0].name
            }
            document.querySelector('#name').value = newFileName
        })

        function deleteFile(element) {
            if (!confirm('Are you sure to delete the file ?')) return
            let file_id = element.closest('td').getAttribute('data-file-id')
            document.querySelector('#action').value = 'delete'
            document.querySelector('#file_id').value = file_id
            document.querySelector('#action-form').submit()
        }

        function generateLink(element) {
            let file_id = element.closest('td').getAttribute('data-file-id')
            document.querySelector('#action').value = 'generate-link'
            document.querySelector('#file_id').value = file_id
            document.querySelector('#action-form').submit()
        }

        function showDownloadLink(element) {
            let token = element.closest('td').getAttribute('data-dl-token')
            let url = new URL(window.location.href)
            url.pathname = 'download.php'
            url.search = 'token=' + encodeURIComponent(token)
            document.querySelector('#dl-link').value = url.toString()
            document.querySelector('.download-link-modal').style.display = 'flex'
        }

        function closeModal() {
            document.querySelector('.download-link-modal').style.display = 'none'
        }

        function copyLink() {
            let input = document.querySelector('#dl-link')
            input.focus();
            input.setSelectionRange(0, input.value.length);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value)
                    //.then(() => alert('Link copied to clipboard!'))
                    .catch(e => {
                        console.log(e)
                        alert('Failed to copy link, please copy manually from the input field')
                    });
            } else {
                alert('Cannot access clipboard, please copy manually from the input field')
            }
        }
    </script>
<?php

$body_content = ob_get_clean();
include __DIR__ . '/templates/dashboard.template.php';
