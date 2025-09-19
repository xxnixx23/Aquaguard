<?php
/**
 * AquaGuard – Mobile-Responsive Web App (Single-file PHP)
 * Tech: PHP 8+, MySQL, jQuery, Bootstrap 5, Chart.js
 *
 * QUICK START
 * 1) Set DB credentials below, drop this file as index.php in your PHP server.
 * 2) Load the page – it will auto-create tables and a demo account.
 *    Demo login: demo@example.com / demo1234
 *
 * NOTE: This single-file version  
 * (Login/Signup · Dashboard with metrics & charts · Sensor Settings · Smart Recommendation
 *  · Fishpond Settings · Edit Profile), optimized for mobile.
 */

//------------------------------------------------------
// DB CONFIG – change these to match your environment
//------------------------------------------------------
$DB_HOST = 'localhost';
$DB_NAME = 'aquaguard_db';
$DB_USER = 'root';
$DB_PASS = '';

//------------------------------------------------------
// DB CONNECTION
//------------------------------------------------------
function db() {
  static $pdo = null;
  if ($pdo === null) {
    global $DB_HOST,$DB_NAME,$DB_USER,$DB_PASS;
    $pdo = new PDO(
      "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
      $DB_USER,
      $DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
  }
  return $pdo;
}

//------------------------------------------------------
// FIRST RUN: CREATE DATABASE IF NOT EXISTS (safe)
//------------------------------------------------------
try {
  $pdoRoot = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
} catch (Throwable $e) { /* ignore */ }

//------------------------------------------------------
// MIGRATIONS – tables
//------------------------------------------------------
function migrate() {
  $pdo = db();
  $pdo->exec(<<<SQL
  CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) DEFAULT 'Client',
    role VARCHAR(50) DEFAULT 'Owner',
    phone VARCHAR(30) DEFAULT NULL,
    farm_name VARCHAR(120) DEFAULT 'Taste From The Sea',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB
  SQL);

  $pdo->exec(<<<SQL
  CREATE TABLE IF NOT EXISTS ponds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(120) DEFAULT 'Pond 1',
    size_sqm INT DEFAULT 100,
    fish_type VARCHAR(80) DEFAULT 'Tilapia',
    pond_type VARCHAR(80) DEFAULT 'Earthen',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB
  SQL);

  $pdo->exec(<<<SQL
  CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reading_frequency ENUM('1m','15m','1h','1d') DEFAULT '15m',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB
  SQL);

  $pdo->exec(<<<SQL
  CREATE TABLE IF NOT EXISTS readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pond_id INT NULL,
    recorded_at DATETIME NOT NULL,
    temperature_c DECIMAL(5,2) NULL,
    turbidity_ntu DECIMAL(6,2) NULL,
    ph_level DECIMAL(4,2) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE SET NULL,
    INDEX (user_id, recorded_at)
  ) ENGINE=InnoDB
  SQL);

  $pdo->exec(<<<SQL
  CREATE TABLE IF NOT EXISTS recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB
  SQL);
}

migrate();

