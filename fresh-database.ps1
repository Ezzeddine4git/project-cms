[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$RepoRoot = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
$LocalPhp = 'C:\php\php.exe'
$Artisan = Join-Path $RepoRoot 'artisan'

function Resolve-Php {
    if (Test-Path -LiteralPath $LocalPhp -PathType Leaf) {
        return $LocalPhp
    }

    $php = Get-Command php -ErrorAction SilentlyContinue
    if ($php) {
        return $php.Source
    }

    throw 'PHP was not found. Run plug-and-play.ps1 first.'
}

Set-Location -LiteralPath $RepoRoot
$phpExe = Resolve-Php

Write-Host '==> Running migrate:fresh' -ForegroundColor Cyan
& $phpExe $Artisan migrate:fresh --force
if ($LASTEXITCODE -ne 0) {
    throw "migrate:fresh failed with exit code $LASTEXITCODE."
}

Write-Host 'Database migrations are fresh.'
