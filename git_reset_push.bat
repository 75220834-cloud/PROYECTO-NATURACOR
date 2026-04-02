@echo off
cd /d "D:\ESCRITORIO\UNIVERSIDAD\7mo ciclo\PRUEBAS Y CALIDAD DE SOFTWARE\PROYECTO NATURACOR\naturacor"

echo ===================================================
echo  PASO 1: Eliminar test_groq.php del proyecto
echo ===================================================
del /f /q test_groq.php

echo ===================================================
echo  PASO 2: Eliminar historial git corrompido
echo ===================================================
rmdir /s /q .git

echo ===================================================
echo  PASO 3: Iniciar repositorio git limpio
echo ===================================================
git init
git config user.email "75220834@continental.edu.pe"
git config user.name "ItaloRey"

echo ===================================================
echo  PASO 4: Configurar remote origin
echo ===================================================
git remote add origin https://github.com/75220834-cloud/PROYECTO-NATURACOR.git

echo ===================================================
echo  PASO 5: Agregar todos los archivos (sin secretos)
echo ===================================================
git add .

echo ===================================================
echo  PASO 6: Commit inicial limpio
echo ===================================================
git commit -m "feat: NATURACOR - Sistema Web Empresarial para Tiendas Naturistas

- Modulo POS (Punto de Venta) con IGV incluido
- Gestion de inventario, clientes y caja
- Recetario de enfermedades y productos naturales
- Asistente IA (Groq + Gemini + modo offline)
- Modulo de Reclamos con flujo de estados
- Gestion de Cordiales
- Sistema de Fidelizacion por acumulado
- Multi-sucursal con roles (admin/empleado)
- 125+ casos de prueba automatizados
- README completo con guia de instalacion"

echo ===================================================
echo  PASO 7: Push a GitHub
echo ===================================================
git branch -M main
git push -u origin main

echo ===================================================
echo  LISTO - Verifica en: https://github.com/75220834-cloud/PROYECTO-NATURACOR
echo ===================================================
pause
