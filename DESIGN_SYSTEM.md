# Design System Principles

This project utilizes [Livewire Flux](https://fluxui.dev/) components and Tailwind CSS conventions to create a clean, modern, and "dense" user interface. Follow these principles for all future UI development.

## 1. Page Layout Structure

All admin pages should follow a consistent hierarchy:

1.  **Header Row**:
    *   **Left**: Breadcrumb/Context (Small uppercase label) + Page Title (H1) + Description (Subtext).
    *   **Right**: Primary Page Actions (e.g., "Add User" button) or Status Indicators.
2.  **Stats Overview (Optional but Recommended)**:
    *   A grid of cards summarizing key metrics relevant to the page context.
    *   **Grid**: `grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4`
3.  **Filter & Search Row**:
    *   **Left**: Filter buttons (Pills).
    *   **Right**: Search input.
4.  **Content Area**:
    *   **Lists/Grids**: Use specialized card rows or standard tables styled to match Flux.
    *   **Empty States**: comprehensive empty states with icons.

## 2. Component Patterns

### Stats Cards
Do NOT use `flux:card` for simple stats to avoid overhead/errors if not needed. Use standard styled divs.

```html
<div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Label</span>
    <span class="text-2xl font-bold text-gray-900 dark:text-white">Value</span>
</div>
```

**Color Variants:**
*   **Default/Neutral**: Gray border/text.
*   **Pending/Warning**: Amber border (`border-amber-200`), bg (`bg-amber-50/50`), text (`text-amber-700`).
*   **Success/Active**: Emerald border (`border-emerald-200`), bg (`bg-emerald-50/50`), text (`text-emerald-700`).
*   **Danger/Error**: Rose border (`border-rose-200`), bg (`bg-rose-50/50`), text (`text-rose-700`).

### Buttons & Actions
Use `flux:button` for all interactive elements.

*   **Primary Actions**: `variant="primary"` (e.g., "Save", "Add User").
*   **Secondary/Neutral**: `variant="ghost"` or default (e.g., "Cancel", "View").
*   **Destructive**: `variant="danger"` (e.g., "Reject", "Delete").
*   **Filters**: Use `variant="filled"` for active states and `variant="subtle"` for inactive.

### Badges & Status
Use styled `span` elements or `flux:badge` (if available/stable) for status indicators.
*   **Pattern**: `inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset`
*   **Icons**: Always include a small icon (`flux:icon` or styled `div`) to the left of the text.

### Icons
Use `flux:icon` where possible for consistency.
*   **Sizes**: `size-4` (16px) for small buttons/text, `size-5` (20px) for standard buttons.

## 3. Visual "Density"
Avoid "empty" white space.
*   Use **Borders** to define sections (`border border-gray-200 dark:border-gray-700`).
*   Use **Shadows** sparingly (`shadow-sm`).
*   **Backgrounds**: Main content cards should be White (`bg-white`) / Dark Zinc (`dark:bg-zinc-900`).
*   **Page Background**: Gray 50 (`bg-gray-50`) / Slate 950 (`dark:bg-slate-950`).

## 4. Code Example (List Item)

```blade
<div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-4">
        <!-- Content -->
    </div>
    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
         <!-- Footer / Actions -->
    </div>
</div>
```
