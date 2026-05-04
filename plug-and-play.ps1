[CmdletBinding()]
param(
    [int]$Port = 8000,
    [string]$HostAddress = '127.0.0.1',
    [switch]$SkipGitPull,
    [switch]$SkipNpm,
    [switch]$SkipTests,
    [switch]$NoServer,
    [switch]$NoBrowser
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$RepoRoot = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
$InstallDir = Join-Path $RepoRoot 'install'
$PhpDir = 'C:\php'
$ComposerDir = 'C:\composer'
$SetupScript = Join-Path $RepoRoot 'setup.ps1'
$Artisan = Join-Path $RepoRoot 'artisan'

$script:ComposerPhar = $null
$script:ComposerCommand = $null

function Write-Step {
    param([Parameter(Mandatory = $true)][string]$Message)

    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function ConvertTo-PowerShellLiteral {
    param([Parameter(Mandatory = $true)][string]$Value)

    return "'" + $Value.Replace("'", "''") + "'"
}

function Invoke-Setup {
    if (-not (Test-Path -LiteralPath $SetupScript -PathType Leaf)) {
        throw "Setup script was not found: $SetupScript"
    }

    Write-Step "Running setup"
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $SetupScript
    if ($LASTEXITCODE -ne 0) {
        throw "Setup failed with exit code $LASTEXITCODE."
    }
}

function Resolve-Php {
    $localPhp = Join-Path $PhpDir 'php.exe'
    if (-not (Test-Path -LiteralPath $localPhp -PathType Leaf)) {
        Invoke-Setup
    }

    if (Test-Path -LiteralPath $localPhp -PathType Leaf) {
        return $localPhp
    }

    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) {
        return $phpCommand.Source
    }

    throw "PHP was not found. Run setup.ps1 first."
}

function Assert-PhpExtensions {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $modules = @(& $PhpExe -m)
    if ($LASTEXITCODE -ne 0) {
        throw "PHP failed to start."
    }

    $requiredExtensions = @('openssl', 'curl', 'zip', 'fileinfo', 'mbstring', 'mysqli', 'pdo_mysql', 'pdo_sqlite', 'sqlite3', 'intl')
    $missing = @($requiredExtensions | Where-Object { $modules -notcontains $_ })
    if ($missing.Count -gt 0) {
        Invoke-Setup
        $modules = @(& $PhpExe -m)
        $missing = @($requiredExtensions | Where-Object { $modules -notcontains $_ })
    }

    if ($missing.Count -gt 0) {
        throw "Missing PHP extensions: $($missing -join ', ')."
    }
}

function Resolve-Composer {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $localPhar = Join-Path $ComposerDir 'composer.phar'
    $localCommands = @(
        (Join-Path $ComposerDir 'composer.cmd'),
        (Join-Path $ComposerDir 'composer.bat'),
        (Join-Path $ComposerDir 'composer.exe')
    )

    if (Test-Path -LiteralPath $localPhar -PathType Leaf) {
        $script:ComposerPhar = $localPhar
        return
    }

    foreach ($command in $localCommands) {
        if (Test-Path -LiteralPath $command -PathType Leaf) {
            $script:ComposerCommand = $command
            return
        }
    }

    $composerCommand = Get-Command composer.cmd -ErrorAction SilentlyContinue
    if (-not $composerCommand) {
        $composerCommand = Get-Command composer.bat -ErrorAction SilentlyContinue
    }
    if (-not $composerCommand) {
        $composerCommand = Get-Command composer -ErrorAction SilentlyContinue
    }

    if ($composerCommand) {
        $script:ComposerCommand = $composerCommand.Source
        return
    }

    Invoke-Setup

    if (Test-Path -LiteralPath $localPhar -PathType Leaf) {
        $script:ComposerPhar = $localPhar
        return
    }

    foreach ($command in $localCommands) {
        if (Test-Path -LiteralPath $command -PathType Leaf) {
            $script:ComposerCommand = $command
            return
        }
    }

    throw "Composer was not found. Run setup.ps1 first."
}

function Invoke-Composer {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)

    if ($script:ComposerPhar) {
        & $script:PhpExe $script:ComposerPhar @Arguments
    } elseif ($script:ComposerCommand) {
        & $script:ComposerCommand @Arguments
    } else {
        throw "Composer was not resolved."
    }

    if ($LASTEXITCODE -ne 0) {
        throw "Composer failed with exit code $LASTEXITCODE."
    }
}

