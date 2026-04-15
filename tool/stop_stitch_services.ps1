param()

$ErrorActionPreference = 'Stop'

$runtimeDir = Join-Path $PSScriptRoot 'runtime'
New-Item -ItemType Directory -Force -Path $runtimeDir | Out-Null

$serviceDefinitions = @(
  @{
    Name = 'laravel-serve'
    Port = 8000
    CommandFragments = @('artisan serve', '--host=127.0.0.1', '--port=8000')
    ProcessName = 'php.exe'
  },
  @{
    Name = 'reverb'
    Port = 8080
    CommandFragments = @('reverb:start', '--host=0.0.0.0', '--port=8080')
    ProcessName = 'php.exe'
  },
  @{
    Name = 'ai-service'
    Port = 8100
    CommandFragments = @('serve_api.py')
    ProcessName = 'python.exe'
  }
)

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
    Sort-Object ProcessId -Descending
}

foreach ($service in $serviceDefinitions) {
  $pidsToStop = New-Object System.Collections.Generic.HashSet[int]
  $listeningPid = Get-ListeningProcessId -Port $service.Port
  if ($listeningPid) {
    [void]$pidsToStop.Add([int]$listeningPid)
  }

  $matchedProcesses = @(Find-ManagedProcesses -CommandFragments $service.CommandFragments -ProcessName $service.ProcessName)
  $pidFile = Join-Path $runtimeDir "$($service.Name).pid"

  foreach ($process in $matchedProcesses) {
    [void]$pidsToStop.Add([int]$process.ProcessId)
  }

  if ($pidsToStop.Count -eq 0) {
    Write-Host "$($service.Name) was already stopped."
    Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
    continue
  }

  foreach ($processId in ($pidsToStop | Sort-Object -Descending)) {
    Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue
    Write-Host "$($service.Name) stopped (PID $processId)."
  }

  Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
}
