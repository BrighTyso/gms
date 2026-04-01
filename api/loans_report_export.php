<?php
/**
 * GMS · Loans Report — Excel Export
 * File: loans_report_export.php
 * Called from loans_report.php with ?export=xlsx + all filter params
 * Uses PhpSpreadsheet (composer) OR falls back to CSV if not available
 */
require "conn.php";
require "validate.php";
date_default_timezone_set("Africa/Harare");
$conn->query("SET time_zone = '+02:00'");

// ── Active season ─────────────────────────────────────────────────────────────
$seasonId = 0;
$r = $conn->query("SELECT id FROM seasons WHERE active=1 LIMIT 1");
if($r && $row=$r->fetch_assoc()){ $seasonId=(int)$row['id']; $r->free(); }

// ── Filters ───────────────────────────────────────────────────────────────────
$filterOfficer  = isset($_GET['officer_id'])  && $_GET['officer_id']!==''  ? (int)$_GET['officer_id']  : null;
$filterSplitId  = isset($_GET['splitid'])     && $_GET['splitid']!==''     ? (int)$_GET['splitid']     : null;
$filterProduct  = isset($_GET['productid'])   && $_GET['productid']!==''   ? (int)$_GET['productid']   : null;
$filterVerified = isset($_GET['verified'])    && $_GET['verified']!==''    ? (int)$_GET['verified']    : null;
$filterDateFrom = isset($_GET['date_from'])   && $_GET['date_from']!==''   ? $_GET['date_from']        : null;
$filterDateTo   = isset($_GET['date_to'])     && $_GET['date_to']!==''     ? $_GET['date_to']          : null;
$groupBy        = isset($_GET['group_by'])    ? $_GET['group_by']          : 'officer';

if($filterDateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateFrom)) $filterDateFrom = null;
if($filterDateTo   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateTo))   $filterDateTo   = null;

// ── WHERE clause ──────────────────────────────────────────────────────────────
$where = ["l.seasonid=$seasonId"];
if($filterOfficer)  $where[] = "l.userid=$filterOfficer";
if($filterProduct)  $where[] = "l.productid=$filterProduct";
if($filterVerified !== null) $where[] = "l.verified=$filterVerified";
if($filterDateFrom) $where[] = "DATE(l.datetime)>='$filterDateFrom'";
if($filterDateTo)   $where[] = "DATE(l.datetime)<='$filterDateTo'";
$whereStr = implode(' AND ', $where);

// ── Price subquery ────────────────────────────────────────────────────────────
$splitWhere = "seasonid=$seasonId";
if($filterSplitId) $splitWhere .= " AND splitid=$filterSplitId";
$priceSubquery = "
    SELECT pr.productid, pr.splitid, pr.amount, pr.seasonid
    FROM prices pr
    INNER JOIN (
        SELECT productid, splitid, seasonid, MAX(id) AS max_id
        FROM prices WHERE $splitWhere
        GROUP BY productid, splitid, seasonid
    ) latest ON latest.max_id = pr.id
";

// ── Fetch all data ────────────────────────────────────────────────────────────

