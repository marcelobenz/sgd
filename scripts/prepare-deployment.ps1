[CmdletBinding()]
param(
    [string]$PhpPath = '',
    [switch]$ValidateWorkingTree
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$projectRoot = Split-Path -Parent $PSScriptRoot
$originalLocation = Get-Location
$savedEnvironment = @{}
$environmentOverrides = @{
    APP_ENV = 'testing'
    APP_CONFIG_CACHE = Join-Path $projectRoot '.runtime/deployment/unused-testing-config.php'
    DB_CONNECTION = 'mysql'
    DB_DATABASE = 'sgd_testing'
    DATABASE_URL = ''
    DB_URL = ''
    CACHE_DRIVER = 'array'
    SESSION_DRIVER = 'array'
    QUEUE_CONNECTION = 'sync'
    MAIL_MAILER = 'array'
}

function Invoke-Checked {
    param([string]$Executable, [string[]]$Arguments)
    & $Executable @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Fallo $Executable (codigo $LASTEXITCODE). No hay una preparacion aprobada."
    }
}

function Get-GitOutput {
    param([string[]]$Arguments)
    $output = & git @Arguments
    if ($LASTEXITCODE -ne 0) { throw 'Fallo una comprobacion de Git.' }
    return $output
}

try {
    Set-Location $projectRoot
    foreach ($name in @('git', 'node', 'npm.cmd')) {
        if (-not (Get-Command $name -ErrorAction SilentlyContinue)) { throw "Falta $name en PATH." }
    }
    if (-not $PhpPath) {
        $phpCommand = Get-Command php -ErrorAction SilentlyContinue
        if ($phpCommand) {
            $PhpPath = $phpCommand.Source
        } else {
            $laragonPhp = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe'
            if (Test-Path -LiteralPath $laragonPhp) { $PhpPath = $laragonPhp }
        }
    }
    if (-not $PhpPath -or -not (Test-Path -LiteralPath $PhpPath)) { throw 'Indicar PHP mediante -PhpPath.' }
    foreach ($required in @('vendor/autoload.php', 'vendor/bin/pint', 'phpunit.xml', 'package-lock.json')) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Falta $required. Instalar las dependencias locales primero." }
    }

    $runtimeDirectory = Join-Path $projectRoot '.runtime/deployment'
    New-Item -ItemType Directory -Path $runtimeDirectory -Force | Out-Null
    $receiptPath = Join-Path $runtimeDirectory 'preparation.json'
    if (Test-Path -LiteralPath $receiptPath) { Remove-Item -LiteralPath $receiptPath }
    if (Test-Path -LiteralPath $environmentOverrides.APP_CONFIG_CACHE) {
        throw 'Existe el archivo reservado para evitar la cache de configuracion. Revisarlo manualmente.'
    }

    $initialCommit = Get-GitOutput -Arguments @('rev-parse', 'HEAD')
    $initialBranch = Get-GitOutput -Arguments @('branch', '--show-current')
    $initialStatus = @(Get-GitOutput -Arguments @('status', '--porcelain', '--untracked-files=all', '--', '.', ':(exclude)vendor'))
    if ($initialStatus.Count -gt 0 -and -not $ValidateWorkingTree) {
        throw 'Hay cambios sin commit. Usar -ValidateWorkingTree para validarlos, o hacer commit antes de preparar una version.'
    }
    Invoke-Checked -Executable 'git' -Arguments @('diff', '--check')
    Invoke-Checked -Executable 'git' -Arguments @('diff', '--cached', '--check')

    foreach ($key in $environmentOverrides.Keys) {
        $savedEnvironment[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
        [Environment]::SetEnvironmentVariable($key, $environmentOverrides[$key], 'Process')
    }
    # An empty PowerShell environment variable is removed in Windows PowerShell 5.1.
    # Use Laravel's explicit null token to prevent DATABASE_URL from .env taking precedence.
    [Environment]::SetEnvironmentVariable('DATABASE_URL', '(null)', 'Process')
    [Environment]::SetEnvironmentVariable('DB_URL', '(null)', 'Process')

    Write-Host 'Verificando base de pruebas antes de ejecutar PHPUnit...'
    Invoke-Checked -Executable $PhpPath -Arguments @('scripts/check-deployment.php', 'testing')
    Write-Host 'Verificando formato de los archivos PHP del cambio...'
    Get-GitOutput -Arguments @('rev-parse', '--verify', 'refs/heads/main') | Out-Null
    $base = Get-GitOutput -Arguments @('merge-base', 'HEAD', 'main')
    # On main validate the latest commit; on a feature branch validate its changes against main.
    if ($base -eq $initialCommit) {
        $parent = & git rev-parse 'HEAD^' 2>$null
        if ($LASTEXITCODE -eq 0) { $base = $parent }
    }
    $phpFiles = @(
        Get-GitOutput -Arguments @('diff', '--name-only', '--diff-filter=ACMR', $base, '--', '*.php', ':(exclude)vendor', ':(exclude)*.blade.php')
        Get-GitOutput -Arguments @('diff', '--cached', '--name-only', '--diff-filter=ACMR', '--', '*.php', ':(exclude)vendor', ':(exclude)*.blade.php')
        Get-GitOutput -Arguments @('ls-files', '--others', '--exclude-standard', '--', '*.php', ':(exclude)vendor', ':(exclude)*.blade.php')
    ) | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Sort-Object -Unique
    foreach ($file in $phpFiles) {
        Invoke-Checked -Executable $PhpPath -Arguments @('vendor/bin/pint', '--test', $file)
    }
    Write-Host 'Ejecutando la suite completa...'
    Invoke-Checked -Executable $PhpPath -Arguments @('artisan', 'test', '--no-coverage', '--env=testing')
    Write-Host 'Instalando dependencias frontend y compilando assets...'
    Invoke-Checked -Executable 'npm.cmd' -Arguments @('ci', '--no-audit', '--no-fund')
    Invoke-Checked -Executable 'npm.cmd' -Arguments @('run', 'build')
    Invoke-Checked -Executable $PhpPath -Arguments @('scripts/check-deployment.php', 'assets')
    Invoke-Checked -Executable 'git' -Arguments @('diff', '--check')

    $finalCommit = Get-GitOutput -Arguments @('rev-parse', 'HEAD')
    $finalBranch = Get-GitOutput -Arguments @('branch', '--show-current')
    $finalStatus = @(Get-GitOutput -Arguments @('status', '--porcelain', '--untracked-files=all', '--', '.', ':(exclude)vendor'))
    if ($finalCommit -ne $initialCommit -or $finalBranch -ne $initialBranch) {
        throw 'Cambio el commit o el branch durante la preparacion. Ejecutar nuevamente.'
    }
    if ($finalStatus.Count -gt 0) {
        if (-not $ValidateWorkingTree) {
            throw 'La compilacion produjo cambios. Revisarlos, incorporarlos al commit y ejecutar nuevamente.'
        }
        Write-Host 'Validacion del arbol de trabajo completa. Revisar/confirmar cambios y repetir sin -ValidateWorkingTree.'
        Write-Host 'No se genera constancia de version lista para produccion.'
    } else {
        $receipt = [ordered]@{
            commit = $finalCommit
            branch = $finalBranch
            preparedAtUtc = [DateTime]::UtcNow.ToString('o')
            testDatabase = 'sgd_testing'
            checks = @('git diff --check', 'Pint de archivos PHP del cambio', 'suite completa', 'npm ci', 'npm run build', 'manifest y assets')
        }
        $receipt | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath $receiptPath -Encoding UTF8
        Write-Host "Preparacion completa para $finalCommit. Constancia: $receiptPath"
        Write-Host 'La integracion en main y la publicacion requieren volver a validar el SHA resultante.'
    }
} finally {
    foreach ($key in $savedEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($key, $savedEnvironment[$key], 'Process')
    }
    Set-Location $originalLocation
}
