[CmdletBinding()]
param(
    [int]$Port = 8000,
    [string]$HostAddress = '127.0.0.1',
    [switch]$SkipGitPull,
    [switch]$SkipNpm,
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

    $requiredExtensions = @('openssl', 'curl', 'zip', 'fileinfo', 'mbstring', 'mysqli', 'pdo_mysql')
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

function Ensure-LaravelEnvironment {
    param([Parameter(Mandatory = $true)][string]$PhpExe)

    $envPath = Join-Path $RepoRoot '.env'
    $examplePath = Join-Path $RepoRoot '.env.example'

    if (-not (Test-Path -LiteralPath $envPath -PathType Leaf) -and (Test-Path -LiteralPath $examplePath -PathType Leaf)) {
        Write-Step "Creating .env"
        Copy-Item -LiteralPath $examplePath -Destination $envPath
    }

    $appKey = Get-DotEnvValue -Name 'APP_KEY'
    if ([string]::IsNullOrWhiteSpace($appKey)) {
        Invoke-Checked -File $PhpExe -Arguments @($Artisan, 'key:generate', '--ansi') -Label 'Generating APP_KEY'
    }

    $dbConnection = Get-DotEnvValue -Name 'DB_CONNECTION'
    if ([string]::IsNullOrWhiteSpace($dbConnection)) {
        $dbConnection = 'sqlite'
    }

    if ($dbConnection -eq 'sqlite') {
        $dbDatabase = Get-DotEnvValue -Name 'DB_DATABASE'
        if ([string]::IsNullOrWhiteSpace($dbDatabase) -or $dbDatabase -eq 'database/database.sqlite') {
            $sqlitePath = Join-Path $RepoRoot 'database\database.sqlite'
            if (-not (Test-Path -LiteralPath $sqlitePath -PathType Leaf)) {
                Write-Step "Creating SQLite database file"
                New-Item -ItemType File -Path $sqlitePath -Force | Out-Null
            }
        }
    }
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
    }

    if (-not $NoBrowser) {
        Start-Process $url
    }

    Write-Host "Project is running at $url"
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
        Invoke-Checked -File $git.Source -Arguments @('-C', $RepoRoot, 'pull', '--ff-only') -Label 'Pulling latest code'
    } else {
        Write-Warning "Git was not found or this is not a Git checkout; skipping git pull."
    }
}

Write-Step "Installing Composer dependencies"
Invoke-Composer -Arguments @('install', '--no-interaction', '--prefer-dist')

Ensure-LaravelEnvironment -PhpExe $script:PhpExe

Invoke-Checked -File $script:PhpExe -Arguments @($Artisan, 'migrate', '--force') -Label 'Running migrations'

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
        Write-Warning "npm was not found; frontend assets were not built."
    }
}

Start-LaravelServer -PhpExe $script:PhpExe
