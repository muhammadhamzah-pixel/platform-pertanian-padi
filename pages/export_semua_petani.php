<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'perangkat_desa') {
    header('Location: ' . $base_url . 'login');
    exit();
}

$query = "SELECT * FROM users WHERE role = 'petani' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$filename = "Data_Semua_Petani_" . date('Y-m-d') . ".xls";

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
echo "h2 { color: #1b5e20; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h2>👨🌾 " . t('farmers_title') . "</h2>";
echo "<p><strong>" . t('export_date') . ":</strong> " . date('d-m-Y H:i:s') . "</p>";
echo "<p><strong>" . t('recap_total_farmer') . ":</strong> " . mysqli_num_rows($result) . " " . t('export_unit_people') . "</p>";
echo "<br>";

echo "<table>";
echo "<thead>";
echo "<tr>";
echo "<th>" . t('export_col_no') . "</th>";
echo "<th>" . t('manage_user_col_email') . "</th>";
echo "<th>" . t('manage_user_col_name') . "</th>";
echo "<th>" . t('phone') . "</th>";
echo "<th>" . t('address') . "</th>";
echo "<th>" . t('manage_user_col_status') . "</th>";
echo "<th>" . t('farmers_col_date') . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . htmlspecialchars($row['email'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['nama'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['no_hp'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['alamat'] ?? '-') . "</td>";
    echo "<td>" . t('status_' . $row['status']) . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($row['created_at'])) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</body>";
echo "</html>";
exit();
?>