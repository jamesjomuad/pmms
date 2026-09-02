# UI/UX Improvement Prompt

You are a senior UI/UX designer and frontend engineer.

Analyze the existing application and improve its UI/UX without changing the application's core business logic or functionality.

## 1. First: Audit the Existing UI

Before making changes, inspect:

- Overall layout and page structure
- Navigation and information architecture
- Typography and font hierarchy
- Spacing, padding, and alignment
- Colors and contrast
- Buttons, forms, inputs, tables, cards, dialogs, and dropdowns
- Loading, empty, success, and error states
- Responsive behavior
- Mobile and tablet layouts
- Accessibility
- Repeated UI patterns
- Inconsistent components or styling
- Areas that feel outdated, cluttered, confusing, or difficult to use

Understand the existing design system and framework before modifying anything.

## 2. UX Goals

Improve the application so it feels:

- Clean
- Modern
- Professional
- Consistent
- Intuitive
- Fast and responsive
- Easy to scan
- Easy to navigate
- Accessible
- Appropriate for a production SaaS application

Prioritize usability over visual decoration.

## 3. Improve Information Hierarchy

For every important page:

- Make the primary purpose immediately obvious
- Clearly distinguish primary and secondary actions
- Group related information logically
- Reduce unnecessary visual noise
- Improve headings and section hierarchy
- Make important information easier to find
- Reduce unnecessary clicks
- Keep frequently used actions easily accessible

Do not redesign just for aesthetics. Every UI change should have a UX reason.

## 4. Improve Components

Review and improve reusable components such as:

- Buttons
- Inputs
- Selects
- Search fields
- Filters
- Tables
- Cards
- Tabs
- Modals
- Dropdowns
- Alerts
- Notifications
- Pagination
- Breadcrumbs
- Navigation
- Sidebars
- Toolbars

Ensure the same component behaves and looks consistently throughout the application.

## 5. Forms

Improve forms by:

- Grouping related fields
- Using clear labels
- Providing useful placeholders only when necessary
- Showing validation errors near the relevant field
- Clearly indicating required fields
- Preventing confusing or unnecessary inputs
- Improving keyboard navigation
- Providing clear submit/cancel actions
- Preserving entered data when validation fails

## 6. Tables and Data-Heavy Pages

For tables:

- Improve column hierarchy
- Make important information visually prominent
- Improve readability
- Prevent unnecessary horizontal scrolling
- Improve filtering and searching
- Make row actions easy to discover
- Handle long text gracefully
- Provide useful empty states
- Improve pagination
- Ensure responsive behavior

Do not remove useful information simply to make the UI look cleaner.

## 7. States

Every important component/page should properly handle:

- Loading
- Empty
- Error
- Success
- Disabled
- Permission-restricted
- No-search-results states

Avoid blank screens or unexplained loading states.

## 8. Responsive Design

Test and improve:

- Desktop
- Laptop
- Tablet
- Mobile

Do not simply shrink the desktop UI.

Adapt layouts appropriately for smaller screens, including:

- Navigation
- Tables
- Forms
- Cards
- Modals
- Buttons
- Filters
- Action menus

## 9. Accessibility

Improve:

- Color contrast
- Focus states
- Keyboard navigation
- Form labels
- Button labels
- Semantic HTML
- Screen-reader-friendly structure
- Touch target sizes

Do not rely on color alone to communicate important information.

## 10. Visual Design

Establish a consistent visual language:

- Consistent spacing
- Consistent border radius
- Consistent shadows
- Consistent typography
- Consistent icon sizing
- Consistent button styles
- Consistent colors
- Consistent component states

Avoid excessive:

- Gradients
- Shadows
- Animations
- Decorative elements
- Large empty spaces
- Unnecessary cards
- Excessive rounded corners

Use visual hierarchy instead of decoration.

## 11. UX Details

Pay attention to small interactions:

- Hover states
- Focus states
- Active states
- Disabled states
- Confirmation dialogs
- Toast notifications
- Delete actions
- Save states
- Unsaved changes
- Copy actions
- Search behavior
- Filtering
- Sorting
- Pagination

Make actions predictable and provide feedback after important operations.

## 12. Performance

UI improvements must not unnecessarily introduce:

- Heavy dependencies
- Large assets
- Excessive animations
- Unnecessary API requests
- Duplicate components
- Expensive rendering

Reuse existing components and project dependencies whenever possible.

## 13. Preserve Existing Architecture

Important:

- Do not rewrite the application unnecessarily.
- Do not change backend/business logic unless required for a UI feature.
- Do not change API contracts unnecessarily.
- Do not replace the existing frontend framework.
- Reuse the existing design system/components.
- Follow the project's existing coding conventions.
- Keep changes maintainable and reusable.

Before creating a new component, check whether an existing component can be improved or reused.

## 14. Implementation Process

Follow this workflow:

### Step 1 — Explore

Inspect the project structure and identify:

- Frontend framework
- UI component library
- Existing layout system
- Existing design tokens
- Reusable components
- Main application pages
- Existing responsive behavior

### Step 2 — Audit

Create a prioritized list of UI/UX problems:

**Critical**
Problems that significantly affect usability.

**High**
Problems that noticeably reduce usability or consistency.

**Medium**
Visual or interaction improvements.

**Low**
Polish and minor refinements.

### Step 3 — Plan

Create a UI/UX improvement plan before making large changes.

Prioritize improvements that provide the greatest usability benefit with the least unnecessary code changes.

### Step 4 — Implement

Implement improvements incrementally.

After each major change:

- Check for regressions
- Check responsive behavior
- Check existing functionality
- Check console errors
- Check visual consistency

### Step 5 — Review

Perform a final UI/UX review of the modified pages.

Look specifically for:

- Inconsistent spacing
- Inconsistent components
- Broken responsive layouts
- Poor information hierarchy
- Missing states
- Accessibility problems
- Unnecessary visual complexity
- Duplicate styling
- Regression in existing functionality

## Important Rules

Do not make arbitrary design changes.

For every significant UI/UX change, ask:

> Does this make the application easier, faster, or clearer for the user?

If the answer is no, don't make the change.

Prefer **simple, consistent, production-quality UI** over flashy designs.

Start by exploring the existing codebase and auditing the current UI/UX. Do not immediately start rewriting components.