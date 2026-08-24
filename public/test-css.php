<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSS Test</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <h1>CSS Load Test</h1>
    <p>If you see cyan background, magenta border, and red dashed borders on all elements, CSS is loading.</p>
    <div style="padding: 20px; margin: 20px; background: white;">
        <h2>Test Box</h2>
        <p>This should have red dashed border.</p>
    </div>
</body>
</html>