function Invoke-Checked {
    param(
        [Parameter(Mandatory = $true)][string]$File,
        [Parameter(Mandatory = $true)][string[]]$Arguments,
        [Parameter(Mandatory = $true)][string]$Label
    )

    Write-Step $Label
    & $File @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "$Label failed with exit code $LASTEXITCODE."
    }
}

function Get-DotEnvValue {
    param([Parameter(Mandatory = $true)][string]$Name)

    $envPath = Join-Path $RepoRoot '.env'
    if (-not (Test-Path -LiteralPath $envPath -PathType Leaf)) {
        return $null
    }

    $pattern = '^\s*' + [regex]::Escape($Name) + '\s*=\s*(.*)\s*$'
    foreach ($line in Get-Content -LiteralPath $envPath) {
        if ($line -match '^\s*#') {
            continue
        }

        if ($line -match $pattern) {
            $value = $Matches[1].Trim()
            if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
                $value = $value.Substring(1, $value.Length - 2)
            }

            return $value
        }
    }

    return $null
}

function Format-DotEnvValue {
    param([AllowEmptyString()][string]$Value)

    if ($Value -match '\s' -or $Value -match '[#"]') {
        return '"' + $Value.Replace('"', '\"') + '"'
    }

    return $Value
}

function Set-DotEnvValue {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][AllowEmptyString()][string]$Value
    )

    $envPath = Join-Path $RepoRoot '.env'
    if (-not (Test-Path -LiteralPath $envPath -PathType Leaf)) {
        throw ".env does not exist yet."
    }

    $lines = New-Object System.Collections.Generic.List[string]
    $found = $false
    $pattern = '^\s*' + [regex]::Escape($Name) + '\s*='
    $replacement = "$Name=$(Format-DotEnvValue -Value $Value)"

    foreach ($line in Get-Content -LiteralPath $envPath) {
        if (-not $found -and $line -match $pattern) {
            $lines.Add($replacement)
            $found = $true
        } else {
            $lines.Add($line)
        }
    }

    if (-not $found) {
        $lines.Add($replacement)
    }

    Set-Content -LiteralPath $envPath -Value $lines -Encoding ASCII
}

function Ensure-DotEnvDefault {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][AllowEmptyString()][string]$Value,
        [string[]]$ReplaceWhen = @()
    )

    $current = Get-DotEnvValue -Name $Name
    if ([string]::IsNullOrWhiteSpace($current) -or ($ReplaceWhen -contains $current)) {
        Set-DotEnvValue -Name $Name -Value $Value
    }
}

function Get-DotEnvSetting {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [AllowEmptyString()][string]$Default = ''
    )

    $value = Get-DotEnvValue -Name $Name
    if ([string]::IsNullOrWhiteSpace($value)) {
        return $Default
    }

    return $value
}

function Resolve-SqliteDatabasePath {
    param([AllowEmptyString()][string]$Database)

    if ([string]::IsNullOrWhiteSpace($Database)) {
        return (Join-Path $RepoRoot 'database\database.sqlite')
    }

    if ($Database -eq ':memory:') {
        return $Database
    }

    if ([IO.Path]::IsPathRooted($Database)) {
        return $Database
    }

    return (Join-Path $RepoRoot $Database)
}

function Ensure-SqliteDatabase {
    $database = Resolve-SqliteDatabasePath -Database (Get-DotEnvSetting -Name 'DB_DATABASE' -Default '')
    if ($database -eq ':memory:') {
        Write-Host "SQLite is configured for memory; no database file is needed."
        return
    }

    $directory = Split-Path -Parent $database
    if (-not (Test-Path -LiteralPath $directory -PathType Container)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }

    if (-not (Test-Path -LiteralPath $database -PathType Leaf)) {
        New-Item -ItemType File -Path $database -Force | Out-Null
        Write-Host "Created SQLite database: $database"
    } else {
        Write-Host "SQLite database exists: $database"
    }
}

