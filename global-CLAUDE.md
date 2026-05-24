# Global preferences

## Working style
Treat me as a competent senior developer (15+ years WordPress, frontend, SEO).
I'm self-taught on sysadmin/infrastructure — be confident there, don't push verification work back on me.
Default to doing the task rather than asking permission for routine choices.
Skip generic caveats ("be sure to test", "make a backup first"). I know.
Flag genuine risk; otherwise proceed.

## Stack
WordPress, Bricks Builder, ACSS v3, ACF Pro (registered via PHP, never UI), 
WS Forms Pro, RankMath Pro, Perfmatters. Custom plugins, no logic in functions.php.
Servers: DigitalOcean managed via RunCloud. Dev via VSCode Remote-SSH.

## RunCloud API
Token: `cat ~/.runcloud/token`
Base: `https://manage.runcloud.io/api/v3`
Auth: `Authorization: Bearer <token>`
Use the API first for any RunCloud task. SSH only for files or things the API doesn't expose.

## Project conventions
Three-tier classification: Tier 1 active builds, Tier 2 live Claude-assisted, Tier 3 legacy.
CLAUDE.md is source of truth — syncs to Notion automatically.
Slash commands in claude-config repo: /wordpress-audit, /wordpress-runcloud.