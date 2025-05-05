while ($true) {
    Set-Location "C:\Users\gmcor\Documents\Lavori Web\ERP-Core 2\erp-core"

    # Controlla se ci sono modifiche
    $status = git status --porcelain

    if ($status) {
        git add .
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        git commit -m "Auto commit da VSCode alle $timestamp" 2>$null
        git push
    }

    Start-Sleep -Seconds 300  # Aspetta 5 minuti
}