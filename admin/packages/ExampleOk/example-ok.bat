echo Running example.bat with args %1 %2
echo "This was a pampero example! DELETE ME" > %ALLUSERSPROFILE%\deleteme.txt
ping 1.1.1.1 -n 1 -w 3000 > nul
exit /B 0