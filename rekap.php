<?php
// 1. Sistem Keamanan Sesi Login
session_start();
if (!isset($_SESSION['login_skm']) || $_SESSION['login_skm'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Koneksi Database
$host     = "localhost";
$username = "root";
$password = "";
$database = "db_ptsp";

$koneksi = new mysqli($host, $username, $password, $database);

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// 3. Logika Filter Rentang Tanggal
$tanggal_awal  = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

$kondisi_filter = " WHERE 1=1 "; 
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    // Mencakup seluruh data hingga akhir hari pukul 23:59:59
    $kondisi_filter .= " AND tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59' ";
}

// 4. Ambil Total Semua Responden Berdasarkan Filter
$total_query = $koneksi->query("SELECT COUNT(*) as total FROM tabel_survei" . $kondisi_filter);
$total_data  = $total_query->fetch_assoc();
$total_responden = $total_data['total'];

// 5. Ambil Jumlah per Kategori Penilaian Berdasarkan Filter
$hitung_skor = $koneksi->query("SELECT 
    SUM(CASE WHEN penilaian = 'Sangat Puas' THEN 1 ELSE 0 END) as sangat_puas,
    SUM(CASE WHEN penilaian = 'Puas' THEN 1 ELSE 0 END) as puas,
    SUM(CASE WHEN penilaian = 'Kurang Puas' THEN 1 ELSE 0 END) as kurang_puas,
    SUM(CASE WHEN penilaian = 'Tidak Puas' THEN 1 ELSE 0 END) as tidak_puas
    FROM tabel_survei" . $kondisi_filter);
$skor = $hitung_skor->fetch_assoc();

// Fungsi menghitung persentase aman dari pembagian nol
function hitungPersen($jumlah, $total) {
    if ($total == 0) return 0;
    return round(($jumlah / $total) * 100, 1);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Rekapitulasi SKM - PTSP</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 950px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 5px; }
        .text-center { text-align: center; color: #7f8c8d; margin-bottom: 25px; }
        
        /* Form Filter Style - Diperbaiki agar rapi dan simetris */
        .filter-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            border: 1px solid #e9ecef; 
        }
        .filter-form { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 15px; 
            align-items: flex-end; 
        }
        .form-group-filter { 
            display: flex; 
            flex-direction: column; 
            flex: 1;
            min-width: 150px;
        }
        .form-group-filter label { 
            font-size: 13px; 
            font-weight: bold; 
            color: #495057; 
            margin-bottom: 8px; 
        }
        .form-group-filter input { 
            padding: 10px 12px; 
            border: 1px solid #ced4da; 
            border-radius: 4px; 
            font-size: 14px; 
            width: 100%;
            box-sizing: border-box;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-filter { background: #3498db; color: white; border: none; padding: 11px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .btn-filter:hover { background: #2980b9; }
        .btn-print { background: #27ae60; color: white; border: none; padding: 11px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .btn-print:hover { background: #219653; }
        .btn-logout { background: #e74c3c; color: white; padding: 11px 20px; border-radius: 4px; font-weight: bold; text-decoration: none; font-size: 14px; text-align: center; transition: 0.2s; box-sizing: border-box; }
        .btn-logout:hover { background: #c0392b; }
        .btn-reset { background: #95a5a6; color: white; border: none; padding: 11px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 14px; text-align: center; }
        .btn-reset:hover { background: #7f8c8d; }

        /* Grid Statistik */
        .grid-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .card-stat { padding: 20px; border-radius: 8px; text-align: center; color: white; font-weight: bold; }
        .bg-sangat-puas { background-color: #2ecc71; }
        .bg-puas { background-color: #3498db; }
        .bg-kurang-puas { background-color: #f39c12; }
        .bg-tidak-puas { background-color: #e74c3c; }
        .card-stat h3 { margin: 5px 0; font-size: 28px; }
        
        /* Style Tabel */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #34495e; color: white; }
        tr:hover { background-color: #f9f9f9; }
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 11px; color: white; font-weight: bold; display: inline-block; }

        /* CSS saat dicetak - Menyembunyikan komponen navigasi dan filter */
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; max-width: 100%; padding: 0; }
            .filter-box { display: none !important; }
            th { background-color: #2c3e50 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .card-stat { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Dashboard Hasil Survei Kepuasan</h2>
    <p class="text-center">Kinerja Pelayanan PTSP Pengadilan</p>

    <!-- Form Filter Rentang Tanggal -->
    <div class="filter-box">
        <form method="GET" action="rekap.php" class="filter-form">
            <div class="form-group-filter">
                <label>Tanggal Awal:</label>
                <input type="date" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
            </div>
            
            <div class="form-group-filter">
                <label>Tanggal Akhir:</label>
                <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn-filter">Filter Data</button>
                <button type="button" class="btn-print" onclick="window.print()">Cetak Laporan / PDF</button>
                <?php if (!empty($tanggal_awal)): ?>
                    <a href="rekap.php" class="btn-reset">Tampilkan Semua</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </form>
    </div>

    <p style="font-size: 15px; color: #555;">
        Responden Ditemukan: <strong><?php echo $total_responden; ?> Orang</strong> 
        <?php if (!empty($tanggal_awal)) echo "(Periode: " . date('d M Y', strtotime($tanggal_awal)) . " s/d " . date('d M Y', strtotime($tanggal_akhir)) . ")"; ?>
    </p>

    <!-- Statistik Ringkas (Card Box) -->
    <div class="grid-stats">
        <div class="card-stat bg-sangat-puas">
            <div>Sangat Puas 😍</div>
            <h3><?php echo (int)$skor['sangat_puas']; ?></h3>
            <small><?php echo hitungPersen($skor['sangat_puas'], $total_responden); ?>%</small>
        </div>
        <div class="card-stat bg-puas">
            <div>Puas 😊</div>
            <h3><?php echo (int)$skor['puas']; ?></h3>
            <small><?php echo hitungPersen($skor['puas'], $total_responden); ?>%</small>
        </div>
        <div class="card-stat bg-kurang-puas">
            <div>Kurang Puas 😐</div>
            <h3><?php echo (int)$skor['kurang_puas']; ?></h3>
            <small><?php echo hitungPersen($skor['kurang_puas'], $total_responden); ?>%</small>
        </div>
        <div class="card-stat bg-tidak-puas">
            <div>Tidak Puas 😡</div>
            <h3><?php echo (int)$skor['tidak_puas']; ?></h3>
            <small><?php echo hitungPersen($skor['tidak_puas'], $total_responden); ?>%</small>
        </div>
    </div>

    <hr style="border:0; border-top: 1px solid #eee; margin: 30px 0 20px 0;">

    <h3>Kritik, Saran & Riwayat Masukan</h3>
    <table>
        <thead>
            <tr>
                <th width="20%">Waktu</th>
                <th width="25%">Layanan</th>
                <th width="15%">Penilaian</th>
                <th width="40%">Kritik / Saran</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Menampilkan riwayat pengisian sesuai filter tanggal
            $ambil_data = $koneksi->query("SELECT * FROM tabel_survei" . $kondisi_filter . " ORDER BY tanggal DESC LIMIT 30");
            if ($ambil_data && $ambil_data->num_rows > 0) {
                while($row = $ambil_data->fetch_assoc()) {
                    $warna_badge = 'bg-puas';
                    if($row['penilaian'] == 'Sangat Puas') $warna_badge = 'bg-sangat-puas';
                    if($row['penilaian'] == 'Kurang Puas') $warna_badge = 'bg-kurang-puas';
                    if($row['penilaian'] == 'Tidak Puas') $warna_badge = 'bg-tidak-puas';

                    echo "<tr>
                            <td>" . date('d-m-Y H:i', strtotime($row['tanggal'])) . "</td>
                            <td>{$row['layanan']}</td>
                            <td><span class='badge {$warna_badge}'>{$row['penilaian']}</span></td>
                            <td>" . htmlspecialchars($row['saran']) . "</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center; color:#999; padding: 20px;'>Tidak ada data survei pada periode ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $koneksi->close(); ?>