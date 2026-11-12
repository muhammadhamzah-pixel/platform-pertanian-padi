<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petani') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM lahan WHERE user_id = '$user_id' ORDER BY id DESC";
$result = mysqli_query($conn, $query);

$filename = "Data_Lahan_" . $_SESSION['nama'] . "_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<style>";
echo "table { border-collapse: collapse; width: 100%; }";
echo "th { background: #2e7d32; color: white; padding: 10px; border: 1px solid #000; font-weight: bold; }";
echo "td { padding: 8px; border: 1px solid #000; }";
echo "h2 { color: #1b5e20; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h2>📋 " . t('land_title_data') . "</h2>";
echo "<p><strong>" . t('export_farmer_name') . ":</strong> " . htmlspecialchars($_SESSION['nama']) . "</p>";
echo "<p><strong>" . t('export_date') . ":</strong> " . date('d-m-Y H:i:s') . "</p>";
echo "<br>";

echo "<table>";
echo "<thead>";
echo "<tr>";
echo "<th>" . t('export_col_no') . "</th>";
echo "<th>" . t('land_name') . "</th>";
echo "<th>" . t('land_location') . "</th>";
echo "<th>" . t('land_area') . "</th>";
echo "<th>" . t('land_rice_type') . "</th>";
echo "<th>" . t('land_plant_date') . "</th>";
echo "<th>" . t('land_harvest_estimate') . "</th>";
echo "<th>" . t('status') . "</th>";
echo "<th>" . t('land_notes') . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_lahan']) . "</td>";
    echo "<td>" . htmlspecialchars($row['lokasi']) . "</td>";
    echo "<td>" . number_format($row['luas'], 0) . "</td>";
    echo "<td>" . htmlspecialchars($row['jenis_padi']) . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($row['tgl_tanam'])) . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($row['tgl_panen_perkiraan'])) . "</td>";
    echo "<td>" . t('status_' . $row['status']) . "</td>";
    echo "<td>" . htmlspecialchars($row['catatan'] ?? '-') . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

echo "</body>";
echo "</html>";
exit();
?>