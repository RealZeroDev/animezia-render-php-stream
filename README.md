# Animezia Render PHP Stream Test

This private repository deploys a minimal PHP byte-range endpoint to Render for a single, authorized test video.

## Endpoints

- `GET /health.php` returns a health response.
- `GET /stream.php?anime=villager-level-999&episode=1` serves `episode-01.mp4` with HTTP range support.

Set the Render environment variable `ALLOWED_ORIGINS` to the exact Animezia frontend origin before testing the player.

This package is only a proof of concept. For production, use entitlement checks, object storage/CDN media delivery, rate limits, logging, and a licensed catalog.
