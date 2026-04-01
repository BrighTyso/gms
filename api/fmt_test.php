<?php
echo "fmtAmount test: ";
function fmtAmount($val) {
    $val = (float)$val;
    if($val >= 1000000) return '$'.number_format($val/1000000, 2).'M';
    if($val >= 100000)  return '$'.number_format($val/1000, 1).'K';
    if($val >= 10000)   return '$'.number_format($val/1000, 1).'K';
    return '$'.number_format($val, 2);
}
echo fmtAmount(482368); // should output $482.4K
echo "<br>If you see 482,368.00 your server is caching the old file.";