function Ensure-MySqlDatabase {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $database = Get-DotEnvSetting -Name 'DB_DATABASE' -Default ''
    if ([string]::IsNullOrWhiteSpace($database)) {
        Write-Warning "DB_DATABASE is empty; skipping MySQL database setup."
        return
    }

    $hostName = Get-DotEnvSetting -Name 'DB_HOST' -Default '127.0.0.1'
    $port = Get-DotEnvSetting -Name 'DB_PORT' -Default '3306'
    $username = Get-DotEnvSetting -Name 'DB_USERNAME' -Default 'root'
    $password = Get-DotEnvSetting -Name 'DB_PASSWORD' -Default ''
    $socket = Get-DotEnvSetting -Name 'DB_SOCKET' -Default ''
    $charset = Get-DotEnvSetting -Name 'DB_CHARSET' -Default 'utf8mb4'
    $collation = Get-DotEnvSetting -Name 'DB_COLLATION' -Default 'utf8mb4_unicode_ci'

    if ($charset -notmatch '^[A-Za-z0-9_]+$') {
        throw "DB_CHARSET contains unsupported characters: $charset"
    }

    if ($collation -and $collation -notmatch '^[A-Za-z0-9_]+$') {
        throw "DB_COLLATION contains unsupported characters: $collation"
    }

    $previous = @{
        SMART_DB_HOST = $env:SMART_DB_HOST
        SMART_DB_PORT = $env:SMART_DB_PORT
        SMART_DB_NAME = $env:SMART_DB_NAME
        SMART_DB_USER = $env:SMART_DB_USER
        SMART_DB_PASS = $env:SMART_DB_PASS
        SMART_DB_SOCKET = $env:SMART_DB_SOCKET
        SMART_DB_CHARSET = $env:SMART_DB_CHARSET
        SMART_DB_COLLATION = $env:SMART_DB_COLLATION
    }

    try {
        $env:SMART_DB_HOST = $hostName
        $env:SMART_DB_PORT = $port
        $env:SMART_DB_NAME = $database
        $env:SMART_DB_USER = $username
        $env:SMART_DB_PASS = $password
        $env:SMART_DB_SOCKET = $socket
        $env:SMART_DB_CHARSET = $charset
        $env:SMART_DB_COLLATION = $collation

        $phpCode = @'
$host = getenv('SMART_DB_HOST') ?: '127.0.0.1';
$port = getenv('SMART_DB_PORT') ?: '3306';
$db = getenv('SMART_DB_NAME') ?: '';
$user = getenv('SMART_DB_USER') ?: 'root';
$pass = getenv('SMART_DB_PASS');
$socket = getenv('SMART_DB_SOCKET') ?: '';
$charset = getenv('SMART_DB_CHARSET') ?: 'utf8mb4';
$collation = getenv('SMART_DB_COLLATION') ?: 'utf8mb4_unicode_ci';

if ($db === '') {
    fwrite(STDERR, "DB_DATABASE is empty.\n");
    exit(3);
}

$dsn = $socket !== ''
    ? "mysql:unix_socket={$socket};charset={$charset}"
    : "mysql:host={$host};port={$port};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $identifier = '`' . str_replace('`', '``', $db) . '`';
    $sql = "CREATE DATABASE IF NOT EXISTS {$identifier} CHARACTER SET {$charset}";
    if ($collation !== '') {
        $sql .= " COLLATE {$collation}";
    }
    $pdo->exec($sql);

    echo "Database exists: {$db}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Database setup failed: " . $e->getMessage() . "\n");
    exit(2);
}
'@

        $tempPhp = Join-Path ([IO.Path]::GetTempPath()) ("project-cms-db-setup-$([Guid]::NewGuid().ToString('N')).php")
        try {
            Set-Content -LiteralPath $tempPhp -Value ("<?php`n" + $phpCode) -Encoding ASCII
            & $PhpExe $tempPhp
            if ($LASTEXITCODE -ne 0) {
                throw "Could not prepare MySQL database '$database'. Check DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, and that MySQL is running."
            }
        } finally {
            if (Test-Path -LiteralPath $tempPhp -PathType Leaf) {
                Remove-Item -LiteralPath $tempPhp -Force
            }
        }
    } finally {
        foreach ($key in $previous.Keys) {
            if ($null -eq $previous[$key]) {
                Remove-Item -Path "Env:$key" -ErrorAction SilentlyContinue
            } else {
                Set-Item -Path "Env:$key" -Value $previous[$key]
            }
        }
    }
}

function Ensure-ConfiguredDatabase {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $connection = (Get-DotEnvSetting -Name 'DB_CONNECTION' -Default 'sqlite').ToLowerInvariant()
    Write-Step "Checking configured database ($connection)"

    switch ($connection) {
        'sqlite' {
            Ensure-SqliteDatabase
        }
        'mysql' {
            Ensure-MySqlDatabase -PhpExe $PhpExe
        }
        'mariadb' {
            Ensure-MySqlDatabase -PhpExe $PhpExe
        }
        default {
            Write-Warning "Automatic database creation is not implemented for DB_CONNECTION=$connection."
        }
    }
}

