@echo off
title Laravel Ad Moderator Server
echo ====================================================
echo Starting Laravel Advertisement System Server...
echo ====================================================
echo Moderator Dashboard: http://localhost:8000
echo Unity Submit API:    http://localhost:8000/api/ads/submit
echo Unity Live Ads API:  http://localhost:8000/api/ads
echo ====================================================
php artisan serve --port=8000
pause
