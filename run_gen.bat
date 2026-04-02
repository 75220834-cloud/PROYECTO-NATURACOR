@echo off
python -m pip install python-docx --quiet > C:\tmp\pip_log.txt 2>&1
echo PIP DONE >> C:\tmp\pip_log.txt
python generar_formatos_03_04.py >> C:\tmp\pip_log.txt 2>&1
echo SCRIPT DONE >> C:\tmp\pip_log.txt
type C:\tmp\pip_log.txt
