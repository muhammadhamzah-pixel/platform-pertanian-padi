<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'perangkat_desa') {
    header('Location: ' . $base_url . 'login');
    exit();
}

// ===== STATISTIK =====
$total_petani = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM users WHERE role = 'petani'"))['total'];

$data_lahan = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total, SUM(luas) AS total_luas FROM lahan"));
$total_lahan = $data_lahan['total'] ?? 0;
$total_luas = $data_lahan['total_luas'] ?? 0;

$status_query = mysqli_query($conn, 
    "SELECT status, COUNT(*) AS jumlah FROM lahan GROUP BY status");
$status_data = [];
while ($row = mysqli_fetch_assoc($status_query)) {
    $status_data[$row['status']] = $row['jumlah'];
}

$lahan_query = "SELECT lahan.*, users.nama AS nama_petani 
                FROM lahan 
                JOIN users ON lahan.user_id = users.id 
                ORDER BY lahan.id DESC";
$lahan_result = mysqli_query($conn, $lahan_query);

$filename = "Rekap_Desa_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<style>";
echo "table { border-collapse: collapse; width: 100%; }";
echo "th { background: #2e7d32; color: white; padding: 10px; border: 1px solid #000; }";
echo "td { padding: 8px; border: 1px solid #000; }";
echo "h2, h3 { color: #1b5e20; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h2>📊 " . t('recap_title') . "</h2>";
echo "<p><strong>" . t('export_date') . ":</strong> " . date('d-m-Y H:i:s') . "</p>";
echo "<br>";

echo "<h3>📈 " . t('recap_main_stats') . "</h3>";
echo "<table>";
echo "<tr><th>" . t('recap_col_info') . "</th><th>" . t('export_total') . "</th></tr>";
echo "<tr><td>" . t('recap_total_farmer') . "</td><td>" . $total_petani . " " . t('export_unit_people') . "</td></tr>";
echo "<tr><td>" . t('recap_total_land') . "</td><td>" . $total_lahan . " " . t('recap_unit_land') . "</td></tr>";
echo "<tr><td>" . t('recap_total_area') . "</td><td>" . number_format($total_luas, 0) . " m²</td></tr>";
echo "</table>";
echo "<br>";

echo "<h3>🌱 " . t('recap_status') . "</h3>";
echo "<table>";
echo "<tr><th>" . t('status') . "</th><th>" . t('export_total') . "</th></tr>";
echo "<tr><td>" . t('status_planting') . "</td><td>" . ($status_data['tanam'] ?? 0) . " " . t('recap_unit_land') . "</td></tr>";
echo "<tr><td>" . t('status_growing') . "</td><td>" . ($status_data['tumbuh'] ?? 0) . " " . t('recap_unit_land') . "</td></tr>";
echo "<tr><td>" . t('status_harvest') . "</td><td>" . ($status_data['panen'] ?? 0) . " " . t('recap_unit_land') . "</td></tr>";
echo "<tr><td>" . t('status_finished') . "</td><td>" . ($status_data['selesai'] ?? 0) . " " . t('recap_unit_land') . "</td></tr>";
echo "</table>";
echo "<br>";

echo "<h3>🌾 " . t('recap_all_land') . "</h3>";
echo "<table>";
echo "<thead>";
echo "<tr>";
echo "<th>" . t('export_col_no') . "</th>";
echo "<th>" . t('export_farmer_name') . "</th>";
echo "<th>" . t('land_name') . "</th>";
echo "<th>" . t('land_location') . "</th>";
echo "<th>" . t('land_area') . "</th>";
echo "<th>" . t('land_rice_type') . "</th>";
echo "<th>" . t('land_plant_date') . "</th>";
echo "<th>" . t('land_harvest_estimate') . "</th>";
echo "<th>" . t('status') . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$no = 1;
while ($row = mysqli_fetch_assoc($lahan_result)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_petani']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_lahan']) . "</td>";
    echo "<td>" . htmlspecialchars($row['lokasi']) . "</td>";
    echo "<td>" . number_format($row['luas'], 0) . "</td>";
    echo "<td>" . htmlspecialchars($row['jenis_padi']) . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($row['tgl_tanam'])) . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($row['tgl_panen_perkiraan'])) . "</td>";
    echo "<td>" . t('status_' . $row['status']) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</body>";
echo "</html>";
exit();
?>