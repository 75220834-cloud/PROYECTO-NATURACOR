@echo off
setlocal
cd /d "D:\ESCRITORIO\UNIVERSIDAD\7mo ciclo\PRUEBAS Y CALIDAD DE SOFTWARE\PROYECTO NATURACOR\naturacor"

echo. > C:\tmp\git_clean_log.txt
echo ============================================ >> C:\tmp\git_clean_log.txt
echo   NATURACOR - Limpieza historial git         >> C:\tmp\git_clean_log.txt
echo ============================================ >> C:\tmp\git_clean_log.txt

echo [1] Log de commits actuales: >> C:\tmp\git_clean_log.txt
git log --oneline >> C:\tmp\git_clean_log.txt 2>&1

echo. >> C:\tmp\git_clean_log.txt
echo [2] Eliminando test_groq.php del historial de git (filter-branch)... >> C:\tmp\git_clean_log.txt
git filter-branch --force --index-filter "git rm --cached --ignore-unmatch test_groq.php" --prune-empty --tag-name-filter cat -- --all >> C:\tmp\git_clean_log.txt 2>&1

echo. >> C:\tmp\git_clean_log.txt
echo [3] Limpiando refs y garbage collection... >> C:\tmp\git_clean_log.txt
git for-each-ref --format="delete %(refname)" refs/original | git update-ref --stdin >> C:\tmp\git_clean_log.txt 2>&1
git reflog expire --expire=now --all >> C:\tmp\git_clean_log.txt 2>&1
git gc --prune=now --aggressive >> C:\tmp\git_clean_log.txt 2>&1

echo. >> C:\tmp\git_clean_log.txt
echo [4] Verificando que test_groq.php ya no esta en el historial... >> C:\tmp\git_clean_log.txt
git log --all --full-history -- test_groq.php >> C:\tmp\git_clean_log.txt 2>&1

echo. >> C:\tmp\git_clean_log.txt
echo [5] Haciendo push limpio a GitHub... >> C:\tmp\git_clean_log.txt
git push -u origin main --force >> C:\tmp\git_clean_log.txt 2>&1

echo. >> C:\tmp\git_clean_log.txt
echo [6] Estado final del repo: >> C:\tmp\git_clean_log.txt
git log --oneline >> C:\tmp\git_clean_log.txt 2>&1
git status >> C:\tmp\git_clean_log.txt 2>&1

echo ============================================ >> C:\tmp\git_clean_log.txt
echo PROCESO COMPLETADO >> C:\tmp\git_clean_log.txt
echo ============================================ >> C:\tmp\git_clean_log.txt

echo.
echo Proceso completado. Resultado guardado en C:\tmp\git_clean_log.txt
type C:\tmp\git_clean_log.txt
