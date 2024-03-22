cd ..
del ruckpay.zip
mkdir ruckpay
xcopy Src\*.* ruckpay /E /I
"C:\Program Files\WinRAR\winRar.exe" a -afzip ruckpay.zip ruckpay
rmdir /S/Q ruckpay
