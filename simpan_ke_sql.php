<?php
// Pastikan baris <?php berada di baris paling pertama tanpa spasi atau baris kosong sebelumnya

$host     = "localhost";
$username = "root";       
$password = "";           
$database = "db_ptsp";    

$koneksi = new mysqli($host, $username, $password, $database);

if ($koneksi->connect_error) {
    die("Koneksi gagal ke database: " . $koneksi->connect_error);
}

if (isset($_POST['layanan'])) {
    // Mengamankan input string dari karakter berbahaya (SQL Injection)
    $layanan   = $koneksi->real_escape_string($_POST['layanan']);
    $penilaian = $koneksi->real_escape_string($_POST['penilaian']);
    $saran     = $koneksi->real_escape_string($_POST['saran']);

    // Memasukkan data ke dalam tabel_survei
    $query = "INSERT INTO tabel_survei (layanan, penilaian, saran, tanggal) VALUES ('$layanan', '$penilaian', '$saran', NOW())";

    if ($koneksi->query($query) === TRUE) {
        echo "OK"; 
    } else {
        echo "Error SQL: " . $koneksi->error;
    }
} else {
    echo "Data POST tidak terdeteksi oleh sistem.";
}

$koneksi->close();
?>