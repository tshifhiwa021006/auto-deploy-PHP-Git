<?php
// Simple health endpoint used by the Deployer health check.
// Place this file under public/ so it can be reached at https://your-site/health.php

http_response_code(200);
echo "OK";
