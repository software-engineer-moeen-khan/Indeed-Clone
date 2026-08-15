param(
    [int]$Port = 8000,
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

function Require-Command {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][string]$InstallHint
    )

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "$Name is not available in PATH. $InstallHint"
    }
}

function Test-LocalPort {
    param([int]$PortToCheck)

    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $connect = $client.BeginConnect('127.0.0.1', $PortToCheck, $null, $null)
        if (-not $connect.AsyncWaitHandle.WaitOne(300)) {
            return $false
        }
        $client.EndConnect($connect)
        return $true
    }
    catch {
        return $false
    }
    finally {
        $client.Close()
    }
}

function Show-LaravelLogTail {
    $logDirectory = Join-Path $projectRoot 'storage\logs'
    if (-not (Test-Path $logDirectory)) {
        return
    }

    $latestLog = Get-ChildItem $logDirectory -Filter '*.log' -File -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if ($latestLog) {
        Write-Host ''
        Write-Host "Latest Laravel log: $($latestLog.FullName)" -ForegroundColor Yellow
        Write-Host '---------------- Laravel log tail ----------------' -ForegroundColor DarkGray
        Get-Content $latestLog.FullName -Tail 45 -ErrorAction SilentlyContinue
        Write-Host '----------------------------------------------------' -ForegroundColor DarkGray
    }
}

function Test-LaravelForTunnel {
    param([int]$PortToCheck)

    $baseUrl = "http://127.0.0.1:$PortToCheck"
    $headers = @{
        'X-Forwarded-Proto' = 'https'
        'X-Forwarded-Host' = 'preview.trycloudflare.com'
        'X-Forwarded-Port' = '443'
        'X-Forwarded-For' = '203.0.113.10'
        'CF-IPCountry' = 'PK'
    }

    try {
        $health = Invoke-WebRequest -Uri "$baseUrl/up" -UseBasicParsing -TimeoutSec 10
        if ($health.StatusCode -lt 200 -or $health.StatusCode -ge 400) {
            throw "Laravel health endpoint returned HTTP $($health.StatusCode)."
        }

        $home = Invoke-WebRequest -Uri "$baseUrl/" -Headers $headers -UseBasicParsing -TimeoutSec 20
        if ($home.StatusCode -lt 200 -or $home.StatusCode -ge 400) {
            throw "Homepage returned HTTP $($home.StatusCode) with tunnel-style forwarded headers."
        }

        Write-Host "      Preflight passed: /up=$($health.StatusCode), /=$($home.StatusCode)" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host ''
        Write-Host 'Laravel preflight failed before Cloudflare was started.' -ForegroundColor Red
        Write-Host $_.Exception.Message -ForegroundColor Red
        Show-LaravelLogTail
        return $false
    }
}

Require-Command -Name 'php' -InstallHint 'Install PHP and reopen PowerShell.'
Require-Command -Name 'npm.cmd' -InstallHint 'Install Node.js LTS and reopen PowerShell.'
Require-Command -Name 'npx.cmd' -InstallHint 'Install Node.js LTS and reopen PowerShell.'

Write-Host ''
Write-Host 'Geezap Cloudflare temporary share' -ForegroundColor Cyan
Write-Host '---------------------------------' -ForegroundColor DarkGray

Write-Host '[1/5] Clearing Laravel caches...'
& php artisan optimize:clear
if ($LASTEXITCODE -ne 0) {
    throw 'Laravel optimize:clear failed.'
}

if (-not $SkipBuild) {
    Write-Host '[2/5] Building frontend assets for public sharing...'

    if (-not (Test-Path (Join-Path $projectRoot 'node_modules'))) {
        Write-Host 'node_modules not found; installing npm dependencies first...'
        & npm.cmd install
        if ($LASTEXITCODE -ne 0) {
            throw 'npm install failed.'
        }
    }

    $hotFile = Join-Path $projectRoot 'public\hot'
    if (Test-Path $hotFile) {
        Remove-Item $hotFile -Force
    }

    & npm.cmd run build
    if ($LASTEXITCODE -ne 0) {
        throw 'Vite production build failed.'
    }
}
else {
    Write-Host '[2/5] Skipping frontend build.'
}

$serverProcess = $null
$startedServer = $false

try {
    if (Test-LocalPort -PortToCheck $Port) {
        Write-Host "[3/5] A service is already listening on http://127.0.0.1:$Port"
    }
    else {
        Write-Host "[3/5] Starting Laravel on http://127.0.0.1:$Port ..."
        $serverProcess = Start-Process -FilePath 'php' `
            -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$Port") `
            -WorkingDirectory $projectRoot `
            -WindowStyle Hidden `
            -PassThru
        $startedServer = $true

        $ready = $false
        for ($i = 0; $i -lt 40; $i++) {
            Start-Sleep -Milliseconds 250
            if (Test-LocalPort -PortToCheck $Port) {
                $ready = $true
                break
            }
            if ($serverProcess.HasExited) {
                break
            }
        }

        if (-not $ready) {
            Show-LaravelLogTail
            throw "Laravel did not start on port $Port."
        }
    }

    Write-Host '[4/5] Testing Laravel with Cloudflare-style forwarded headers...'
    if (-not (Test-LaravelForTunnel -PortToCheck $Port)) {
        throw 'Tunnel was not started because the Laravel preflight returned an error.'
    }

    Write-Host ''
    Write-Host '[5/5] Creating Cloudflare Quick Tunnel...' -ForegroundColor Green
    Write-Host 'Cloudflare will print a temporary https://*.trycloudflare.com URL below.' -ForegroundColor Yellow
    Write-Host 'Keep this window open while sharing. Press Ctrl+C to close the tunnel.' -ForegroundColor Yellow
    Write-Host ''

    & npx.cmd --yes wrangler@latest tunnel quick-start "http://127.0.0.1:$Port"
}
finally {
    if ($startedServer -and $serverProcess -and -not $serverProcess.HasExited) {
        Write-Host ''
        Write-Host 'Stopping Laravel server started by the share script...'
        Stop-Process -Id $serverProcess.Id -Force -ErrorAction SilentlyContinue
    }
}
