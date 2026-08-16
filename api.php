<?php
/**
 * ============================================================================
 * HIGH-PERFORMANCE REST API ENDPOINT UNTUK CPANEL MYSQL / MARIADB
 * APLIKASI PRESENSI DIGITAL SMA NEGERI 1 LHOKSUKON
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ⚙️ KONFIGURASI DATABASE CPANEL (SESUAIKAN DENGAN CPANEL ANDA)
$db_host = 'localhost';
$db_user = 'root';        // Username database cPanel Anda
$db_pass = '';            // Password database cPanel Anda
$db_name = 'absensi_db';  // Nama database cPanel Anda

date_default_timezone_set('Asia/Jakarta');

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Koneksi Database MySQL Gagal: ' . $e->getMessage()]);
    exit();
}

$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true) ?: $_POST;
$action = isset($data['action']) ? $data['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$args = isset($data['args']) ? $data['args'] : [];

try {
    switch ($action) {
        case 'login':
            $username = trim($args[0] ?? '');
            $password = trim($args[1] ?? '');
            $nisn = trim($args[2] ?? '');

            // 1. Siswa
            $stmt = $pdo->prepare("SELECT * FROM siswa WHERE nisn = ? OR nisn = ?");
            $stmt->execute([$nisn ?: $username, $username]);
            $siswa = $stmt->fetch();
            if ($siswa) {
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'username' => $siswa['nisn'],
                        'nama' => $siswa['nama'],
                        'nisn' => $siswa['nisn'],
                        'role' => 'siswa',
                        'kelas' => $siswa['kelas'],
                        'token' => md5($siswa['nisn'] . time())
                    ]
                ]);
                exit();
            }

            // 2. Users (Admin, Guru, Tendik)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            if ($user) {
                echo json_encode([
                    'success' => true,
                    'user' => [
                        'username' => $user['username'],
                        'nama' => $user['nama'],
                        'role' => $user['role'],
                        'nip' => $user['nip'],
                        'noHp' => $user['no_hp'],
                        'token' => md5($user['username'] . time())
                    ]
                ]);
                exit();
            }

            echo json_encode(['success' => false, 'message' => 'Username / NISN atau password tidak valid!']);
            break;

        case 'getSiswaList':
            $stmt = $pdo->query("SELECT nama, nisn, jenis_kelamin as jenisKelamin, tanggal_lahir as tanggalLahir, agama, nama_ayah as namaAyah, nama_ibu as namaIbu, no_hp as noHp, kelas, alamat FROM siswa ORDER BY nama ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'getGuruList':
            $stmt = $pdo->query("SELECT nip, nama, jenis_kelamin as jenisKelamin, jabatan, username, password, no_hp as noHp FROM guru ORDER BY nama ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'getTendikList':
            $stmt = $pdo->query("SELECT nip, nama, jenis_kelamin as jenisKelamin, jabatan, username, password, no_hp as noHp FROM tendik ORDER BY nama ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'getAbsensiToday':
            $targetId = trim($args[0] ?? '');
            $today = date('Y-m-d');

            // Check Libur
            $stmtL = $pdo->prepare("SELECT * FROM hari_libur WHERE tanggal = ?");
            $stmtL->execute([$today]);
            $libur = $stmtL->fetch();
            if ($libur) {
                echo json_encode(['success' => true, 'isLibur' => true, 'keteranganLibur' => $libur['keterangan']]);
                exit();
            }

            $stmt = $pdo->prepare("SELECT tanggal, nisn_nip as nisn, nama, jam_datang as jamDatang, jam_pulang as jamPulang, keterangan, status FROM absensi WHERE tanggal = ? AND nisn_nip = ?");
            $stmt->execute([$today, $targetId]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $row ?: null]);
            break;

        case 'scanAbsensi':
            $targetId = trim($args[0] ?? '');
            $role = trim($args[1] ?? 'siswa');
            $kelasParam = trim($args[2] ?? '-');
            $today = date('Y-m-d');
            $nowTime = date('H:i');

            // Find User Details
            $nama = 'Pengguna';
            $kelasJabatan = $kelasParam;

            if ($role === 'siswa') {
                $st = $pdo->prepare("SELECT nama, kelas FROM siswa WHERE nisn = ?");
                $st->execute([$targetId]);
                if ($r = $st->fetch()) {
                    $nama = $r['nama'];
                    $kelasJabatan = $r['kelas'];
                }
            } else if ($role === 'guru') {
                $st = $pdo->prepare("SELECT nama, jabatan FROM guru WHERE nip = ? OR username = ?");
                $st->execute([$targetId, $targetId]);
                if ($r = $st->fetch()) {
                    $nama = $r['nama'];
                    $kelasJabatan = $r['jabatan'];
                }
            } else {
                $st = $pdo->prepare("SELECT nama, jabatan FROM tendik WHERE nip = ? OR username = ?");
                $st->execute([$targetId, $targetId]);
                if ($r = $st->fetch()) {
                    $nama = $r['nama'];
                    $kelasJabatan = $r['jabatan'];
                }
            }

            // Config check
            $confSt = $pdo->query("SELECT key_name, value_name FROM konfigurasi");
            $conf = [];
            foreach ($confSt->fetchAll() as $c) {
                $conf[$c['key_name']] = $c['value_name'];
            }

            $jamMasukAkhir = $conf['jam_masuk_akhir'] ?? '07:15';
            $jamPulangMulai = $conf['jam_pulang_mulai'] ?? '15:00';

            // Existing record
            $exSt = $pdo->prepare("SELECT * FROM absensi WHERE tanggal = ? AND nisn_nip = ?");
            $exSt->execute([$today, $targetId]);
            $ex = $exSt->fetch();

            if (!$ex) {
                // Absen Masuk
                $ket = ($nowTime > $jamMasukAkhir) ? 'Terlambat (' . $nowTime . ')' : 'Tepat Waktu';
                $ins = $pdo->prepare("INSERT INTO absensi (tanggal, nisn_nip, nama, role, kelas, jam_datang, jam_pulang, keterangan, status) VALUES (?, ?, ?, ?, ?, ?, '-', ?, 'Hadir')");
                $ins->execute([$today, $targetId, $nama, $role, $kelasJabatan, $nowTime, $ket]);

                echo json_encode([
                    'success' => true,
                    'type' => 'masuk',
                    'message' => "ABSEN MASUK BERHASIL!\nNama: $nama\nJam: $nowTime ($ket)",
                    'data' => ['nama' => $nama, 'jamDatang' => $nowTime, 'keterangan' => $ket]
                ]);
            } else if ($ex['jam_pulang'] === '-' || empty($ex['jam_pulang'])) {
                // Absen Pulang
                $ketPulang = ($nowTime < $jamPulangMulai) ? 'Pulang Cepat (' . $nowTime . ')' : 'Tepat Waktu';
                $upd = $pdo->prepare("UPDATE absensi SET jam_pulang = ?, keterangan = ? WHERE id = ?");
                $upd->execute([$nowTime, $ketPulang, $ex['id']]);

                echo json_encode([
                    'success' => true,
                    'type' => 'pulang',
                    'message' => "ABSEN PULANG BERHASIL!\nNama: $nama\nJam: $nowTime",
                    'data' => ['nama' => $nama, 'jamPulang' => $nowTime]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => "Pengguna $nama sudah melakukan Absen Masuk ($ex[jam_datang]) dan Absen Pulang ($ex[jam_pulang]) hari ini."]);
            }
            break;

        case 'getMonitoringRealtime':
            $filterKelas = $args[0] ?? null;
            $today = date('Y-m-d');

            $sql = "SELECT s.nama, s.nisn, s.kelas, COALESCE(a.jam_datang, '-') as jamDatang, COALESCE(a.jam_pulang, '-') as jamPulang, COALESCE(a.keterangan, '-') as keterangan, COALESCE(a.status, 'Belum Absen') as status FROM siswa s LEFT JOIN absensi a ON s.nisn = a.nisn_nip AND a.tanggal = ?";
            $params = [$today];

            if ($filterKelas) {
                $sql .= " WHERE s.kelas = ?";
                $params[] = $filterKelas;
            }
            $sql .= " ORDER BY s.nama ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'getDashboardBundle':
            $identifier = $args[0] ?? '';
            $role = $args[1] ?? 'siswa';
            $kelas = $args[2] ?? null;

            // 1. Today Absensi
            $today = date('Y-m-d');
            $stmtL = $pdo->prepare("SELECT * FROM hari_libur WHERE tanggal = ?");
            $stmtL->execute([$today]);
            $libur = $stmtL->fetch();

            $stmtA = $pdo->prepare("SELECT tanggal, nisn_nip as nisn, nama, jam_datang as jamDatang, jam_pulang as jamPulang, keterangan, status FROM absensi WHERE tanggal = ? AND nisn_nip = ?");
            $stmtA->execute([$today, $identifier]);
            $todayAbsensi = $stmtA->fetch();

            // 2. Monitoring
            $monitoring = [];
            if ($role === 'admin' || $role === 'guru') {
                $sqlM = "SELECT s.nama, s.nisn, s.kelas, COALESCE(a.jam_datang, '-') as jamDatang, COALESCE(a.jam_pulang, '-') as jamPulang, COALESCE(a.keterangan, '-') as keterangan, COALESCE(a.status, 'Belum Absen') as status FROM siswa s LEFT JOIN absensi a ON s.nisn = a.nisn_nip AND a.tanggal = ?";
                $paramsM = [$today];
                if ($role === 'guru' && $kelas) {
                    $sqlM .= " WHERE s.kelas = ?";
                    $paramsM[] = $kelas;
                }
                $sqlM .= " ORDER BY s.nama ASC";
                $stM = $pdo->prepare($sqlM);
                $stM->execute($paramsM);
                $monitoring = $stM->fetchAll();
            }

            echo json_encode([
                'success' => true,
                'todayAbsensi' => $todayAbsensi ?: null,
                'isLibur' => $libur ? true : false,
                'keteranganLibur' => $libur ? $libur['keterangan'] : '',
                'monitoring' => $monitoring
            ]);
            break;

        case 'changeCredentials':
            $oldU = trim($args[1] ?? '');
            $newU = trim($args[2] ?? '');
            $newPass = trim($args[4] ?? '');

            // Users
            $st1 = $pdo->prepare("UPDATE users SET username = ?, password = IF(? != '', ?, password) WHERE LOWER(username) = LOWER(?) OR LOWER(nip) = LOWER(?)");
            $st1->execute([$newU, $newPass, $newPass, $oldU, $oldU]);

            // Guru
            $st2 = $pdo->prepare("UPDATE guru SET username = ?, password = IF(? != '', ?, password) WHERE LOWER(username) = LOWER(?) OR LOWER(nip) = LOWER(?)");
            $st2->execute([$newU, $newPass, $newPass, $oldU, $oldU]);

            // Tendik
            $st3 = $pdo->prepare("UPDATE tendik SET username = ?, password = IF(? != '', ?, password) WHERE LOWER(username) = LOWER(?) OR LOWER(nip) = LOWER(?)");
            $st3->execute([$newU, $newPass, $newPass, $oldU, $oldU]);

            echo json_encode(['success' => true, 'message' => 'Username & Password berhasil diubah di database MySQL!']);
            break;

        case 'importSiswaBulk':
            $dataArray = $args[0] ?? [];
            $added = 0;
            $skipped = 0;

            $stmt = $pdo->prepare("INSERT INTO siswa (nama, nisn, jenis_kelamin, tanggal_lahir, agama, nama_ayah, nama_ibu, no_hp, kelas, alamat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas)");

            foreach ($dataArray as $item) {
                $nisn = trim($item['nisn'] ?? '');
                $nama = trim($item['nama'] ?? '');
                if (!$nisn || !$nama) {
                    $skipped++;
                    continue;
                }
                $stmt->execute([
                    $nama,
                    $nisn,
                    $item['jenisKelamin'] ?? 'Laki-laki',
                    $item['tanggalLahir'] ?? '2008-01-01',
                    $item['agama'] ?? 'Islam',
                    $item['namaAyah'] ?? '-',
                    $item['namaIbu'] ?? '-',
                    $item['noHp'] ?? '-',
                    $item['kelas'] ?? 'X MIPA 1',
                    $item['alamat'] ?? 'Lhoksukon'
                ]);
                $added++;
            }
            echo json_encode(['success' => true, 'added' => $added, 'skipped' => $skipped, 'message' => "Import Siswa Selesai. Berhasil: $added, Gagal: $skipped"]);
            break;

        case 'importGuruBulk':
            $dataArray = $args[0] ?? [];
            $added = 0;
            $skipped = 0;

            $stmtG = $pdo->prepare("INSERT INTO guru (nip, nama, jenis_kelamin, jabatan, username, password, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), jabatan = VALUES(jabatan)");
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, role, nip, nama, no_hp) VALUES (?, ?, 'guru', ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama)");

            foreach ($dataArray as $item) {
                $nip = trim($item['nip'] ?? '');
                $nama = trim($item['nama'] ?? '');
                $username = trim($item['username'] ?? $nip);
                $password = trim($item['password'] ?? '123456');

                if (!$nip || !$nama || !$username) {
                    $skipped++;
                    continue;
                }
                $stmtG->execute([
                    $nip,
                    $nama,
                    $item['jenisKelamin'] ?? 'Laki-laki',
                    $item['jabatan'] ?? 'Guru Mata Pelajaran',
                    $username,
                    $password,
                    $item['noHp'] ?? '-'
                ]);
                $stmtU->execute([$username, $password, $nip, $nama, $item['noHp'] ?? '-']);
                $added++;
            }
            echo json_encode(['success' => true, 'added' => $added, 'skipped' => $skipped, 'message' => "Import Guru Selesai. Berhasil: $added, Gagal: $skipped"]);
            break;

        case 'importTendikBulk':
            $dataArray = $args[0] ?? [];
            $added = 0;
            $skipped = 0;

            $stmtT = $pdo->prepare("INSERT INTO tendik (nip, nama, jenis_kelamin, jabatan, username, password, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama), jabatan = VALUES(jabatan)");
            $stmtU = $pdo->prepare("INSERT INTO users (username, password, role, nip, nama, no_hp) VALUES (?, ?, 'tendik', ?, ?, ?) ON DUPLICATE KEY UPDATE nama = VALUES(nama)");

            foreach ($dataArray as $item) {
                $nip = trim($item['nip'] ?? '');
                $nama = trim($item['nama'] ?? '');
                $username = trim($item['username'] ?? $nip);
                $password = trim($item['password'] ?? '123456');

                if (!$nip || !$nama || !$username) {
                    $skipped++;
                    continue;
                }
                $stmtT->execute([
                    $nip,
                    $nama,
                    $item['jenisKelamin'] ?? 'Perempuan',
                    $item['jabatan'] ?? 'Staf Kependidikan',
                    $username,
                    $password,
                    $item['noHp'] ?? '-'
                ]);
                $stmtU->execute([$username, $password, $nip, $nama, $item['noHp'] ?? '-']);
                $added++;
            }
            echo json_encode(['success' => true, 'added' => $added, 'skipped' => $skipped, 'message' => "Import Tendik Selesai. Berhasil: $added, Gagal: $skipped"]);
            break;

        default:
            echo json_encode(['success' => true, 'message' => 'API Presensi Digital SMANSA Lhoksukon cPanel MySQL Active']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
