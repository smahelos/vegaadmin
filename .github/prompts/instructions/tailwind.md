---
mode: 'agent'
description: 'Tailwind CSS 4.1.3 styling instructions and conventions'
---

# Tailwind CSS 4.1.3 Styling Instructions

## Version
- Always use Tailwind CSS 4.1.3 features and syntax
- Follow Tailwind CSS 4.1.3 conventions and best practices
- Use Tailwind CSS 4.1.3 specific utilities and components

## Utility Classes
- Use utility-first approach
- Prefer Tailwind utilities over custom CSS
- Use responsive prefixes for different screen sizes
- Utilize Tailwind's color palette and spacing scale

## Configuration
- Tailwind config is in `tailwind.config.js`
- Custom utilities and components in `resources/css/`
- Build process managed by Vite in `vite.config.js`

## Best Practices
- Use semantic class names when grouping utilities
- Leverage Tailwind's component layer for reusable styles
- Use @apply directive sparingly, prefer utility classes
- Follow mobile-first responsive design approach

## Integration with Backpack
- Override Backpack styles using Tailwind utilities
- Maintain Backpack's admin panel consistency
- Use Tailwind for custom frontend components
- Ensure accessibility with Tailwind utilities

## File Organization
- Main CSS file: `resources/css/app.css`
- Component styles in dedicated files
- Use PostCSS for processing (configured in `postcss.config.js`)