//------------------------------------------------------
// SEED – demo account & data
//------------------------------------------------------
function seed_demo() {
  $pdo = db();
  $exists = $pdo->prepare("SELECT id FROM users WHERE email=?");
  $exists->execute(['demo@example.com']);
  if (!$exists->fetch()) {
    $pdo->prepare("INSERT INTO users(email,password_hash,name,role,phone,farm_name) VALUES(?,?,?,?,?,?)")
        ->execute(['demo@example.com', password_hash('demo1234', PASSWORD_BCRYPT), 'Xhyllah Serrano','Owner','+63 9323481924','Taste From The Sea']);
    $uid = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO ponds(user_id,name,size_sqm,fish_type,pond_type) VALUES(?,?,?,?,?)")
        ->execute([$uid,'Pond A',120,'Tilapia','Earthen']);
    $pdo->prepare("INSERT INTO settings(user_id,reading_frequency) VALUES(?,?)")
        ->execute([$uid,'3']);
    $pdo->prepare("UPDATE settings SET reading_frequency='3'") ;
    // seed readings for last 3 days hourly
    $pondId = $pdo->lastInsertId();
    $start = new DateTimeImmutable('-2 days');
    for ($i=0; $i<72; $i++) {
      $t = $start->modify("+{$i} hour");
      $temp = 24 + sin($i/4)*3 + mt_rand(-20,20)/10; // playful curve
      $turb = 20 + cos($i/6)*10 + mt_rand(-50,50)/10;
      $ph   = 7  + sin($i/8)*0.4 + mt_rand(-10,10)/100;
      $pdo->prepare("INSERT INTO readings(user_id,pond_id,recorded_at,temperature_c,turbidity_ntu,ph_level) VALUES(?,?,?,?,?,?)")
          ->execute([$uid,$pondId,$t->format('Y-m-d H:00:00'),$temp,$turb,$ph]);
    }
    $pdo->prepare("INSERT INTO recommendations(user_id,title,body) VALUES(?,?,?)")
        ->execute([$uid,'Prescriptive Suggestions','Maintain temperature between 24–30°C. If turbidity exceeds 40 NTU, increase filtration. Keep pH in the 6.5–8 range. Schedule checks every 3 hours during hot days.']);
  }
}
seed_demo();

//------------------------------------------------------
// AUTH HELPERS
//------------------------------------------------------
session_start();
function current_user() {
  return $_SESSION['user'] ?? null;
}
function require_login() {
  if (!current_user()) { header('Location: ?page=login'); exit; }
}

//------------------------------------------------------
// SIMPLE API (AJAX)
//------------------------------------------------------
if (isset($_POST['action'])) {
  header('Content-Type: application/json');
  try {
    $pdo = db();
    switch ($_POST['action']) {
      case 'signup':
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $name  = trim($_POST['name'] ?? 'Client');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) throw new Exception('Invalid email or password too short.');
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) throw new Exception('Email already registered.');
        $pdo->prepare('INSERT INTO users(email,password_hash,name) VALUES(?,?,?)')
            ->execute([$email, password_hash($pass, PASSWORD_BCRYPT), $name]);
        echo json_encode(['ok'=>true]);
        exit;

      case 'signin':
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email=?');
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || !password_verify($pass, $u['password_hash'])) throw new Exception('Invalid credentials.');
        $_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email'],'name'=>$u['name'],'role'=>$u['role'],'phone'=>$u['phone'],'farm_name'=>$u['farm_name']];
        echo json_encode(['ok'=>true]);
        exit;

      case 'signout':
        session_destroy();
        echo json_encode(['ok'=>true]);
        exit;

      case 'update_profile':
        if (!current_user()) throw new Exception('Not signed in');
        $uid = current_user()['id'];
        $name = trim($_POST['name'] ?? 'Client');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'Owner');
        $phone = trim($_POST['phone'] ?? '');
        $farm = trim($_POST['farm_name'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email');
        $pdo->prepare('UPDATE users SET name=?, email=?, role=?, phone=?, farm_name=? WHERE id=?')
            ->execute([$name,$email,$role,$phone,$farm,$uid]);
        $_SESSION['user'] = array_merge(current_user(), compact('name','email','role','phone','farm_name'));
        echo json_encode(['ok'=>true]);
        exit;

      case 'update_frequency':
        if (!current_user()) throw new Exception('Not signed in');
        $uid = current_user()['id'];
        $freq = $_POST['frequency'] ?? '15m';
        if (!in_array($freq,['1m','15m','1h','1d'],true)) throw new Exception('Invalid frequency');
        $pdo->prepare('INSERT INTO settings(user_id,reading_frequency) VALUES(?,?) ON DUPLICATE KEY UPDATE reading_frequency=VALUES(reading_frequency)')
            ->execute([$uid,$freq]);
        echo json_encode(['ok'=>true]);
        exit;

      case 'rename_pond':
        if (!current_user()) throw new Exception('Not signed in');
        $uid = current_user()['id'];
        $id  = (int)($_POST['pond_id'] ?? 0);
        $name= trim($_POST['name'] ?? '');
        if ($id<=0 || $name==='') throw new Exception('Missing data');
        $pdo->prepare('UPDATE ponds SET name=? WHERE id=? AND user_id=?')->execute([$name,$id,$uid]);
        echo json_encode(['ok'=>true]);
        exit;

      case 'edit_pond':
        if (!current_user()) throw new Exception('Not signed in');
        $uid=(int)current_user()['id'];
        $id=(int)($_POST['pond_id'] ?? 0);
        $size=(int)($_POST['size_sqm'] ?? 0);
        $fish=trim($_POST['fish_type'] ?? '');
        $type=trim($_POST['pond_type'] ?? '');
        if ($id<=0) throw new Exception('Invalid pond');
        $pdo->prepare('UPDATE ponds SET size_sqm=?, fish_type=?, pond_type=? WHERE id=? AND user_id=?')
            ->execute([$size,$fish,$type,$id,$uid]);
        echo json_encode(['ok'=>true]);
        exit;

      case 'chart_data':
        if (!current_user()) throw new Exception('Not signed in');
        $uid = current_user()['id'];
        $since = new DateTimeImmutable('-1 day');
        $stmt = $pdo->prepare('SELECT recorded_at, temperature_c, turbidity_ntu, ph_level FROM readings WHERE user_id=? AND recorded_at>=? ORDER BY recorded_at ASC');
        $stmt->execute([$uid, $since->format('Y-m-d H:i:s')]);
        echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;

      case 'get_profile':
        if (!current_user()) throw new Exception('Not signed in');
        $uid = current_user()['id'];
        $u = $pdo->prepare('SELECT * FROM users WHERE id=?');
        $u->execute([$uid]);
        $p = $pdo->prepare('SELECT * FROM ponds WHERE user_id=? ORDER BY id LIMIT 1');
        $p->execute([$uid]);
        $s = $pdo->prepare('SELECT * FROM settings WHERE user_id=?');
        $s->execute([$uid]);
        echo json_encode(['ok'=>true,'user'=>$u->fetch(PDO::FETCH_ASSOC),'pond'=>$p->fetch(PDO::FETCH_ASSOC),'settings'=>$s->fetch(PDO::FETCH_ASSOC)]);
        exit;

      default:
        throw new Exception('Unknown action');
    }
  } catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    exit;
  }
}

