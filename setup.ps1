[CmdletBinding()]
param(
    [switch]$Force,
    [switch]$AddToUserPath,
    [switch]$NoPath,
    [switch]$NoDatabase
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$RepoRoot = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
$InstallDir = Join-Path $RepoRoot 'install'
$PhpDir = 'C:\php'
$ComposerDir = 'C:\composer'
$ComposerSetup = Join-Path $InstallDir 'Composer-Setup.exe'

function Write-Step {
    param([Parameter(Mandatory = $true)][string]$Message)

    Write-Host ''
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Find-PhpZip {
    if (-not (Test-Path -LiteralPath $InstallDir -PathType Container)) {
        throw "Install folder was not found: $InstallDir"
    }

    $zips = @(Get-ChildItem -LiteralPath $InstallDir -Filter 'php-*.zip' -File | Sort-Object LastWriteTime -Descending)
    if ($zips.Count -eq 0) {
        throw "No PHP zip was found in $InstallDir."
    }

    return $zips[0].FullName
}

function Set-IniValue {
    param(
        [AllowEmptyCollection()][AllowEmptyString()][string[]]$Lines,
        [Parameter(Mandatory = $true)][string]$Key,
        [Parameter(Mandatory = $true)][string]$Value
    )

    $pattern = "^\s*;?\s*$([regex]::Escape($Key))\s*="
    $replacement = "$Key = $Value"
    $updated = New-Object System.Collections.Generic.List[string]
    $found = $false

    foreach ($line in $Lines) {
        if (-not $found -and $line -match $pattern) {
            $updated.Add($replacement)
            $found = $true
        } elseif ($found -and $line -match $pattern) {
            continue
        } else {
            $updated.Add($line)
        }
    }

    if (-not $found) {
        $updated.Add($replacement)
    }

    return $updated.ToArray()
}

function Enable-PhpExtension {
    param(
        [AllowEmptyCollection()][AllowEmptyString()][string[]]$Lines,
        [Parameter(Mandatory = $true)][string]$Extension
    )

    $escaped = [regex]::Escape($Extension)
    $pattern = "^\s*;?\s*extension\s*=\s*`"?(php_)?$escaped(\.dll)?`"?\s*(;.*)?$"
    $replacement = "extension=$Extension"
    $updated = New-Object System.Collections.Generic.List[string]
    $found = $false

    foreach ($line in $Lines) {
        if (-not $found -and $line -match $pattern) {
            $updated.Add($replacement)
            $found = $true
        } elseif ($found -and $line -match $pattern) {
            continue
        } else {
            $updated.Add($line)
        }
    }

    if (-not $found) {
        $updated.Add($replacement)
    }

    return $updated.ToArray()
}

function Install-LocalPhp {
    $phpZip = Find-PhpZip
    $phpExe = Join-Path $PhpDir 'php.exe'

    if ($Force -and (Test-Path -LiteralPath $PhpDir)) {
        Write-Step "Removing existing PHP install"
        Remove-Item -LiteralPath $PhpDir -Recurse -Force
    }

    if (-not (Test-Path -LiteralPath $phpExe -PathType Leaf)) {
        Write-Step "Extracting PHP from $(Split-Path -Leaf $phpZip)"
        New-Item -ItemType Directory -Path $PhpDir -Force | Out-Null
        Expand-Archive -LiteralPath $phpZip -DestinationPath $PhpDir -Force
    } else {
        Write-Host "PHP already installed at $phpExe"
    }

    if (-not (Test-Path -LiteralPath $phpExe -PathType Leaf)) {
        throw "PHP extraction completed, but php.exe was not found at $phpExe."
    }

    return $phpExe
}

function Configure-PhpIni {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $phpIni = Join-Path $PhpDir 'php.ini'
    $phpIniProduction = Join-Path $PhpDir 'php.ini-production'
    $phpIniDevelopment = Join-Path $PhpDir 'php.ini-development'
    $extDir = Join-Path $PhpDir 'ext'
    $requiredExtensions = @('openssl', 'curl', 'zip', 'fileinfo', 'mbstring', 'mysqli', 'pdo_mysql')

    foreach ($dll in @($requiredExtensions | ForEach-Object { "php_$_.dll" })) {
        $dllPath = Join-Path $extDir $dll
        if (-not (Test-Path -LiteralPath $dllPath -PathType Leaf)) {
            throw "Missing PHP extension DLL: $dllPath"
        }
    }

    $phpIniNeedsTemplate = -not (Test-Path -LiteralPath $phpIni -PathType Leaf)
    if (-not $phpIniNeedsTemplate) {
        $phpIniNeedsTemplate = ((Get-Item -LiteralPath $phpIni).Length -eq 0)
    }

    if ($phpIniNeedsTemplate) {
        if (Test-Path -LiteralPath $phpIniProduction -PathType Leaf) {
            Copy-Item -LiteralPath $phpIniProduction -Destination $phpIni
        } elseif (Test-Path -LiteralPath $phpIniDevelopment -PathType Leaf) {
            Copy-Item -LiteralPath $phpIniDevelopment -Destination $phpIni
        } else {
            throw "No php.ini template was found in $PhpDir."
        }
    }

    Write-Step "Enabling required PHP extensions"
    $lines = @(Get-Content -LiteralPath $phpIni)
    $lines = Set-IniValue -Lines $lines -Key 'extension_dir' -Value "`"$extDir`""
    foreach ($extension in $requiredExtensions) {
        $lines = Enable-PhpExtension -Lines $lines -Extension $extension
    }
    Set-Content -LiteralPath $phpIni -Value $lines -Encoding ASCII

    $modules = @(& $PhpExe -m)
    if ($LASTEXITCODE -ne 0) {
        throw "PHP failed to start. If Windows reports a missing runtime DLL, install the Microsoft Visual C++ Redistributable for VS 2015-2022 x64."
    }

    foreach ($extension in $requiredExtensions) {
        if ($modules -notcontains $extension) {
            throw "PHP extension '$extension' is not loaded. Check $phpIni."
        }
    }
}

function Write-ComposerWrapper {
    $wrapper = Join-Path $ComposerDir 'composer.cmd'
    $phpExe = Join-Path $PhpDir 'php.exe'
    $content = @(
        '@echo off',
        'set SCRIPT_DIR=%~dp0',
        "`"$phpExe`" `"%SCRIPT_DIR%composer.phar`" %*"
    )

    Set-Content -LiteralPath $wrapper -Value $content -Encoding ASCII
}

function Find-ComposerPhar {
    $candidates = New-Object System.Collections.Generic.List[string]
    $candidates.Add((Join-Path $ComposerDir 'composer.phar'))

    if ($env:ProgramData) {
        $candidates.Add((Join-Path $env:ProgramData 'ComposerSetup\bin\composer.phar'))
    }

    if ($env:APPDATA) {
        $candidates.Add((Join-Path $env:APPDATA 'Composer\vendor\bin\composer.phar'))
    }

    foreach ($candidate in $candidates) {
        if ($candidate -and (Test-Path -LiteralPath $candidate -PathType Leaf)) {
            return $candidate
        }
    }

    return $null
}

function Install-Composer {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    New-Item -ItemType Directory -Path $ComposerDir -Force | Out-Null

    $localPhar = Join-Path $ComposerDir 'composer.phar'
    $setupExitCode = $null
    if ($Force -and (Test-Path -LiteralPath $localPhar -PathType Leaf)) {
        Remove-Item -LiteralPath $localPhar -Force
    }

    if (-not (Test-Path -LiteralPath $localPhar -PathType Leaf)) {
        if (-not (Test-Path -LiteralPath $ComposerSetup -PathType Leaf)) {
            Write-Warning "Composer setup was not found at $ComposerSetup."
        } else {
            Write-Step "Installing Composer with bundled setup"
            $arguments = @(
                '/VERYSILENT',
                '/SUPPRESSMSGBOXES',
                '/NORESTART',
                "/DEV=`"$ComposerDir`"",
                "/PHP=`"$PhpExe`""
            )

            try {
                $process = Start-Process -FilePath $ComposerSetup -ArgumentList $arguments -WorkingDirectory $InstallDir -Wait -PassThru
                if ($process.ExitCode -ne 0) {
                    $setupExitCode = $process.ExitCode
                }
            } catch {
                Write-Warning "Composer setup could not complete: $($_.Exception.Message)"
            }
        }
    }

    $sourcePhar = Find-ComposerPhar
    if ($sourcePhar -and ($sourcePhar -ne $localPhar)) {
        Copy-Item -LiteralPath $sourcePhar -Destination $localPhar -Force
    }

    if (-not (Test-Path -LiteralPath $localPhar -PathType Leaf)) {
        Write-Step "Downloading Composer to $ComposerDir"
        try {
            [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
            Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile $localPhar -UseBasicParsing
        } catch {
            Write-Warning "Composer download failed: $($_.Exception.Message)"
        }
    }

    if (Test-Path -LiteralPath $localPhar -PathType Leaf) {
        Write-ComposerWrapper
        & $PhpExe $localPhar --version
        if ($LASTEXITCODE -ne 0) {
            throw "Local Composer was installed, but it failed to run."
        }

        if ($setupExitCode -ne $null) {
            Write-Host "Composer setup returned code $setupExitCode, but Composer was installed successfully."
        }
        Write-Host "Composer installed at $ComposerDir"
        return
    }

    $composerCommand = Get-Command composer -ErrorAction SilentlyContinue
    if ($composerCommand) {
        Write-Warning "Local composer.phar was not found, but Composer is available on PATH: $($composerCommand.Source)"
        & $composerCommand.Source --version
        if ($LASTEXITCODE -ne 0) {
            throw "Composer was found on PATH, but it failed to run."
        }

        return
    }

    throw "Composer could not be installed. Re-run this script with internet access or install Composer manually."
}

function ConvertFrom-DotEnvValue {
    param([AllowEmptyString()][string]$Value)

    if ($null -eq $Value) {
        return $null
    }

    $result = $Value.Trim()
    if (($result.StartsWith('"') -and $result.EndsWith('"')) -or ($result.StartsWith("'") -and $result.EndsWith("'"))) {
        $result = $result.Substring(1, $result.Length - 2)
    } else {
        $commentIndex = $result.IndexOf(' #')
        if ($commentIndex -ge 0) {
            $result = $result.Substring(0, $commentIndex).TrimEnd()
        }
    }

    return $result
}

function Read-DotEnv {
    $envPath = Join-Path $RepoRoot '.env'
    $values = @{}

    if (-not (Test-Path -LiteralPath $envPath -PathType Leaf)) {
        Write-Warning ".env was not found; skipping database setup."
        return $values
    }

    foreach ($line in Get-Content -LiteralPath $envPath) {
        if ($line -match '^\s*$' -or $line -match '^\s*#') {
            continue
        }

        if ($line -match '^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$') {
            $values[$Matches[1]] = ConvertFrom-DotEnvValue -Value $Matches[2]
        }
    }

    return $values
}

function Get-DotEnvSetting {
    param(
        [Parameter(Mandatory = $true)][hashtable]$Values,
        [Parameter(Mandatory = $true)][string]$Name,
        [AllowEmptyString()][string]$Default = ''
    )

    if ($Values.ContainsKey($Name)) {
        return $Values[$Name]
    }

    return $Default
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
    param([Parameter(Mandatory = $true)][hashtable]$EnvValues)

    $database = Resolve-SqliteDatabasePath -Database (Get-DotEnvSetting -Values $EnvValues -Name 'DB_DATABASE' -Default '')
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
    param(
        [Parameter(Mandatory = $true)][hashtable]$EnvValues,
        [Parameter(Mandatory = $true)][string]$PhpExe
    )

    $database = Get-DotEnvSetting -Values $EnvValues -Name 'DB_DATABASE' -Default ''
    if ([string]::IsNullOrWhiteSpace($database)) {
        Write-Warning "DB_DATABASE is empty; skipping MySQL database setup."
        return
    }

    $hostName = Get-DotEnvSetting -Values $EnvValues -Name 'DB_HOST' -Default '127.0.0.1'
    $port = Get-DotEnvSetting -Values $EnvValues -Name 'DB_PORT' -Default '3306'
    $username = Get-DotEnvSetting -Values $EnvValues -Name 'DB_USERNAME' -Default 'root'
    $password = Get-DotEnvSetting -Values $EnvValues -Name 'DB_PASSWORD' -Default ''
    $socket = Get-DotEnvSetting -Values $EnvValues -Name 'DB_SOCKET' -Default ''
    $charset = Get-DotEnvSetting -Values $EnvValues -Name 'DB_CHARSET' -Default 'utf8mb4'
    $collation = Get-DotEnvSetting -Values $EnvValues -Name 'DB_COLLATION' -Default 'utf8mb4_unicode_ci'

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

    $check = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
    $check->execute([$db]);
    if (!$check->fetchColumn()) {
        fwrite(STDERR, "Database was not created: {$db}\n");
        exit(4);
    }

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

    $envValues = Read-DotEnv
    if ($envValues.Count -eq 0) {
        return
    }

    $connection = (Get-DotEnvSetting -Values $envValues -Name 'DB_CONNECTION' -Default 'sqlite').ToLowerInvariant()
    Write-Step "Checking configured database ($connection)"

    switch ($connection) {
        'sqlite' {
            Ensure-SqliteDatabase -EnvValues $envValues
        }
        'mysql' {
            Ensure-MySqlDatabase -EnvValues $envValues -PhpExe $PhpExe
        }
        'mariadb' {
            Ensure-MySqlDatabase -EnvValues $envValues -PhpExe $PhpExe
        }
        default {
            Write-Warning "Automatic database creation is not implemented for DB_CONNECTION=$connection."
        }
    }
}

function Normalize-PathEntry {
    param([string]$PathEntry)

    if ([string]::IsNullOrWhiteSpace($PathEntry)) {
        return $null
    }

    $trimmed = $PathEntry.Trim().Trim('"')
    try {
        return [IO.Path]::GetFullPath($trimmed).TrimEnd('\')
    } catch {
        return $trimmed.TrimEnd('\')
    }
}

function Add-DirectoryToPathValue {
    param(
        [AllowEmptyString()][string]$PathValue,
        [Parameter(Mandatory = $true)][string]$Directory
    )

    $resolved = (Resolve-Path -LiteralPath $Directory).Path.TrimEnd('\')
    $parts = @()
    if ($PathValue) {
        $parts = @($PathValue -split ';' | Where-Object { $_ })
    }

    $normalizedParts = @($parts | ForEach-Object { Normalize-PathEntry -PathEntry $_ })
    if ($normalizedParts -notcontains $resolved) {
        $parts = @($parts + $resolved)
    }

    return ($parts -join ';')
}

function Add-DirectoryToCurrentPath {
    param([Parameter(Mandatory = $true)][string]$Directory)

    $resolved = (Resolve-Path -LiteralPath $Directory).Path
    $env:Path = Add-DirectoryToPathValue -PathValue $env:Path -Directory $resolved
}

function Add-DirectoryToUserPath {
    param([Parameter(Mandatory = $true)][string]$Directory)

    $resolved = (Resolve-Path -LiteralPath $Directory).Path
    $current = [Environment]::GetEnvironmentVariable('Path', 'User')
    $newPath = Add-DirectoryToPathValue -PathValue $current -Directory $resolved

    if ($newPath -ne $current) {
        [Environment]::SetEnvironmentVariable('Path', $newPath, 'User')
        Write-Host "Added to user PATH: $resolved"
    } else {
        Write-Host "Already on user PATH: $resolved"
    }
}

function Publish-EnvironmentChange {
    try {
        Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;

public static class NativeEnvironmentChange
{
    [DllImport("user32.dll", SetLastError = true, CharSet = CharSet.Auto)]
    public static extern IntPtr SendMessageTimeout(
        IntPtr hWnd,
        uint Msg,
        UIntPtr wParam,
        string lParam,
        uint fuFlags,
        uint uTimeout,
        out UIntPtr lpdwResult);
}
'@ -ErrorAction SilentlyContinue

        $result = [UIntPtr]::Zero
        [NativeEnvironmentChange]::SendMessageTimeout([IntPtr]0xffff, 0x001A, [UIntPtr]::Zero, 'Environment', 0x0002, 5000, [ref]$result) | Out-Null
    } catch {
        Write-Warning "Could not broadcast PATH update: $($_.Exception.Message)"
    }
}

$phpExe = Install-LocalPhp
Configure-PhpIni -PhpExe $phpExe
Install-Composer -PhpExe $phpExe

if (-not $NoPath) {
    Write-Step "Updating user PATH"
    Add-DirectoryToUserPath -Directory $PhpDir
    Add-DirectoryToCurrentPath -Directory $PhpDir

    if (Test-Path -LiteralPath (Join-Path $ComposerDir 'composer.cmd') -PathType Leaf) {
        Add-DirectoryToUserPath -Directory $ComposerDir
        Add-DirectoryToCurrentPath -Directory $ComposerDir
    }

    Publish-EnvironmentChange
}

if (-not $NoDatabase) {
    Ensure-ConfiguredDatabase -PhpExe $phpExe
}

Write-Step "Setup complete"
& $phpExe -v
Write-Host "openssl, curl, zip, fileinfo, mbstring, mysqli, and pdo_mysql are enabled."
