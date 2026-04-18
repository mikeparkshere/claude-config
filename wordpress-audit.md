# WordPress Site Audit Mode
You are now in AUDIT MODE for the current webapp session.

This command layers on top of /wordpress-runcloud. The webapp root, WP-CLI access,
and CLAUDE.md project context from that session remain active. Do not re-run the
webapp selection step — you are already in the correct directory.

---

## CRITICAL — Audit Rules

These rules are non-negotiable for the entire session:

- **Read and document only** — do not modify, create, or delete any files without explicit instruction
- **Understand before touching** — the goal of this session is a complete picture of what exists
- **Flag everything** — surface issues, inconsistencies, and risks even if not asked
- **One phase at a time** — complete and confirm each phase before moving to the next
- **No assumptions** — if something is unclear, ask rather than guess

---

## Audit Phases

Work through these phases in order. After each phase, present findings and wait
for confirmation before proceeding to the next.

---

### Phase 1 — Environment Snapshot

Establish the baseline before looking at anything else.

```bash
# WordPress version
wp core version

# PHP binary + version as WP-CLI sees it (site-specific on Runcloud)
wp cli info | grep -i "php"

# Active theme
wp theme list --status=active

# Confirm child theme is in use
wp option get template
wp option get stylesheet

# Database table prefix (flags non-standard setups like wpx_ instead of wp_)
wp db prefix
```

Present findings as a simple snapshot table. Flag anything unexpected.

---

### Phase 2 — Plugin Inventory

```bash
# All active plugins with version
wp plugin list --status=active --format=table

# Any inactive plugins (clutter / risk)
wp plugin list --status=inactive --format=table

# Check for update-available plugins (security risk indicator)
wp plugin list --update=available --format=table
```

For each active plugin, note:
- **Purpose** — what it does on this site
- **MPD standard** — is it in the standard stack or a one-off?
- **Flag** — anything that should be removed, replaced, or updated

---

### Phase 3 — Custom Code Inventory

This is the most important phase. Find everything that is not in the core plugin.

```bash
# Scan functions.php
cat wp-content/themes/$(wp option get stylesheet)/functions.php

# Search for custom functions across all theme files
grep -rn "function " wp-content/themes/ --include="*.php" | grep -v "vendor"

# Check for a core plugin (look for site-specific functionality plugin)
wp plugin list --format=table | grep -i "functionality\|core\|custom\|site"

# List all custom / non-standard plugins
ls wp-content/plugins/

# WPCodeBox snippets
wp post list --post_type=wpcodebox --fields=ID,post_title,post_status --format=table

# Get content of each WPCodeBox snippet (run per ID from above)
# wp post get [ID] --field=post_content
```

Document every piece of custom code found with:
- **Location** — where it lives now
- **What it does** — plain English description
- **Migration target** — where it should live under the MPD standard
- **Priority** — must migrate / nice to migrate / can remove

---

### Phase 4 — ACF Inventory

```bash
# List all ACF field groups
wp post list --post_type=acf-field-group --fields=ID,post_title,post_status --format=table

# Check if acf-json directory exists and is populated
ls wp-content/plugins/$(wp plugin list --status=active --field=name | grep -iE "functionality|core" | head -1)/acf-json/ 2>/dev/null || echo "No acf-json found in core plugin"

# Check for acf-json in theme
ls wp-content/themes/$(wp option get stylesheet)/acf-json/ 2>/dev/null || echo "No acf-json in theme"

# Get field group details (run per ID from list above)
# wp post get [ID] --format=json
```

For each field group, note:
- **Registration method** — UI-only or programmatic?
- **acf-json sync** — enabled or not?
- **Location rules** — what post type or page it applies to
- **Migration needed** — yes / no / already done

---

### Phase 5 — Custom Post Types & Taxonomies

```bash
# All registered post types (built-in and custom)
wp post-type list --format=table

# All registered taxonomies
wp taxonomy list --format=table

# Where CPTs are registered (theme or plugin)
grep -rn "register_post_type" wp-content/ --include="*.php" -l
grep -rn "register_taxonomy" wp-content/ --include="*.php" -l
```

Flag any CPTs or taxonomies registered outside the core plugin.

---

### Phase 6 — CSS & Style Audit

Check all locations where custom styles may live:

```bash
# 1. ACSS custom CSS
cat wp-content/uploads/automatic-css/automatic-custom-css.css 2>/dev/null | head -50

# 2. Child theme stylesheet
cat wp-content/themes/$(wp option get stylesheet)/style.css

# 3. WPCodeBox CSS snippets (from Phase 3 inventory — filter for CSS type)
wp post list --post_type=wpcodebox --fields=ID,post_title --format=table

# 4. Bricks global classes (count only — full output can be large)
wp option get bricks_global_classes --format=json | python3 -c "import sys,json; data=json.load(sys.stdin); print(f'{len(data)} global classes registered')" 2>/dev/null
```

Note where custom CSS lives and whether it belongs there under the MPD standard.

---

### Phase 7 — Security & Code Quality Flags

Run through this checklist and flag any issues found:

```bash
# Check for debug mode enabled (should be off on production)
grep "WP_DEBUG" wp-config.php

# Check for hardcoded credentials or API keys in theme/plugin files
grep -rn "define(" wp-content/themes/ --include="*.php" | grep -i "key\|secret\|password\|token"

# Check file permissions on wp-config.php (should be 600 or 640)
stat -c "%a %n" wp-config.php

# Check for direct PHP execution in uploads (security risk)
find wp-content/uploads/ -name "*.php" 2>/dev/null

# Outdated WordPress core
wp core check-update
```

---

## Audit Report Format

After all phases are complete, produce a structured audit report in this format:

---

**SITE AUDIT REPORT**
Site: [webapp name]
Date: [today's date]
Audited by: Claude Code (MPD Audit Mode)

**ENVIRONMENT SNAPSHOT**
[Table: WP version, PHP version, theme, child theme active Y/N]

**PLUGIN INVENTORY**
[Table: Plugin | Purpose | Standard? | Action needed]

**CUSTOM CODE FINDINGS**
[Table: Location | What it does | Migration target | Priority]

**ACF STATUS**
[Table: Field group | Method | acf-json | Action needed]

**CPT & TAXONOMY STATUS**
[Table: Name | Registered in | Action needed]

**CSS & STYLES**
[Summary of where custom CSS lives and what needs consolidating]

**SECURITY FLAGS**
[List of any issues found — none if clean]

**RECOMMENDED NEXT STEPS**
[Ordered list: highest priority first]
- [ ] [Action item]
- [ ] [Action item]

**POST-AUDIT PATH RECOMMENDATION**
[ ] Refactor in place on staging
[ ] Rebuild as new build on LocalWP
[ ] Reason: [explanation]

---

## After the Report

Once the report is presented:
1. Save it — paste into the Claude.ai Project for this client as a persistent record
2. Decide the post-audit path with the client context in mind
3. If refactoring in place — return to this session and begin Phase 1 of the migration workflow
4. If rebuilding — start a new build session on LocalWP using the audit report as the brief

Do not begin any migration work in the same session as the audit without explicit instruction.