//------------------------------------------------------
// ROUTING
//------------------------------------------------------
$page = $_GET['page'] ?? (current_user() ? 'dashboard' : 'login');

//------------------------------------------------------
// HTML HEAD
//------------------------------------------------------
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AquaGuard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{--ag-primary:#0EA5E9; --ag-dark:#0B1220; --ag-accent:#10B981; --ag-warn:#F59E0B;}
    body{background:#0B1220; color:#f5f5f7;}
    .brand{font-weight:800; letter-spacing:2px}
    .card.ag{background:#0e1528; border:1px solid #1f2a44;}
    .ag-pill{background:#111b33; border:1px solid #1f2a44;}
    .metric{font-size: .9rem; text-transform:uppercase; opacity:.8}
    .value{font-size:1.6rem; font-weight:700}
    .ok{color:var(--ag-accent)}
    .neutral{color:#60a5fa}
    .warn{color:var(--ag-warn)}
    .nav-tabs .nav-link{color:#bcd;}
    .nav-tabs .nav-link.active{background:#0e1528; color:#fff; border-color:#1f2a44 #1f2a44 transparent}
    .calendar{display:grid; grid-template-columns:repeat(7,1fr); gap:.25rem;}
    .calendar .day{padding:.35rem; text-align:center; border-radius:.4rem; background:#0e1528;}
    .calendar .head{opacity:.7; font-size:.8rem}
    .btn-ag{background:var(--ag-primary); border:none}
    .btn-ag:hover{background:#0284c7}
    a, .link-light{color:#cfe7ff}
    .form-control, .form-select{background:#0e1528; border:1px solid #1f2a44; color:#e5e7eb}
    .form-control::placeholder{color:#6b7280}
    .modal-content{background:#0e1528; color:#e5e7eb; border:1px solid #1f2a44}
    .navbar{background:#0e1528}
    .chip{background:#0e1a2f; border:1px solid #243253; padding:.35rem .6rem; border-radius:999px; font-size:.8rem}
  </style>
</head>
<body>

<?php if (current_user()): ?>
<nav class="navbar navbar-expand-lg mb-3 sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand text-info brand" href="?page=dashboard">AQUAG<span class="text-secondary">UARD</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="?page=dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="?page=settings"><i class="bi bi-gear"></i> Settings</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <span class="chip"><i class="bi bi-person-circle me-1"></i><?=htmlspecialchars(current_user()['name'])?></span>
        <button class="btn btn-sm btn-outline-light" id="btnEditProfile"><i class="bi bi-pencil-square"></i> Edit Profile</button>
        <button class="btn btn-sm btn-danger" id="btnSignout"><i class="bi bi-box-arrow-right"></i> Sign out</button>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<div class="container px-3 pb-5">
  <?php if ($page==='login'): ?>
    <div class="row justify-content-center mt-5">
      <div class="col-12 col-md-6 col-lg-5">
        <div class="text-center mb-4">
          <h1 class="brand text-info">AQUAG<span class="text-secondary">UARD</span></h1>
          <p class="text-secondary">Grow More. Waste Less. Farm Smart.</p>
        </div>
        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabSignIn" role="tab">Sign In</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSignUp" role="tab">Sign Up</button></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="tabSignIn" role="tabpanel">
            <div class="card ag p-3">
              <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" id="loginEmail" placeholder="you@example.com">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" id="loginPassword" placeholder="••••••••">
              </div>
              <button class="btn btn-ag w-100" id="btnSignIn">Sign In</button>
            </div>
          </div>
          <div class="tab-pane fade" id="tabSignUp" role="tabpanel">
            <div class="card ag p-3">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" id="signupName" placeholder="Your name">
              </div>
              <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" id="signupEmail" placeholder="you@example.com">
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Password</label>
                  <input type="password" class="form-control" id="signupPassword" placeholder="••••••••">
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Confirm Password</label>
                  <input type="password" class="form-control" id="signupConfirm" placeholder="••••••••">
                </div>
              </div>
              <button class="btn btn-ag w-100 mt-3" id="btnSignUp">Create Account</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php elseif ($page==='dashboard'): require_login(); ?>
    <?php
      $now = new DateTime('now');
      $monthLabel = $now->format('F Y');
    ?>
    <div class="row g-3 align-items-stretch">
      <div class="col-12 col-lg-8">
        <div class="card ag p-3 mb-3">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
              <div class="h5 mb-0">Hello, <?=htmlspecialchars(current_user()['name'])?>!</div>
              <div class="text-secondary">Your Dashboard as of <?= $now->format('g:i A') ?></div>
            </div>
            <div class="text-end">
              <div class="chip">April 2025 style calendar</div>
            </div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <div class="card ag p-3 h-100">
              <div class="metric">Water Temperature</div>
              <div class="value neutral" id="tempNow">-- °C</div>
              <div class="small text-secondary">Status: <span class="ok">Normal</span></div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card ag p-3 h-100">
              <div class="metric">Water Turbidity</div>
              <div class="value neutral" id="turbNow">-- NTU</div>
              <div class="small text-secondary">Status: <span class="ok">Normal</span></div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card ag p-3 h-100">
              <div class="metric">pH Level</div>
              <div class="value neutral" id="phNow">--</div>
              <div class="small text-secondary">Status: <span class="neutral">Neutral</span></div>
            </div>
          </div>
        </div>
        <div class="card ag p-3 mt-3">
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabTemp" role="tab">Temperature</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTurbidity" role="tab">Turbidity</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPH" role="tab">pH Level</button></li>
          </ul>
          <div class="tab-content p-2">
            <div class="tab-pane fade show active" id="tabTemp" role="tabpanel">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="chip">Normal</div>
                <div class="small text-secondary">per 3 hours</div>
              </div>
              <canvas id="chartTemp" height="150"></canvas>
              <div class="small text-secondary mt-2">Ideal Temperature Range</div>
            </div>
            <div class="tab-pane fade" id="tabTurbidity" role="tabpanel">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="chip">Normal</div>
                <div class="small text-secondary">per 3 days</div>
              </div>
              <canvas id="chartTurb" height="150"></canvas>
              <div class="small text-secondary mt-2">Ideal Turbidity</div>
            </div>
            <div class="tab-pane fade" id="tabPH" role="tabpanel">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="chip">Neutral</div>
                <div class="small text-secondary">per 3 days</div>
              </div>
              <canvas id="chartPH" height="150"></canvas>
              <div class="small text-secondary mt-2">Ideal range</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="card ag p-3 mb-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center" style="width:56px;height:56px;border:1px solid #1f2a44;">
              <i class="bi bi-person fs-4 text-secondary"></i>
            </div>
            <div>
              <div class="fw-bold"><?=htmlspecialchars(current_user()['name'])?></div>
              <div class="text-secondary small">ID: <?= (int)current_user()['id'] ?></div>
            </div>
          </div>
          <div class="mt-3 d-grid gap-2">
            <a class="btn btn-outline-light" href="?page=settings#sensor"><i class="bi bi-sliders"></i> Sensor Settings</a>
            <a class="btn btn-outline-light" href="?page=settings#smart"><i class="bi bi-lightbulb"></i> Smart Recommendation</a>
            <a class="btn btn-outline-light" href="?page=settings#pond"><i class="bi bi-water"></i> Fishpond Setting</a>
            <button class="btn btn-outline-info" id="btnEditProfileRight"><i class="bi bi-pencil"></i> Edit Profile</button>
          </div>
        </div>
        <div class="card ag p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="fw-bold"><?=$monthLabel?></div>
            </div>
          </div>
          <div class="calendar" id="cal"></div>
        </div>
      </div>
    </div>
  <?php elseif ($page==='settings'): require_login(); ?>
    <div class="row g-3">
      <div class="col-12 col-lg-4">
        <div class="card ag p-3">
          <div class="fw-bold mb-2">Menu</div>
          <div class="list-group">
            <a class="list-group-item list-group-item-action" href="#sensor">Sensor Settings</a>
            <a class="list-group-item list-group-item-action" href="#smart">Smart Recommendation</a>
            <a class="list-group-item list-group-item-action" href="#pond">Fishpond Setting</a>
            <a class="list-group-item list-group-item-action" href="#profile">Edit Profile</a>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-8">
        <div id="sensor" class="card ag p-3 mb-3">
          <div class="h5 mb-3">Sensor Settings</div>
          <label class="form-label">Set Data Reading Frequency</label>
          <div class="row g-2">
            <div class="col-6 col-md-3"><button class="btn w-100 btn-outline-info freq" data-freq="1m">Every minute</button></div>
            <div class="col-6 col-md-3"><button class="btn w-100 btn-outline-info freq" data-freq="15m">Every 15 minutes</button></div>
            <div class="col-6 col-md-3"><button class="btn w-100 btn-outline-info freq" data-freq="1h">Every hour</button></div>
            <div class="col-6 col-md-3"><button class="btn w-100 btn-outline-info freq" data-freq="1d">Once per day</button></div>
          </div>
        </div>

        <div id="smart" class="card ag p-3 mb-3">
          <div class="h5 mb-3">Smart Recommendation</div>
          <div id="smartBody" class="text-secondary"></div>
        </div>

        <div id="pond" class="card ag p-3 mb-3">
          <div class="h5 mb-3">Fishpond Settings</div>
          <div class="mb-3">
            <label class="form-label">Rename Ponds</label>
            <div class="input-group">
              <select class="form-select" id="pondSelect"></select>
              <input class="form-control" id="pondName" placeholder="Enter new pond name">
              <button class="btn btn-ag" id="btnRenamePond">Save</button>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label">Size (sqm)</label>
              <input type="number" class="form-control" id="pondSize" placeholder="e.g., 120">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Fish Type</label>
              <input class="form-control" id="pondFish" placeholder="Enter fish type">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Pond Type</label>
              <input class="form-control" id="pondType" placeholder="Enter pond type">
            </div>
            <div class="col-12">
              <button class="btn btn-ag" id="btnEditPond">Save</button>
            </div>
          </div>
        </div>

        <div id="profile" class="card ag p-3">
          <div class="h5 mb-3">Edit Profile</div>
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" id="profileEmail" placeholder="Email">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Role</label>
              <input class="form-control" id="profileRole" placeholder="Owner">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Farm Name</label>
              <input class="form-control" id="profileFarm" placeholder="Farm Name">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Phone Number</label>
              <input class="form-control" id="profilePhone" placeholder="+63">
            </div>
            <div class="col-12">
              <label class="form-label">Name</label>
              <input class="form-control" id="profileName" placeholder="Your Name">
            </div>
            <div class="col-12 d-flex gap-2">
              <button class="btn btn-ag" id="btnSaveProfile">Save</button>
              <button class="btn btn-outline-danger" id="btnDeleteAccount" disabled>Delete Account</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Edit Profile Modal (quick access) -->
<div class="modal" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Email</label>
          <input class="form-control" id="mEmail">
        </div>
        <div class="mb-2">
          <label class="form-label">Role</label>
          <input class="form-control" id="mRole">
        </div>
        <div class="mb-2">
          <label class="form-label">Farm Name</label>
          <input class="form-control" id="mFarm">
        </div>
        <div class="mb-2">
          <label class="form-label">Phone Number</label>
          <input class="form-control" id="mPhone">
        </div>
        <div class="mb-2">
          <label class="form-label">Name</label>
          <input class="form-control" id="mName">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-ag" id="mSave">Save</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const toast = (msg, type='info')=>{
  const el = document.createElement('div');
  el.className = `position-fixed top-0 start-50 translate-middle-x mt-3 alert alert-${type}`;
  el.style.zIndex = 2000;
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(()=>el.remove(), 2600);
};

// Auth
$('#btnSignIn').on('click', ()=>{
  $.post('', {action:'signin', email:$('#loginEmail').val(), password:$('#loginPassword').val()}, (r)=>{
    if(r.ok) location.href='?page=dashboard';
  }).fail(e=> toast(e.responseJSON?.error||'Sign in failed','danger'));
});
$('#btnSignUp').on('click', ()=>{
  const p = $('#signupPassword').val();
  if (p !== $('#signupConfirm').val()) return toast('Passwords do not match','warning');
  $.post('', {action:'signup', name:$('#signupName').val(), email:$('#signupEmail').val(), password:p}, (r)=>{
    toast('Account created. Please sign in.','success');
    $('[data-bs-target="#tabSignIn"]').tab('show');
  }).fail(e=> toast(e.responseJSON?.error||'Sign up failed','danger'));
});
$('#btnSignout').on('click', ()=>{
  $.post('', {action:'signout'}, ()=> location.href='?page=login');
});

// Profile modal
$('#btnEditProfile, #btnEditProfileRight').on('click', ()=>{
  $.post('', {action:'get_profile'}, (r)=>{
    const u=r.user; $('#mEmail').val(u.email); $('#mRole').val(u.role||'Owner'); $('#mFarm').val(u.farm_name||''); $('#mPhone').val(u.phone||''); $('#mName').val(u.name||'');
    new bootstrap.Modal('#editProfileModal').show();
  });
});
$('#mSave').on('click', ()=>{
  $.post('', {action:'update_profile', email:$('#mEmail').val(), role:$('#mRole').val(), farm_name:$('#mFarm').val(), phone:$('#mPhone').val(), name:$('#mName').val()}, ()=>{
    toast('Profile updated','success');
    location.reload();
  }).fail(e=> toast(e.responseJSON?.error||'Update failed','danger'));
});

// Settings page actions
$('.freq').on('click', function(){
  $.post('', {action:'update_frequency', frequency:$(this).data('freq')}, ()=> toast('Frequency updated','success'))
  .fail(e=> toast(e.responseJSON?.error||'Update failed','danger'));
});

$('#btnRenamePond').on('click', ()=>{
  $.post('', {action:'rename_pond', pond_id:$('#pondSelect').val(), name:$('#pondName').val()}, ()=> toast('Pond renamed','success'))
  .fail(e=> toast(e.responseJSON?.error||'Rename failed','danger'));
});
$('#btnEditPond').on('click', ()=>{
  $.post('', {action:'edit_pond', pond_id:$('#pondSelect').val(), size_sqm:$('#pondSize').val(), fish_type:$('#pondFish').val(), pond_type:$('#pondType').val()}, ()=> toast('Pond info saved','success'))
  .fail(e=> toast(e.responseJSON?.error||'Save failed','danger'));
});

// Load profile+ponds for settings
function loadProfile(){
  $.post('', {action:'get_profile'}, (r)=>{
    if(r.ok){
      const {user, pond, settings} = r;
      $('#smartBody').text('Prescriptive Suggestions: Maintain temperature 24–30°C, turbidity below 40 NTU, pH 6.5–8.');
      $('#pondSelect').empty().append(`<option value="${pond.id}">${pond.name}</option>`);
      $('#pondName').val(pond.name); $('#pondSize').val(pond.size_sqm); $('#pondFish').val(pond.fish_type); $('#pondType').val(pond.pond_type);
      $('#profileEmail').val(user.email); $('#profileRole').val(user.role||'Owner'); $('#profileFarm').val(user.farm_name||''); $('#profilePhone').val(user.phone||''); $('#profileName').val(user.name||'');
    }
  });
}
if (location.search.includes('page=settings')) loadProfile();

// Dashboard charts
let chartT=null, chartB=null, chartP=null;
function renderCharts(rows){
  const labels = rows.map(r=> r.recorded_at.substring(11,16));
  const temp = rows.map(r=> +r.temperature_c);
  const turb = rows.map(r=> +r.turbidity_ntu);
  const ph   = rows.map(r=> +r.ph_level);
  $('#tempNow').text((temp.at(-1)||0).toFixed(1)+' °C');
  $('#turbNow').text((turb.at(-1)||0).toFixed(1)+' NTU');
  $('#phNow').text((ph.at(-1)||0).toFixed(2));

  const makeCfg = (lbl, data) => ({
    type:'line',
    data:{ labels, datasets:[{ label:lbl, data, tension:.35, fill:false }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{grid:{color:'#1f2a44'}}, y:{grid:{color:'#1f2a44'}}} }
  });
  chartT && chartT.destroy(); chartB && chartB.destroy(); chartP && chartP.destroy();
  chartT = new Chart(document.getElementById('chartTemp'), makeCfg('Temperature (°C)', temp));
  chartB = new Chart(document.getElementById('chartTurb'), makeCfg('Turbidity (NTU)', turb));
  chartP = new Chart(document.getElementById('chartPH'),   makeCfg('pH Level', ph));
}
function loadCharts(){
  $.post('', {action:'chart_data'}, (r)=>{ if(r.ok) renderCharts(r.rows); });
}
if (location.search.includes('page=dashboard')) loadCharts();

// Simple calendar
function renderCalendar(){
  const el = document.getElementById('cal'); if(!el) return;
  const now = new Date(); const y=now.getFullYear(), m=now.getMonth();
  const first = new Date(y, m, 1); const start = first.getDay();
  const days = new Date(y, m+1, 0).getDate();
  el.innerHTML = '';
  const heads=['S','M','T','W','T','F','S']; heads.forEach(h=> el.insertAdjacentHTML('beforeend', `<div class="head">${h}</div>`));
  for(let i=0;i<start;i++) el.insertAdjacentHTML('beforeend','<div></div>');
  for(let d=1; d<=days; d++){ el.insertAdjacentHTML('beforeend', `<div class="day${d===now.getDate()?' border border-info':''}">${d}</div>`); }
}
renderCalendar();

// Save Profile (settings page block)
$('#btnSaveProfile').on('click', ()=>{
  $.post('', {action:'update_profile', email:$('#profileEmail').val(), role:$('#profileRole').val(), farm_name:$('#profileFarm').val(), phone:$('#profilePhone').val(), name:$('#profileName').val()}, ()=>{
    toast('Profile saved','success');
  }).fail(e=> toast(e.responseJSON?.error||'Save failed','danger'));
});
</script>
</body>
</html>
