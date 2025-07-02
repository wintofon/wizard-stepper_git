@echo off
setlocal

echo 📁 Creando carpetas...
mkdir wizard 2>nul
mkdir admin 2>nul
mkdir admin\materials 2>nul
mkdir admin\tools 2>nul
mkdir admin\strategies 2>nul
mkdir admin\categories 2>nul
mkdir assets\css 2>nul
mkdir assets\js 2>nul
mkdir assets\images 2>nul
mkdir includes 2>nul
mkdir uploads 2>nul

echo.
echo 📄 Creando archivos...
for %%F in (
  "index.php"
  "wizard\step1_experience.php"
  "wizard\step2_router.php"
  "wizard\step3_material.php"
  "wizard\step4_tool.php"
  "wizard\step5_strategy.php"
  "wizard\step6_results.php"
  "admin\index.php"
  "admin\dashboard.php"
  "admin\tools.php"
  "admin\materials.php"
  "admin\strategies.php"
  "admin\categories.php"
  "admin\tool_form.php"
  "admin\material_form.php"
  "admin\strategy_form.php"
  "admin\category_form.php"
  "assets\css\bootstrap.min.css"
  "assets\css\styles.css"
  "assets\js\bootstrap.bundle.min.js"
  "assets\js\wizard.js"
  "assets\js\admin.js"
  "includes\db.php"
  "includes\auth.php"
  "includes\functions.php"
  "generate_pdf.php"
  ".htaccess"
) do (
  if not exist %%~F (
    type nul > %%~F
    echo   ✓ %%~F
  ) else (
    echo   ○ ya existe: %%~F
  )
)

echo.
echo ✅ Scaffold completado. ¡Ya puedes comenzar a editar!
pause
