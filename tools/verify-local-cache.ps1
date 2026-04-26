$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$redisScript = Join-Path $projectRoot 'tools\redis-compat-server.cjs'
$redisCli = Join-Path $projectRoot 'tools\resp-cli.cjs'

$preferredPhp = 'C:\tools\php84\php.exe'
$fallbackPhp = 'C:\Users\Pongoe\.php\php.exe'

if (Test-Path $preferredPhp) {
    $phpExe = $preferredPhp
} elseif (Test-Path $fallbackPhp) {
    $phpExe = $fallbackPhp
} else {
    $phpExe = (Get-Command php).Source
}

$redis = $null
$php = $null

try {
    $redis = Start-Process node -ArgumentList $redisScript -WorkingDirectory $projectRoot -PassThru
    Start-Sleep -Seconds 2

    $ping = node $redisCli PING
    if ($ping.Trim() -ne 'PONG') {
        throw "Redis compatibility server did not respond with PONG. Got: $ping"
    }

    node $redisCli FLUSHDB | Out-Null
    $before = (node $redisCli DBSIZE).Trim()

    & $phpExe artisan config:clear | Out-Null
    & $phpExe artisan cache:clear | Out-Null

    $php = Start-Process $phpExe -ArgumentList 'artisan', 'serve', '--host=127.0.0.1', '--port=8000' -WorkingDirectory $projectRoot -PassThru
    Start-Sleep -Seconds 3

    $health = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8000/health'
    $tasks1 = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8000/api/78688/v1/tasks'
    $afterGet1 = (node $redisCli DBSIZE).Trim()

    $body = '{"title":"Created after caching","description":"This write should invalidate tasks.index","status":"todo","priority":"medium"}'
    $post = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8000/api/78688/v1/tasks' -Method POST -ContentType 'application/json' -Body $body
    $afterPost = (node $redisCli DBSIZE).Trim()

    $tasks2 = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8000/api/78688/v1/tasks'
    $afterGet2 = (node $redisCli DBSIZE).Trim()

    Write-Output "PING: $($ping.Trim())"
    Write-Output "HEALTH_STATUS: $($health.StatusCode)"
    Write-Output "HEALTH_BODY: $($health.Content)"
    Write-Output "TASKS1_STATUS: $($tasks1.StatusCode)"
    Write-Output "TASKS1_BODY: $($tasks1.Content)"
    Write-Output "POST_STATUS: $($post.StatusCode)"
    Write-Output "POST_BODY: $($post.Content)"
    Write-Output "TASKS2_STATUS: $($tasks2.StatusCode)"
    Write-Output "TASKS2_BODY: $($tasks2.Content)"
    Write-Output "DBSIZE_BEFORE: $before"
    Write-Output "DBSIZE_AFTER_GET1: $afterGet1"
    Write-Output "DBSIZE_AFTER_POST: $afterPost"
    Write-Output "DBSIZE_AFTER_GET2: $afterGet2"

    if ($health.StatusCode -ne 200) {
        throw 'Health endpoint did not return 200.'
    }

    if ($tasks1.StatusCode -ne 200 -or $tasks2.StatusCode -ne 200) {
        throw 'Tasks endpoint did not return 200.'
    }

    if ($afterGet1 -ne '1' -or $afterPost -ne '0' -or $afterGet2 -ne '1') {
        throw 'Cache population/invalidation sequence did not match expected 1 -> 0 -> 1 pattern.'
    }
} finally {
    if ($php -and -not $php.HasExited) {
        Stop-Process -Id $php.Id -Force
    }

    if ($redis -and -not $redis.HasExited) {
        Stop-Process -Id $redis.Id -Force
    }
}
