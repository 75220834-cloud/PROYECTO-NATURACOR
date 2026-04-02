@echo off
cd /d "D:\ESCRITORIO\UNIVERSIDAD\7mo ciclo\PRUEBAS Y CALIDAD DE SOFTWARE\PROYECTO NATURACOR\naturacor"

echo === Configurando git usuario === >> C:\tmp\git_log.txt
git config user.email "75220834@continental.edu.pe" >> C:\tmp\git_log.txt 2>&1
git config user.name "ItaloRey" >> C:\tmp\git_log.txt 2>&1

echo === Verificando remote === >> C:\tmp\git_log.txt
git remote -v >> C:\tmp\git_log.txt 2>&1

echo === Agregando remote (si no existe) === >> C:\tmp\git_log.txt
git remote remove origin >> C:\tmp\git_log.txt 2>&1
git remote add origin https://github.com/75220834-cloud/PROYECTO-NATURACOR.git >> C:\tmp\git_log.txt 2>&1

echo === Git status === >> C:\tmp\git_log.txt
git status >> C:\tmp\git_log.txt 2>&1

echo === Init si no existe === >> C:\tmp\git_log.txt
git init >> C:\tmp\git_log.txt 2>&1

echo === Add all === >> C:\tmp\git_log.txt
git add . >> C:\tmp\git_log.txt 2>&1

echo === Commit === >> C:\tmp\git_log.txt
git commit -m "feat: agrega tests completos Fase 1 - RecetarioTest, IATest, ReclamoTest, FidelizacionTest, CordialTest, Unit tests, modulo Reclamos, CordialController, vistas Blade" >> C:\tmp\git_log.txt 2>&1

echo === Push === >> C:\tmp\git_log.txt
git push -u origin main >> C:\tmp\git_log.txt 2>&1
git push -u origin master >> C:\tmp\git_log.txt 2>&1

echo DONE >> C:\tmp\git_log.txt
type C:\tmp\git_log.txt