function Ensure-LaravelEnvironment {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $envPath = Join-Path $RepoRoot '.env'
    $examplePath = Join-Path $RepoRoot '.env.example'

    if (-not (Test-Path -LiteralPath $envPath -PathType Leaf) -and (Test-Path -LiteralPath $examplePath -PathType Leaf)) {
        Write-Step "Creating .env"
        Copy-Item -LiteralPath $examplePath -Destination $envPath
    }

    Ensure-DotEnvDefault -Name 'APP_NAME' -Value 'Camping Vibes' -ReplaceWhen @('Laravel')
    Ensure-DotEnvDefault -Name 'APP_LOCALE' -Value 'fr' -ReplaceWhen @('en')
    Ensure-DotEnvDefault -Name 'APP_FALLBACK_LOCALE' -Value 'fr' -ReplaceWhen @('en')
    Ensure-DotEnvDefault -Name 'APP_FAKER_LOCALE' -Value 'fr_FR' -ReplaceWhen @('en_US')

    $appKey = Get-DotEnvValue -Name 'APP_KEY'
    if ([string]::IsNullOrWhiteSpace($appKey)) {
        Invoke-Checked -File $PhpExe -Arguments @($Artisan, 'key:generate', '--ansi') -Label 'Generating APP_KEY'
    }

    Ensure-ConfiguredDatabase -PhpExe $PhpExe
}

function Test-PortAvailable {
    param(
        [Parameter(Mandatory = $true)][string]$HostName,
        [Parameter(Mandatory = $true)][int]$PortNumber
    )

    $address = [System.Net.IPAddress]::Parse($HostName)
    $listener = $null
    try {
        $listener = [System.Net.Sockets.TcpListener]::new($address, $PortNumber)
        $listener.Start()
        return $true
    } catch {
        return $false
    } finally {
        if ($listener) {
            $listener.Stop()
        }
    }
}

function Get-AvailablePort {
    param(
        [Parameter(Mandatory = $true)][string]$HostName,
        [Parameter(Mandatory = $true)][int]$StartPort
    )

    for ($candidate = $StartPort; $candidate -le ($StartPort + 20); $candidate++) {
        if (Test-PortAvailable -HostName $HostName -PortNumber $candidate) {
            return $candidate
        }
    }

    throw "No available local port was found from $StartPort to $($StartPort + 20)."
}

function Wait-PortOpen {
    param(
        [Parameter(Mandatory = $true)][string]$HostName,
        [Parameter(Mandatory = $true)][int]$PortNumber,
        [int]$TimeoutSeconds = 20
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    while ((Get-Date) -lt $deadline) {
        $client = New-Object System.Net.Sockets.TcpClient
        try {
            $async = $client.BeginConnect($HostName, $PortNumber, $null, $null)
            if ($async.AsyncWaitHandle.WaitOne(1000)) {
                $client.EndConnect($async)
                return $true
            }
        } catch {
        } finally {
            $client.Close()
        }

        Start-Sleep -Milliseconds 500
    }

    return $false
}

function Start-LaravelServer {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $selectedPort = Get-AvailablePort -HostName $HostAddress -StartPort $Port
    $url = "http://$HostAddress`:$selectedPort"
    $phpDirectory = Split-Path -Parent $PhpExe
    $serverCommand = @(
        "Set-Location -LiteralPath $(ConvertTo-PowerShellLiteral $RepoRoot)",
        "`$env:Path = $(ConvertTo-PowerShellLiteral $phpDirectory) + ';' + `$env:Path",
        "& $(ConvertTo-PowerShellLiteral $PhpExe) $(ConvertTo-PowerShellLiteral $Artisan) serve --host=$HostAddress --port=$selectedPort"
    ) -join '; '

    Write-Step "Starting Laravel server"
    Start-Process -FilePath 'powershell.exe' -WorkingDirectory $RepoRoot -ArgumentList @(
        '-NoExit',
        '-NoProfile',
        '-ExecutionPolicy',
        'Bypass',
        '-Command',
        $serverCommand
    ) | Out-Null

    if (-not (Wait-PortOpen -HostName $HostAddress -PortNumber $selectedPort)) {
        Write-Warning "The server window opened, but port $selectedPort did not respond yet."
    } else {
        try {
            $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 10
            Write-Host "Health check passed: $($response.StatusCode) $url"
        } catch {
            Write-Warning "The server port is open, but the home page health check failed: $($_.Exception.Message)"
        }
    }

    if (-not $NoBrowser) {
        Start-Process $url
    }

    Write-Host "Project is running at $url"
    Write-Host "Admin CMS: $url/admin"
    Write-Host "Admin login: abir@admin.com / admin"
    Write-Host "Demo admin: admin@camping-vibes.test / password"
    Write-Host "Customer login: client@camping-vibes.test / password"
}

Set-Location -LiteralPath $RepoRoot

$script:PhpExe = Resolve-Php
$env:Path = (Split-Path -Parent $script:PhpExe) + ';' + $ComposerDir + ';' + $env:Path

Assert-PhpExtensions -PhpExe $script:PhpExe
Resolve-Composer -PhpExe $script:PhpExe

if (-not $SkipGitPull) {
    $git = Get-Command git.exe -ErrorAction SilentlyContinue
    if (-not $git) {
        $git = Get-Command git -ErrorAction SilentlyContinue
    }

    if ($git -and (Test-Path -LiteralPath (Join-Path $RepoRoot '.git') -PathType Container)) {
        $gitStatus = @(& $git.Source -C $RepoRoot status --porcelain)
        if ($LASTEXITCODE -ne 0) {
            Write-Warning "Could not inspect Git status; skipping git pull."
        } elseif ($gitStatus.Count -gt 0) {
            Write-Warning "Working tree has local changes; skipping git pull."
        } else {
            $upstream = & $git.Source -C $RepoRoot rev-parse --abbrev-ref --symbolic-full-name '@{u}' 2>$null
            if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($upstream)) {
                Write-Warning "No upstream branch is configured; skipping git pull."
            } else {
                Invoke-Checked -File $git.Source -Arguments @('-C', $RepoRoot, 'pull', '--ff-only') -Label 'Pulling latest code'
            }
        }
    } else {
        Write-Warning "Git was not found or this is not a Git checkout; skipping git pull."
    }
}

