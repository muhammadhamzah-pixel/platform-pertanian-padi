<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$query = "SELECT * FROM log_aktivitas ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$filename = "Log_Aktivitas_" . date('Y-m-d') . ".xls";

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

echo "<h2>📋 " . t('log_title') . "</h2>";
echo "<p><strong>" . t('export_date') . ":</strong> " . date('d-m-Y H:i:s') . "</p>";
echo "<p><strong>" . t('export_total') . ":</strong> " . mysqli_num_rows($result) . "</p>";
echo "<br>";

echo "<table>";
echo "<thead>";
echo "<tr>";
echo "<th>" . t('export_col_no') . "</th>";
echo "<th>" . t('log_col_time') . "</th>";
echo "<th>" . t('log_col_name') . "</th>";
echo "<th>" . t('log_col_email') . "</th>";
echo "<th>" . t('log_col_activity') . "</th>";
echo "<th>" . t('log_col_description') . "</th>";
echo "<th>" . t('log_col_ip') . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . date('d-m-Y H:i:s', strtotime($row['created_at'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['nama'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['email'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['aktivitas']) . "</td>";
    echo "<td>" . htmlspecialchars($row['keterangan'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['ip_address'] ?? '-') . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</body>";
echo "</html>";
exit();
?>