// KPI summary
$kpi = [];
$r = $conn->query("
    SELECT COUNT(*) AS total_loans,
           COUNT(DISTINCT l.growerid) AS unique_growers,
           COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
           SUM(l.verified=1) AS verified,
           SUM(l.verified=0) AS unverified,
           SUM(l.processed=1) AS processed,
           SUM(l.surrogate=1) AS surrogate,
           SUM(l.sync=0) AS pending_sync
    FROM loans l
    LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
    WHERE $whereStr
");
if($r && $row=$r->fetch_assoc()){ $kpi=$row; $r->free(); }

// Summary grouped
$summaryRows = [];
if($groupBy === 'officer') {
    $r = $conn->query("
        SELECT fo.name AS group_label, COUNT(*) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers, SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               SUM(l.verified=0) AS unverified, SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync, MAX(DATE(l.datetime)) AS last_activity
        FROM loans l JOIN field_officers fo ON fo.userid=l.userid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr GROUP BY l.userid, fo.name ORDER BY total_value DESC
    ");
} elseif($groupBy === 'product') {
    $r = $conn->query("
        SELECT CONCAT(p.name,' (',p.units,')') AS group_label, COUNT(*) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers, SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               SUM(l.verified=0) AS unverified, SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync, MAX(DATE(l.datetime)) AS last_activity
        FROM loans l JOIN products p ON p.id=l.productid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr GROUP BY l.productid, p.name, p.units ORDER BY total_value DESC
    ");
} elseif($groupBy === 'grower') {
    $r = $conn->query("
        SELECT CONCAT(g.name,' ',g.surname,' #',g.grower_num) AS group_label,
               COUNT(*) AS loan_count, COUNT(DISTINCT l.growerid) AS unique_growers,
               SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               SUM(l.verified=0) AS unverified, SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync, MAX(DATE(l.datetime)) AS last_activity
        FROM loans l JOIN growers g ON g.id=l.growerid
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr GROUP BY l.growerid, g.name, g.surname, g.grower_num ORDER BY total_value DESC
    ");
} else {
    $r = $conn->query("
        SELECT DATE(l.datetime) AS group_label, COUNT(*) AS loan_count,
               COUNT(DISTINCT l.growerid) AS unique_growers, SUM(l.quantity) AS total_qty,
               COALESCE(SUM(COALESCE(pr.amount,0)*l.quantity),0) AS total_value,
               SUM(l.verified=0) AS unverified, SUM(l.surrogate=1) AS surrogate,
               SUM(l.sync=0) AS pending_sync, MAX(DATE(l.datetime)) AS last_activity
        FROM loans l
        LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
        WHERE $whereStr GROUP BY DATE(l.datetime) ORDER BY DATE(l.datetime) DESC
    ");
}
if($r){ while($row=$r->fetch_assoc()) $summaryRows[]=$row; $r->free(); }

// All detail rows
$detailRows = [];
$r = $conn->query("
    SELECT l.id, l.receipt_number, DATE(l.datetime) AS loan_date, TIME(l.datetime) AS loan_time,
           g.grower_num, g.name AS gname, g.surname AS gsurname,
           fo.name AS officer, p.name AS product, p.units, l.quantity,
           COALESCE(pr.amount,0) AS unit_price,
           COALESCE(pr.amount,0)*l.quantity AS line_value,
           l.hectares,
           IF(l.verified=1,'Yes','No') AS verified,
           IF(l.processed=1,'Yes','No') AS processed,
           IF(l.surrogate=1,'Yes','No') AS surrogate,
           IF(l.sync=1,'Synced','Pending') AS sync_status,
           l.latitude, l.longitude, l.verified_at, l.processed_at
    FROM loans l
    JOIN growers g ON g.id=l.growerid
    JOIN field_officers fo ON fo.userid=l.userid
    JOIN products p ON p.id=l.productid
    LEFT JOIN ($priceSubquery) pr ON pr.productid=l.productid AND pr.splitid=l.splitid AND pr.seasonid=l.seasonid
    WHERE $whereStr
    ORDER BY l.datetime DESC
");
if($r){ while($row=$r->fetch_assoc()) $detailRows[]=$row; $r->free(); }

$conn->close();

// ── Generate filename ─────────────────────────────────────────────────────────
$filename = 'GMS_Loans_Report_' . date('Y-m-d_His') . '.xlsx';

// ── Try PhpSpreadsheet, fall back to CSV ──────────────────────────────────────
$spreadsheetAvailable = file_exists(__DIR__.'/vendor/autoload.php');

if($spreadsheetAvailable) {
    require __DIR__.'/vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()
        ->setTitle('GMS Loans Report')
        ->setCreator('GMS System')
        ->setDescription('Generated ' . date('Y-m-d H:i:s'));

    // ── Sheet 1: Summary ─────────────────────────────────────────────────────
    $ws = $spreadsheet->getActiveSheet();
    $ws->setTitle('Summary');

    // Title
    $ws->setCellValue('A1', 'GMS — Loans Report');
    $ws->setCellValue('A2', 'Generated: ' . date('d M Y H:i') . ' · Season ' . $seasonId);
    $ws->setCellValue('A3', 'Grouped by: ' . ucfirst($groupBy));
    $ws->mergeCells('A1:J1');
    $ws->mergeCells('A2:J2');
    $ws->mergeCells('A3:J3');

    $titleStyle = ['font'=>['bold'=>true,'size'=>14,'color'=>['rgb'=>'1a5e30']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'0d150d']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_LEFT]];
    $ws->getStyle('A1')->applyFromArray($titleStyle);
    $ws->getStyle('A2:A3')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF4a6b4a'));

    // KPI row
    $ws->setCellValue('A5', 'Total Loans');  $ws->setCellValue('B5', (int)$kpi['total_loans']);
    $ws->setCellValue('C5', 'Unique Growers'); $ws->setCellValue('D5', (int)$kpi['unique_growers']);
    $ws->setCellValue('E5', 'Total Value ($)'); $ws->setCellValue('F5', (float)$kpi['total_value']);
    $ws->setCellValue('G5', 'Unverified'); $ws->setCellValue('H5', (int)$kpi['unverified']);
    $ws->setCellValue('I5', 'Pending Sync'); $ws->setCellValue('J5', (int)$kpi['pending_sync']);
    $ws->getStyle('A5:J5')->getFont()->setBold(true)->setSize(9);
    $ws->getStyle('F5')->getNumberFormat()->setFormatCode('"$"#,##0.00');

    // Headers row 7
    $sumHeaders = [ucfirst($groupBy), 'Loans', 'Growers', 'Qty', 'Total Value ($)', 'Share %', 'Unverified', 'Surrogate', 'Pending Sync', 'Last Activity'];
    foreach($sumHeaders as $ci=>$h) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1);
        $ws->setCellValue($col.'7', $h);
    }
    $ws->getStyle('A7:J7')->applyFromArray([
        'font'=>['bold'=>true,'size'=>9,'color'=>['rgb'=>'3ddc68']],
        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'0d200d']],
        'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER],
    ]);

    // Data rows
    $grandTotal = array_sum(array_column($summaryRows,'total_value'));
    $row = 8;
    foreach($summaryRows as $sr) {
        $share = $grandTotal>0 ? round(($sr['total_value']/$grandTotal)*100,1) : 0;
        $ws->setCellValue('A'.$row, $sr['group_label']);
        $ws->setCellValue('B'.$row, (int)$sr['loan_count']);
        $ws->setCellValue('C'.$row, (int)$sr['unique_growers']);
        $ws->setCellValue('D'.$row, (int)$sr['total_qty']);
        $ws->setCellValue('E'.$row, (float)$sr['total_value']);
        $ws->setCellValue('F'.$row, $share/100);
        $ws->setCellValue('G'.$row, (int)$sr['unverified']);
        $ws->setCellValue('H'.$row, (int)$sr['surrogate']);
        $ws->setCellValue('I'.$row, (int)$sr['pending_sync']);
        $ws->setCellValue('J'.$row, $sr['last_activity']);
        $ws->getStyle('E'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $ws->getStyle('F'.$row)->getNumberFormat()->setFormatCode('0.0%');
        if($row % 2 === 0) $ws->getStyle('A'.$row.':J'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0f1a0f');
        $row++;
    }

    // Totals
    $ws->setCellValue('A'.$row, 'TOTAL');
    $ws->setCellValue('B'.$row, '=SUM(B8:B'.($row-1).')');
    $ws->setCellValue('C'.$row, (int)$kpi['unique_growers']);
    $ws->setCellValue('E'.$row, '=SUM(E8:E'.($row-1).')');
    $ws->setCellValue('F'.$row, 1);
    $ws->getStyle('A'.$row.':J'.$row)->getFont()->setBold(true);
    $ws->getStyle('E'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $ws->getStyle('F'.$row)->getNumberFormat()->setFormatCode('0.0%');

    // Column widths
    $colWidths = [35,10,10,12,18,10,12,12,14,15];
    foreach($colWidths as $ci=>$w) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1);
        $ws->getColumnDimension($col)->setWidth($w);
    }

    // ── Sheet 2: Detail ──────────────────────────────────────────────────────
    $ds = $spreadsheet->createSheet();
    $ds->setTitle('Detail');

    $detHeaders = ['#','Receipt No','Date','Time','Grower','Grower #','Officer','Product','Units','Qty','Unit Price ($)','Line Value ($)','Hectares','Verified','Processed','Surrogate','Sync','Latitude','Longitude','Verified At','Processed At'];
    foreach($detHeaders as $ci=>$h) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1);
        $ds->setCellValue($col.'1', $h);
    }
    $ds->getStyle('A1:U1')->applyFromArray([
        'font'=>['bold'=>true,'size'=>9,'color'=>['rgb'=>'3ddc68']],
        'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'0d200d']],
    ]);

    $row = 2;
    foreach($detailRows as $i=>$dr) {
        $ds->setCellValue('A'.$row, $i+1);
        $ds->setCellValue('B'.$row, $dr['receipt_number']);
        $ds->setCellValue('C'.$row, $dr['loan_date']);
        $ds->setCellValue('D'.$row, $dr['loan_time']);
        $ds->setCellValue('E'.$row, $dr['gname'].' '.$dr['gsurname']);
        $ds->setCellValue('F'.$row, $dr['grower_num']);
        $ds->setCellValue('G'.$row, $dr['officer']);
        $ds->setCellValue('H'.$row, $dr['product']);
        $ds->setCellValue('I'.$row, $dr['units']);
        $ds->setCellValue('J'.$row, (int)$dr['quantity']);
        $ds->setCellValue('K'.$row, (float)$dr['unit_price']);
        $ds->setCellValue('L'.$row, (float)$dr['line_value']);
        $ds->setCellValue('M'.$row, $dr['hectares']);
        $ds->setCellValue('N'.$row, $dr['verified']);
        $ds->setCellValue('O'.$row, $dr['processed']);
        $ds->setCellValue('P'.$row, $dr['surrogate']);
        $ds->setCellValue('Q'.$row, $dr['sync_status']);
        $ds->setCellValue('R'.$row, $dr['latitude']);
        $ds->setCellValue('S'.$row, $dr['longitude']);
        $ds->setCellValue('T'.$row, $dr['verified_at']);
        $ds->setCellValue('U'.$row, $dr['processed_at']);
        $ds->getStyle('K'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $ds->getStyle('L'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        if($row % 2 === 0) $ds->getStyle('A'.$row.':U'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0f1a0f');
        $row++;
    }

    // Totals row
    $ds->setCellValue('A'.$row, 'TOTAL');
    $ds->setCellValue('J'.$row, '=SUM(J2:J'.($row-1).')');
    $ds->setCellValue('L'.$row, '=SUM(L2:L'.($row-1).')');
    $ds->getStyle('A'.$row.':U'.$row)->getFont()->setBold(true);
    $ds->getStyle('L'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');

    // Detail column widths
    $dWidths = [5,18,12,10,28,12,22,18,8,8,14,14,10,10,10,10,10,14,14,20,20];
    foreach($dWidths as $ci=>$w) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1);
        $ds->getColumnDimension($col)->setWidth($w);
    }

    // Auto-filter on detail sheet
    $ds->setAutoFilter('A1:U1');

    // ── Output ───────────────────────────────────────────────────────────────
    $spreadsheet->setActiveSheetIndex(0);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else {
    // ── CSV fallback ──────────────────────────────────────────────────────────
    $csvFilename = str_replace('.xlsx', '.csv', $filename);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$csvFilename.'"');
    $out = fopen('php://output', 'w');

    // Summary sheet
    fputcsv($out, ['GMS Loans Report — Season '.$seasonId.' — Generated '.date('d M Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['=== SUMMARY (Grouped by '.ucfirst($groupBy).') ===']);
    fputcsv($out, ['Total Loans', $kpi['total_loans'], 'Unique Growers', $kpi['unique_growers'], 'Total Value', '$'.number_format($kpi['total_value'],2), 'Unverified', $kpi['unverified']]);
    fputcsv($out, []);
    fputcsv($out, [ucfirst($groupBy),'Loans','Growers','Qty','Total Value','Share %','Unverified','Surrogate','Pending Sync','Last Activity']);
    $grandTotal = array_sum(array_column($summaryRows,'total_value'));
    foreach($summaryRows as $sr) {
        $share = $grandTotal>0 ? round(($sr['total_value']/$grandTotal)*100,1) : 0;
        fputcsv($out, [$sr['group_label'],$sr['loan_count'],$sr['unique_growers'],$sr['total_qty'],number_format($sr['total_value'],2),$share.'%',$sr['unverified'],$sr['surrogate'],$sr['pending_sync'],$sr['last_activity']]);
    }

    fputcsv($out, []);
    fputcsv($out, ['=== DETAIL ===']);
    fputcsv($out, ['#','Receipt No','Date','Time','Grower','Grower #','Officer','Product','Units','Qty','Unit Price','Line Value','Hectares','Verified','Processed','Surrogate','Sync','Latitude','Longitude','Verified At','Processed At']);
    foreach($detailRows as $i=>$dr) {
        fputcsv($out, [$i+1,$dr['receipt_number'],$dr['loan_date'],$dr['loan_time'],$dr['gname'].' '.$dr['gsurname'],$dr['grower_num'],$dr['officer'],$dr['product'],$dr['units'],$dr['quantity'],number_format($dr['unit_price'],2),number_format($dr['line_value'],2),$dr['hectares'],$dr['verified'],$dr['processed'],$dr['surrogate'],$dr['sync_status'],$dr['latitude'],$dr['longitude'],$dr['verified_at'],$dr['processed_at']]);
    }
    fclose($out);
    exit;
}
