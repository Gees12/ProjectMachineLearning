# Setup aplikasi Laravel DormInsight (Windows PowerShell)
#
# Folder ini berisi kode khusus DormInsight. Skrip ini memasang kerangka
# Laravel 12 standar, menggabungkannya TANPA menimpa file DormInsight,
# lalu memasang paket tambahan.
#
# Pemakaian (di folder backend):
#   powershell -ExecutionPolicy Bypass -File .\setup.ps1

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

foreach ($cmd in 'php', 'composer') {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        throw "$cmd tidak ditemukan. Pasang PHP 8.2+ (mis. XAMPP/Laragon) dan Composer, lalu buka terminal baru."
    }
}

if ((Test-Path 'composer.json') -and (Test-Path 'artisan')) {
    # Project sudah lengkap (hasil clone dari GitHub): cukup pasang dependensi
    Write-Host "1/4 Memasang dependensi dari composer.lock..." -ForegroundColor Cyan
    composer install --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "composer install gagal." }
}
elseif (-not (Test-Path 'vendor')) {
    $tmp = Join-Path $env:TEMP ("dorminsight_laravel_" + (Get-Random))
    Write-Host "1/4 Mengunduh kerangka Laravel 12..." -ForegroundColor Cyan
    composer create-project laravel/laravel $tmp "^12.0" --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) { throw "composer create-project gagal." }

    Write-Host "2/4 Menggabungkan kerangka Laravel (file DormInsight tidak ditimpa)..." -ForegroundColor Cyan
    # /XC /XN /XO = lewati file yang sudah ada; .env dan sqlite bawaan tidak dipakai
    robocopy $tmp . /E /XC /XN /XO /XF .env database.sqlite /NFL /NDL /NJH /NJS | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "Penggabungan file gagal (robocopy $LASTEXITCODE)." }
    Remove-Item $tmp -Recurse -Force
}

if (-not (Select-String -Path composer.json -Pattern 'laravel-dompdf' -Quiet)) {
    Write-Host "3/4 Memasang paket PDF dan Excel..." -ForegroundColor Cyan
    composer require barryvdh/laravel-dompdf rap2hpoutre/fast-excel --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "composer require gagal." }
}

# Zona waktu WITA untuk jadwal buka/tutup survei
$appConfig = Get-Content config/app.php -Raw
$appConfig = $appConfig -replace "'timezone'\s*=>\s*[^,]+,", "'timezone' => env('APP_TIMEZONE', 'Asia/Makassar'),"
Set-Content config/app.php $appConfig -NoNewline

# Test WAJIB memakai SQLite in-memory agar tidak menghapus database MySQL
# (RefreshDatabase menjalankan migrate:fresh pada koneksi yang dipakai test)
$phpunit = Get-Content phpunit.xml -Raw
$phpunit = $phpunit -replace '<!--\s*(<env name="DB_CONNECTION" value="sqlite"/>)\s*-->', '$1'
$phpunit = $phpunit -replace '<!--\s*(<env name="DB_DATABASE" value=":memory:"/>)\s*-->', '$1'
Set-Content phpunit.xml $phpunit -NoNewline
if (-not (php -m | Select-String -Pattern '^pdo_sqlite$' -Quiet)) {
    Write-Host "PERINGATAN: ekstensi pdo_sqlite belum aktif. Aktifkan sebelum menjalankan 'php artisan test'." -ForegroundColor Yellow
}

Write-Host "4/4 Menyiapkan .env..." -ForegroundColor Cyan
if (-not (Test-Path '.env')) { Copy-Item '.env.example' '.env' }
php artisan key:generate

Write-Host ""
Write-Host "Selesai. Langkah berikutnya:" -ForegroundColor Green
Write-Host "  1. Buat database MySQL 'dorminsight' dan sesuaikan DB_* serta ML_API_KEY di .env"
Write-Host "  2. php artisan migrate --seed"
Write-Host "  3. (opsional) php artisan db:seed --class=DemoResponsesSeeder"
Write-Host "  4. php artisan serve   lalu buka http://localhost:8000"
Write-Host "  5. php artisan test"
