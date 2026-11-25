# Run from any directory after npm ci in llm-wiki/tools/qmd.
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$cli = Join-Path $PSScriptRoot 'qmd/node_modules/@tobilu/qmd/bin/qmd'
function Invoke-Qmd {
    & node $cli @args
    if ($LASTEXITCODE -ne 0) { throw "QMD exited with code $LASTEXITCODE" }
}

Push-Location $root
try {
    if (!(Test-Path '.qmd/index.yml')) { Invoke-Qmd init }
    $collections = @(
        @{ Name = 'raw'; Path = 'llm-wiki/raw'; Mask = '**/*.md' }
        @{ Name = 'wiki'; Path = 'llm-wiki/wiki'; Mask = '**/*.md' }
        @{ Name = 'code'; Path = '.'; Mask = '{bootstrap/{app,providers}.php,{app,config,routes,lang,database/migrations,database/seeders,database/factories,resources/views,resources/js,resources/css,tests}/**/*.{php,js,mjs,ts,css}}' }
    )
    foreach ($c in $collections) {
        $existing = & node $cli collection show $c.Name 2>$null
        if ($LASTEXITCODE -eq 0) {
            $expectedPath = (Resolve-Path $c.Path).Path
            $actualPath = ($existing | Select-String '^  Path:\s+(.+)$').Matches.Groups[1].Value
            $actualMask = ($existing | Select-String '^  Pattern:\s+(.+)$').Matches.Groups[1].Value
            if ($actualPath -ne $expectedPath -or $actualMask -cne $c.Mask) {
                throw "Existing QMD collection '$($c.Name)' differs from the project configuration."
            }
            continue
        }
        Invoke-Qmd collection add $c.Path --name $c.Name --mask $c.Mask
    }
    Invoke-Qmd update
    Invoke-Qmd status

    # Codex starts this stdio server in the project whose QMD index it serves.
    $config = Join-Path $root '.codex/config.toml'
    $content = if (Test-Path $config) { [IO.File]::ReadAllText($config) } else { '' }
    if ($content -notmatch '(?m)^\s*\[mcp_servers\.qmd\]\s*$') {
        $quotedRoot = $root.Replace('\\', '/') | ConvertTo-Json -Compress
        $section = @"
[mcp_servers.qmd]
command = "node"
args = ["llm-wiki/tools/qmd/node_modules/@tobilu/qmd/bin/qmd", "mcp"]
cwd = $quotedRoot
startup_timeout_sec = 30
"@
        [void][IO.Directory]::CreateDirectory((Split-Path $config))
        [IO.File]::AppendAllText($config, [Environment]::NewLine + $section + [Environment]::NewLine)
    }
} finally {
    Pop-Location
}
