<?php
http_response_code(404);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="not-found-page">
    <div class="not-found-shell">
        <div class="not-found-inner">
            <h1>This page doesn't exist</h1>
            <p>It may have been moved, removed, or<br>never existed.</p>

            <div class="not-found-code">
                <span>404 NOT_FOUND</span>
                <small>bompl::<?php echo bin2hex(random_bytes(8)); ?></small>
            </div>
        </div>

        <div class="not-found-footer">
            <a href="/">VIEW DOCUMENTATION</a>
            <span>/</span>
            <button type="button" class="copy-debug-btn">COPY DEBUG PROMPT</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const copyBtn = document.querySelector('.copy-debug-btn');
            if (!copyBtn) return;

            copyBtn.addEventListener('click', async () => {
                const msg = '404 NOT_FOUND\nRequest failed on employee management route.';
                try {
                    await navigator.clipboard.writeText(msg);
                    const previous = copyBtn.textContent;
                    copyBtn.textContent = 'COPIED';
                    setTimeout(() => copyBtn.textContent = previous, 1200);
                } catch (error) {
                    copyBtn.textContent = 'COPY FAILED';
                }
            });
        });
    </script>
</body>
</html>
