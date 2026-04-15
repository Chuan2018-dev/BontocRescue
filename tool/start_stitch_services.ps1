param(
  [switch]$SkipHealthChecks
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$runtimeDir = Join-Path $PSScriptRoot 'runtime'
New-Item -ItemType Directory -Force -Path $runtimeDir | Out-Null

function Resolve-PhpPath {
  $phpCommand = Get-Command php -ErrorAction SilentlyContinue
  if ($phpCommand) {
    return $phpCommand.Source
  }

  $laragonPhp = Get-ChildItem 'C:\laragon\bin\php' -Recurse -Filter php.exe -ErrorAction SilentlyContinue |
    Sort-Object FullName -Descending |
    Select-Object -First 1 -ExpandProperty FullName

  if ($laragonPhp) {
    return $laragonPhp
  }

  throw 'php.exe was not found. Install PHP or add it to PATH.'
}

function Resolve-PythonPath {
  $laragonPython = Get-ChildItem 'C:\laragon\bin\python' -Recurse -Filter python.exe -ErrorAction SilentlyContinue |
    Sort-Object FullName -Descending |
    Select-Object -First 1 -ExpandProperty FullName

  if ($laragonPython) {
    return $laragonPython
  }

  $pythonCommand = Get-Command python -CommandType Application -ErrorAction SilentlyContinue |
    Where-Object { $_.Source -and $_.Source -notmatch 'WindowsApps' } |
    Select-Object -First 1

  if ($pythonCommand) {
    return $pythonCommand.Source
  }

  throw 'python.exe was not found. Install Python or update this script path.'
}

function Get-ListeningProcessId {
  param(
    [Parameter(Mandatory = $true)][int]$Port
  )

  $connection = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
    Select-Object -First 1

  if ($connection) {
    return [int]$connection.OwningProcess
  }

  $line = netstat -ano | Select-String (":$Port") | Select-Object -First 1
  if (-not $line) {
    return $null
  }

  $parts = ($line.ToString() -replace '\s+', ' ').Trim().Split(' ')
  if ($parts.Length -lt 5) {
    return $null
  }

  return [int]$parts[-1]
}

function Find-ManagedProcesses {
  param(
    [Parameter(Mandatory = $true)][string[]]$CommandFragments,
    [Parameter(Mandatory = $true)][string]$ProcessName
  )

  return Get-CimInstance Win32_Process |
    Where-Object {
      if ($_.Name -ine $ProcessName) {
        return $false
      }

      $commandLine = $_.CommandLine

      if (-not $commandLine) {
        return $false
      }

      foreach ($fragment in $CommandFragments) {
        if ($commandLine -notlike "*$fragment*") {
          return $false
        }
      }

      return $true
    } |
    Sort-Object ProcessId
}

function Start-ManagedProcess {
  param(
    [Parameter(Mandatory = $true)][string]$Name,
    [Parameter(Mandatory = $true)][string]$FilePath,
    [Parameter(Mandatory = $true)][string[]]$Arguments,
    [Parameter(Mandatory = $true)][int]$Port,
    [Parameter(Mandatory = $true)][string]$WorkingDirectory,
    [Parameter(Mandatory = $true)][string[]]$CommandFragments,
    [Parameter(Mandatory = $true)][string]$ProcessName
  )

  $pidFile = Join-Path $runtimeDir "$Name.pid"
  $stdoutFile = Join-Path $runtimeDir "$Name.out.log"
  $stderrFile = Join-Path $runtimeDir "$Name.err.log"

  $listeningPid = Get-ListeningProcessId -Port $Port
  if ($listeningPid) {
    Set-Content -Path $pidFile -Value $listeningPid

    return [pscustomobject]@{
      Name = $Name
      Status = 'already_running'
      Pid = $listeningPid
      Stdout = $stdoutFile
      Stderr = $stderrFile
    }
  }

  $existingManagedProcesses = @(Find-ManagedProcesses -CommandFragments $CommandFragments -ProcessName $ProcessName)
  if ($existingManagedProcesses.Count -gt 0) {
    $primaryProcessId = [int]$existingManagedProcesses[0].ProcessId
    Set-Content -Path $pidFile -Value $primaryProcessId

    return [pscustomobject]@{
      Name = $Name
      Status = $(if ($existingManagedProcesses.Count -gt 1) { "already_running ($($existingManagedProcesses.Count) found)" } else { 'already_running' })
      Pid = $primaryProcessId
      Stdout = $stdoutFile
      Stderr = $stderrFile
    }
  }

  if (Test-Path $pidFile) {
    $existingPid = (Get-Content $pidFile -Raw).Trim()
    if ($existingPid) {
      $existingProcess = Get-Process -Id ([int]$existingPid) -ErrorAction SilentlyContinue
      if ($existingProcess) {
        return [pscustomobject]@{
          Name = $Name
          Status = 'already_running'
          Pid = $existingProcess.Id
          Stdout = $stdoutFile
          Stderr = $stderrFile
        }
      }
    }

    Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
  }

  $process = Start-Process `
    -FilePath $FilePath `
    -ArgumentList $Arguments `
    -WorkingDirectory $WorkingDirectory `
    -RedirectStandardOutput $stdoutFile `
    -RedirectStandardError $stderrFile `
    -PassThru `
    -WindowStyle Hidden

  Set-Content -Path $pidFile -Value $process.Id

  return [pscustomobject]@{
    Name = $Name
    Status = 'started'
    Pid = $process.Id
    Stdout = $stdoutFile
    Stderr = $stderrFile
  }
}

function Wait-ForHttp {
  param(
    [Parameter(Mandatory = $true)][string]$Uri,
    [int]$TimeoutSeconds = 20
  )

  $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
  do {
    try {
      $response = Invoke-WebRequest -Uri $Uri -UseBasicParsing -TimeoutSec 3
      return $response.StatusCode
    } catch {
      Start-Sleep -Milliseconds 700
    }
  } while ((Get-Date) -lt $deadline)

  return $null
}

function Wait-ForTcpPort {
  param(
    [Parameter(Mandatory = $true)][string]$TargetHost,
    [Parameter(Mandatory = $true)][int]$Port,
    [int]$TimeoutSeconds = 20
  )

  $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
  do {
    try {
      $client = New-Object System.Net.Sockets.TcpClient
      $async = $client.BeginConnect($TargetHost, $Port, $null, $null)
      if ($async.AsyncWaitHandle.WaitOne(1000, $false)) {
        $client.EndConnect($async)
        $client.Close()
        return $true
      }
      $client.Close()
    } catch {
    }

    Start-Sleep -Milliseconds 500
  } while ((Get-Date) -lt $deadline)

  return $false
}

$php = Resolve-PhpPath
$python = Resolve-PythonPath

$services = @(
  @{
    Name = 'laravel-serve'
    FilePath = $php
    Arguments = @('artisan', 'serve', '--host=127.0.0.1', '--port=8000')
    Port = 8000
    WorkingDirectory = Join-Path $root 'web_system'
    CommandFragments = @('artisan serve', '--host=127.0.0.1', '--port=8000')
    ProcessName = 'php.exe'
  },
  @{
    Name = 'reverb'
    FilePath = $php
    Arguments = @('artisan', 'reverb:start', '--host=0.0.0.0', '--port=8080')
    Port = 8080
    WorkingDirectory = Join-Path $root 'web_system'
    CommandFragments = @('reverb:start', '--host=0.0.0.0', '--port=8080')
    ProcessName = 'php.exe'
  },
  @{
    Name = 'ai-service'
    FilePath = $python
    Arguments = @('serve_api.py')
    Port = 8100
    WorkingDirectory = Join-Path $root 'ai_service'
    CommandFragments = @('serve_api.py')
    ProcessName = 'python.exe'
  }
)

$results = foreach ($service in $services) {
  Start-ManagedProcess @service
}

Write-Host ''
Write-Host 'Stitch services status'
Write-Host '----------------------'
foreach ($result in $results) {
  Write-Host ("{0,-14} {1,-16} PID {2}" -f $result.Name, $result.Status, $result.Pid)
}

if (-not $SkipHealthChecks) {
  $webStatus = Wait-ForHttp -Uri 'http://127.0.0.1:8000'
  $aiStatus = Wait-ForHttp -Uri 'http://127.0.0.1:8100/health'
  $reverbReady = Wait-ForTcpPort -TargetHost '127.0.0.1' -Port 8080

  Write-Host ''
  Write-Host 'Health checks'
  Write-Host '-------------'
  Write-Host ("Web     : {0}" -f ($(if ($webStatus) { "HTTP $webStatus" } else { 'Not reachable yet' })))
  Write-Host ("AI      : {0}" -f ($(if ($aiStatus) { "HTTP $aiStatus" } else { 'Not reachable yet' })))
  Write-Host ("Reverb  : {0}" -f ($(if ($reverbReady) { 'Port 8080 ready' } else { 'Port 8080 not reachable yet' })))
}

Write-Host ''
Write-Host 'URLs'
Write-Host '----'
Write-Host 'Web UI     : http://127.0.0.1:8000'
Write-Host 'LAN Web UI : http://192.168.1.4:8000'
Write-Host 'AI Health  : http://127.0.0.1:8100/health'
Write-Host 'Reverb     : http://127.0.0.1:8080'
Write-Host ''
Write-Host "Logs are stored in: $runtimeDir"
Write-Host "To stop everything, run: pwsh -File .\\tool\\stop_stitch_services.ps1"
