<?php
// Extremely basic script to verify if the server is running scripts at all.
// No advanced PHP features, just a safe echo and phpinfo.

echo "<h1>Server Check Successful</h1>";
echo "<p>If you see this, your server isn't completely broken, and your .htaccess is fine.</p>";
echo "<h3>Current PHP Version: " . phpversion() . "</h3>";

// Show extended info
phpinfo();
