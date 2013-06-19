@echo off
set THIS_DIR=%CD%
cd ..\..\js
cscript packages.js %*
cd %THIS_DIR%