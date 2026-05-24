SERVER CONTEXT:
- Server managed by RunCloud on Vultr
- Web apps live at: /home/runcloud/webapps/[app-name]/
- No WordPress, no build tools, no preprocessors
- Pure HTML, CSS, JS projects

PROJECT CONTEXT:
- These are often inherited/legacy projects being redone
- Start every session by auditing the existing structure before making changes
- Do not assume any standard folder organization

WORKFLOW:
1. First, map out what exists (files, folders, structure)
2. Identify what's redundant, broken, or disorganized
3. Propose a clean structure before moving anything
4. Before enforcing the no-inline-styles rule on files inside
   subdirectories (e.g. /clients/*, /tools/*), ask whether the
   subdir should follow site-wide preferences or use its own
   conventions (inline styles allowed, looser CSP, etc.)
5. Reorganize, then begin work

PREFERENCES (apply to root-level site by default):
- Clean folder structure: /css /js /img /fonts
- No inline styles
- External CSS and JS files only
