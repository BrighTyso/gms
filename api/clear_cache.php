<?php
// Run this once to clear OPcache, then delete this file
if(function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully.";
} else {
    echo "OPcache not enabled or not available.";
}
?>