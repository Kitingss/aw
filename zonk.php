<?php
$auth_password = 'memek'; // Ganti password login di sini
$BASE_ROOT = '/home/username/public_html'; // Ganti dengan path utama lo

session_start();

// ==== AUTH LOGIN PAGE ====
if (!isset($_SESSION['logged_in'])) {
    if (!empty($_POST['password']) && $_POST['password'] === $auth_password) {
        $_SESSION['logged_in'] = true;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    // === LOGIN UI STYLISH
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login</title>
    <style>
        body {
            background: #0e3b14;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: monospace;
            margin: 0;
        }

        .login-box {
            background: #2ecc71;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.4);
            color: black;
            text-align: center;
        }

        input[type="password"] {
            padding: 10px;
            width: 200px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
        }

        button {
            padding: 10px 20px;
            margin-left: 10px;
            background-color: black;
            color: #2ecc71;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #111;
        }

        h3 {
            margin-bottom: 15px;
        }
    </style>
    </head><body>
        <form method="POST" class="login-box">
            <h3>🔐 Login File Manager</h3>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </body></html>';
    exit;
}

// ==== HELPER ====
function sanitize_name($name) {
    return preg_replace('/[^a-zA-Z0-9\-_.]/', '_', basename($name));
}

$root = realpath($BASE_ROOT);
$path = realpath($_GET['path'] ?? $root);
if (!$path || strpos($path, $root) !== 0) $path = $root;

// ==== FILE ACTIONS ====
if (!empty($_FILES['upload_file']['name'])) {
    $fn = sanitize_name($_FILES['upload_file']['name']);
    move_uploaded_file($_FILES['upload_file']['tmp_name'], $path.DIRECTORY_SEPARATOR.$fn);
}
if (!empty($_POST['new_folder'])) {
    mkdir($path.DIRECTORY_SEPARATOR.sanitize_name($_POST['new_folder']),0755,true);
}
if (!empty($_POST['new_file'])) {
    $fn = sanitize_name($_POST['new_file']);
    $content = $_POST['file_content'] ?? '';
    file_put_contents($path.DIRECTORY_SEPARATOR.$fn,$content);
}

$items = scandir($path);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP File Manager</title>
    <style>
        body {
            background-color: #0e3b14;
            color: #fff;
            font-family: monospace;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #90ee90;
        }

        a { color: #caffc4; text-decoration: none; }
        a:hover { text-decoration: underline; }

        form {
            margin: 15px 0;
            background: #14532d;
            padding: 15px;
            border-radius: 5px;
        }

        input, textarea, button {
            padding: 8px;
            margin: 5px 0;
            border: none;
            border-radius: 3px;
            font-family: monospace;
        }

        input[type="text"], input[type="file"], textarea {
            width: 100%;
            background: #daf5d8;
            color: #000;
        }

        button {
            background-color: #32cd32;
            color: #000;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: #2eb82e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1f4d2e;
            margin-top: 20px;
            border-radius: 6px;
            overflow: hidden;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #000;
        }

        th {
            background: #32cd32;
            color: #000;
        }

        tr:hover {
            background-color: #246b3c;
        }

        .breadcrumb {
            padding: 10px;
            background-color: #14532d;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        hr {
            border: 1px solid #44aa44;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<h2>📁 FILE MANAGER</h2>

<?php
// ==== Breadcrumb ====
echo "<div class='breadcrumb'>🧭 Path: ";
$relative = trim(substr($path, strlen($root)), DIRECTORY_SEPARATOR);
$parts = $relative ? explode(DIRECTORY_SEPARATOR, $relative) : [];
$crumb = $root;
echo "<a href='?path=".urlencode($root)."'>root</a>";
foreach ($parts as $i => $p) {
    $crumb .= DIRECTORY_SEPARATOR.$p;
    echo " / ";
    if ($i == count($parts) - 1) echo "<b>".htmlspecialchars($p)."</b>";
    else echo "<a href='?path=".urlencode($crumb)."'>".htmlspecialchars($p)."</a>";
}
echo "</div><hr>";
?>

<!-- ==== FORMS ==== -->
<form method="POST" enctype="multipart/form-data">
    <b>Upload File:</b><br>
    <input type="file" name="upload_file" required>
    <button>Upload</button>
</form>

<form method="POST">
    <b>Buat Folder:</b><br>
    <input name="new_folder" placeholder="Nama folder..." required>
    <button>Create Folder</button>
</form>

<form method="POST">
    <b>Buat File + Konten:</b><br>
    <input name="new_file" placeholder="Nama file.txt" required><br>
    <textarea name="file_content" rows="6" placeholder="Isi file..."></textarea><br>
    <button>Create File</button>
</form>

<!-- ==== TABLE FILE ==== -->
<?php
if ($path !== $root) {
    echo "<p><a href='?path=".urlencode(dirname($path))."'>⬅️ Up</a></p>";
}

echo "<table>";
echo "<tr><th>📦 Name</th><th>📏 Size</th><th>🕒 Date</th></tr>";

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $fullpath = $path . DIRECTORY_SEPARATOR . $item;
    $name = htmlspecialchars($item);
    $size = is_file($fullpath) ? round(filesize($fullpath)/1024,2)." KB" : "-";
    $date = date("Y-m-d H:i", filemtime($fullpath));

    if (is_dir($fullpath)) {
        echo "<tr><td>📁 <a href='?path=".urlencode($fullpath)."'>$name</a></td><td>$size</td><td>$date</td></tr>";
    } else {
        echo "<tr><td>📄 $name</td><td>$size</td><td>$date</td></tr>";
    }
}
echo "</table>";
?>

</body>
</html>
