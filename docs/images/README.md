# Documentation Images

This directory contains screenshots and visual assets for project documentation.

## Required Screenshots

1. **gantt-timeline-view.png**
   - Full Gantt chart timeline view
   - Shows 2-month timeline with Excel-style columns
   - Weekend highlighting and today marker visible
   - Color-coded task bars displayed
   - Used in: README.md, docs/GANTT_FEATURES.md

2. **task-detail-card.png**
   - Task detail modal/card
   - Shows task information popup
   - Status badge, priority, dates visible
   - Action buttons displayed (Edit, Toggle, Delete)
   - Used in: README.md, docs/GANTT_FEATURES.md

## Verification

After adding images, verify they render correctly:

```bash
# Check if images exist
ls -lh docs/images/*.png

# View in browser (if markdown preview available)
# Or push to GitHub/GitLab to see rendered markdown
```

## Image Guidelines

- **Format**: PNG (for screenshots)
- **Naming**: kebab-case (lowercase with hyphens)
- **Size**: Keep under 1MB for fast loading
- **Quality**: Use clear, high-resolution screenshots
- **Content**: Capture relevant UI features prominently
