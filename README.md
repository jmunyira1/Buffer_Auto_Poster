---
title: 'Buffer_Auto-Poster'
summary: 'An automated tool that fetches high-quality portrait wallpapers and schedules them as social media posts on Buffer.'
description: 'Managing a consistent social media posting schedule is time-consuming, especially when sourcing and resizing images manually. This tool automates the entire pipeline — it pulls random portrait wallpapers from Wallhaven, resizes them to meet platform requirements, and schedules them directly to your connected social accounts via Buffer. It runs on a cron job so your content queue stays full with zero manual effort.'
tech:
  - 'PHP 8.4'
  - 'cURL'
  - 'GD Library (PHP image processing)'
  - 'Buffer GraphQL API'
  - 'Wallhaven API'
  - 'Cron Jobs (cPanel)'
  - 'dotenv (custom envReader)'
  - 'GraphQL'
  - 'HTML & CSS'
live_url: 'https://your-live-url.com'
github_url: 'https://github.com/username/repo'
featured: false
active: true
---

Wallhaven Buffer Auto-Poster

A fully automated social media content pipeline that sources stunning portrait wallpapers from Wallhaven and schedules them to your Buffer queue — no manual work required. Built for creators and page managers who want a consistent posting schedule without spending time hunting for images.

Runs entirely on shared cPanel hosting via a cron job, making it accessible without any cloud infrastructure or server management.

✨ Key Features

Smart Portrait Filtering
Only 9x16 aspect ratio images are fetched, making every post perfectly sized for TikTok, Instagram Stories, and other portrait-first platforms. Anime content is excluded automatically, keeping the feed clean and general-audience friendly.

Seed-Based Duplicate Prevention
The tool remembers where it left off between runs using a seed system. Each batch of images comes from a different section of Wallhaven's library, so you will never see the same wallpaper posted twice until the entire pool has been cycled through — at which point it resets and starts fresh.

Automatic Image Resizing
Wallhaven images are often too large for platforms like TikTok which enforce strict pixel limits. The tool automatically downloads each image and resizes it to fit within the allowed dimensions while preserving quality and aspect ratio, so posts are never rejected for being oversized.

Temporary Public Hosting
Resized images are temporarily saved to a public URL on your own domain so Buffer can fetch them during scheduling. Once Buffer confirms the post has been queued, the images are automatically deleted from your server — keeping your storage clean.

Buffer GraphQL Integration
Posts are scheduled directly through Buffer's modern GraphQL API with full support for custom scheduling times, multi-image posts, and captions with hashtags. Each run schedules the next post two hours ahead, keeping your queue consistently topped up.

Multi-Image Posts
Each scheduled Buffer post includes a carousel of up to six unique wallpapers fetched in a single API call. This maximises engagement per post while minimising the number of API requests made to both Wallhaven and Buffer.

SFW Content Enforcement
The Wallhaven API is called with strict safe-for-work filters applied at the source. Only clean, family-friendly content makes it into your social media queue, with no need to manually review images before they are posted.

Environment-Based Configuration
All sensitive credentials — Buffer API token, Wallhaven API key, and channel IDs — are stored in a `.env` file and never hardcoded into the script. This makes it safe to share or version-control the codebase without exposing private keys.

Detailed Run Logging
Every action the script takes is written to a log file with a timestamp — from which seed was used, to what images were downloaded, to exactly what Buffer responded with. When something goes wrong, the log tells you precisely where and why.

Graceful Error Handling
If Buffer rejects a post for any reason, the temporary images are kept on disk rather than deleted, giving you a chance to inspect them. The error message from Buffer is captured and logged clearly so you can fix the issue without guesswork.

Cron Job Ready
The script is designed to run unattended on a schedule via cPanel's built-in cron job manager. No Node.js, no Docker, no external services — just a single PHP file and a cron entry on standard shared hosting.