Write-Step "Installing Composer dependencies"
Invoke-Composer -Arguments @('install', '--no-interaction', '--prefer-dist')

Ensure-LaravelEnvironment -PhpExe $script:PhpExe

Invoke-Checked -File $script:PhpExe -Arguments @($Artisan, 'optimize:clear') -Label 'Clearing Laravel caches'
Invoke-Checked -File $script:PhpExe -Arguments @($Artisan, 'filament:assets') -Label 'Publishing Filament assets'
Invoke-Checked -File $script:PhpExe -Arguments @($Artisan, 'migrate', '--force') -Label 'Running migrations'
New-Item -ItemType Directory -Path (Join-Path $RepoRoot 'storage\app\public') -Force | Out-Null
$publicStorage = Join-Path $RepoRoot 'public\storage'
if (Test-Path -LiteralPath $publicStorage) {
    Write-Step "Linking public storage"
    Write-Host "Public storage link already exists: $publicStorage"
} else {
    Invoke-Checked -File $script:PhpExe -Arguments @($Artisan, 'storage:link') -Label 'Linking public storage'
}

if (-not $SkipNpm -and (Test-Path -LiteralPath (Join-Path $RepoRoot 'package.json') -PathType Leaf)) {
    $npm = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if (-not $npm) {
        $npm = Get-Command npm -ErrorAction SilentlyContinue
    }

    if ($npm) {
        if (-not (Test-Path -LiteralPath (Join-Path $RepoRoot 'node_modules') -PathType Container)) {
            Invoke-Checked -File $npm.Source -Arguments @('install', '--ignore-scripts', '--no-audit', '--no-fund') -Label 'Installing npm dependencies'
        }

        if (-not (Test-Path -LiteralPath (Join-Path $RepoRoot 'public\build\manifest.json') -PathType Leaf)) {
            Invoke-Checked -File $npm.Source -Arguments @('run', 'build') -Label 'Building frontend assets'
        }
    } else {
        Write-Host "npm was not found; skipping optional Vite build because the prototype ships public CSS."
    }
}

if (-not $SkipTests) {
    Invoke-Checked -File $script:PhpExe -Arguments @($Artisan, 'test') -Label 'Running automated tests'
}

if ($NoServer) {
    Write-Step "Setup complete"
    Write-Host "Server start was skipped because -NoServer was used."
    Write-Host "Run: php artisan serve --host=$HostAddress --port=$Port"
} else {
    Start-LaravelServer -PhpExe $script:PhpExe
}

