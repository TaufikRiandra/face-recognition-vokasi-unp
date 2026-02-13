<?php
// Set timezone ke WIB (UTC+7) - Jakarta
date_default_timezone_set('Asia/Jakarta');

$conn = mysqli_connect("localhost","root","","absensi_labor");
if(!$conn){
    die("Koneksi gagal");
}

// Set timezone MySQL juga ke WIB
mysqli_query($conn, "SET time_zone='+07:00'");