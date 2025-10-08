<style>
    /* Import Inter Font */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    /* shadcn/ui CSS Variables - Light Theme */
    :root {
        --background: 0 0% 100%;
        --foreground: 222.2 84% 4.9%;
        --card: 0 0% 100%;
        --card-foreground: 222.2 84% 4.9%;
        --popover: 0 0% 100%;
        --popover-foreground: 222.2 84% 4.9%;
        --primary: 222.2 47.4% 11.2%;
        --primary-foreground: 210 40% 98%;
        --secondary: 210 40% 96%;
        --secondary-foreground: 222.2 84% 4.9%;
        --muted: 210 40% 96%;
        --muted-foreground: 215.4 16.3% 46.9%;
        --accent: 210 40% 96%;
        --accent-foreground: 222.2 84% 4.9%;
        --destructive: 0 84.2% 60.2%;
        --destructive-foreground: 210 40% 98%;
        --border: 214.3 31.8% 91.4%;
        --input: 214.3 31.8% 91.4%;
        --ring: 222.2 84% 4.9%;
        --radius: 0.5rem;
    }

    /* shadcn/ui Dark Theme */
    .dark {
        --background: 222.2 84% 4.9%;
        --foreground: 210 40% 98%;
        --card: 222.2 84% 4.9%;
        --card-foreground: 210 40% 98%;
        --popover: 222.2 84% 4.9%;
        --popover-foreground: 210 40% 98%;
        --primary: 210 40% 98%;
        --primary-foreground: 222.2 84% 4.9%;
        --secondary: 217.2 32.6% 17.5%;
        --secondary-foreground: 210 40% 98%;
        --muted: 217.2 32.6% 17.5%;
        --muted-foreground: 215 20.2% 65.1%;
        --accent: 217.2 32.6% 17.5%;
        --accent-foreground: 210 40% 98%;
        --destructive: 0 62.8% 30.6%;
        --destructive-foreground: 210 40% 98%;
        --border: 217.2 32.6% 17.5%;
        --input: 217.2 32.6% 17.5%;
        --ring: 212.7 26.8% 83.9%;
    }

    /* Base Overrides */
    * {
        border-color: hsl(var(--border)) !important;
    }

    /* Font Family Override - Excluding Icons */
    body,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    div,
    a,
    button,
    input,
    select,
    textarea,
    label,
    .btn,
    .input,
    .select,
    .textarea,
    .badge,
    .card,
    .alert,
    .menu,
    [class*="text-"],
    [class*="font-"] {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    }

    /* Preserve Icon Fonts */
    [class*="fa-"],
    [class^="fa-"],
    .fas,
    .far,
    .fal,
    .fab,
    .fad,
    .fa,
    i[class*="fa"],
    .icon,
    [class*="icon-"],
    [class^="icon-"],
    .material-icons,
    .material-icons-outlined,
    .material-icons-round,
    .material-symbols-outlined {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 5 Free", "Font Awesome 5 Pro", "FontAwesome", "Material Icons", "Material Symbols Outlined" !important;
        font-weight: 900 !important;
        /* Ensures solid Font Awesome icons display */
    }

    html {
        color-scheme: light;
    }

    html.dark {
        color-scheme: dark;
    }

    body {
        background-color: hsl(var(--background)) !important;
        color: hsl(var(--foreground)) !important;
        font-feature-settings: "rlig" 1, "calt" 1 !important;
    }

    /* DaisyUI Button Overrides */
    .btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
        border-radius: calc(var(--radius) - 2px) !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        transition: all 0.2s !important;
        outline: none !important;
        border: 1px solid transparent !important;
        cursor: pointer !important;
        height: 2.5rem !important;
        padding: 0.5rem 1rem !important;
        text-transform: none !important;
        letter-spacing: normal !important;
        box-shadow: none !important;
    }

    .btn:focus-visible {
        outline: 2px solid hsl(var(--ring)) !important;
        outline-offset: 2px !important;
    }

    .btn-primary {
        background-color: hsl(var(--primary)) !important;
        color: hsl(var(--primary-foreground)) !important;
        border-color: hsl(var(--primary)) !important;
    }

    .btn-primary:hover {
        background-color: hsl(var(--primary) / 0.9) !important;
        border-color: hsl(var(--primary) / 0.9) !important;
    }

    .btn-secondary {
        background-color: hsl(var(--secondary)) !important;
        color: hsl(var(--secondary-foreground)) !important;
        border-color: hsl(var(--border)) !important;
    }

    .btn-secondary:hover {
        background-color: hsl(var(--secondary) / 0.8) !important;
    }

    .btn-ghost {
        background-color: transparent !important;
        color: hsl(var(--foreground)) !important;
        border-color: transparent !important;
    }

    .btn-ghost:hover {
        background-color: hsl(var(--accent)) !important;
        color: hsl(var(--accent-foreground)) !important;
    }

    .btn-outline {
        background-color: transparent !important;
        color: hsl(var(--foreground)) !important;
        border-color: hsl(var(--input)) !important;
    }

    .btn-outline:hover {
        background-color: hsl(var(--accent)) !important;
        color: hsl(var(--accent-foreground)) !important;
    }

    .btn-error {
        background-color: hsl(var(--destructive)) !important;
        color: hsl(var(--destructive-foreground)) !important;
        border-color: hsl(var(--destructive)) !important;
    }

    .btn-error:hover {
        background-color: hsl(var(--destructive) / 0.9) !important;
    }

    .btn-sm {
        height: 2rem !important;
        padding: 0.25rem 0.75rem !important;
        font-size: 0.75rem !important;
    }

    .btn-lg {
        height: 2.75rem !important;
        padding: 0.5rem 2rem !important;
        font-size: 1rem !important;
    }

    .btn-circle {
        width: 2.5rem !important;
        height: 2.5rem !important;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    /* DaisyUI Input Overrides */
    .input {
        display: flex !important;
        height: 2.5rem !important;
        width: 100% !important;
        border-radius: calc(var(--radius) - 2px) !important;
        border: 1px solid hsl(var(--input)) !important;
        background-color: hsl(var(--background)) !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem !important;
        transition: all 0.2s !important;
        outline: none !important;
        color: hsl(var(--foreground)) !important;
        box-shadow: none !important;
    }

    .input:focus {
        outline: none !important;
        ring: 2px solid hsl(var(--ring)) !important;
        border-color: hsl(var(--ring)) !important;
        box-shadow: 0 0 0 2px hsl(var(--ring)) !important;
    }

    .input::placeholder {
        color: hsl(var(--muted-foreground)) !important;
    }

    .input-bordered {
        border: 1px solid hsl(var(--input)) !important;
    }

    .input-sm {
        height: 2rem !important;
        padding: 0.25rem 0.75rem !important;
        font-size: 0.75rem !important;
    }

    .input-lg {
        height: 3rem !important;
        padding: 0.75rem 1rem !important;
        font-size: 1rem !important;
    }

    /* DaisyUI Select Overrides */
    .select {
        display: flex !important;
        height: 2.5rem !important;
        width: 100% !important;
        border-radius: calc(var(--radius) - 2px) !important;
        border: 1px solid hsl(var(--input)) !important;
        background-color: hsl(var(--background)) !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem !important;
        transition: all 0.2s !important;
        outline: none !important;
        color: hsl(var(--foreground)) !important;
        background-image: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAxMiAxMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTMgNUw2IDhMOSA1IiBzdHJva2U9IjY5NzU3MiIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4K") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 1rem !important;
        appearance: none !important;
        box-shadow: none !important;
    }

    .select:focus {
        outline: none !important;
        ring: 2px solid hsl(var(--ring)) !important;
        border-color: hsl(var(--ring)) !important;
        box-shadow: 0 0 0 2px hsl(var(--ring)) !important;
    }

    /* DaisyUI Textarea Overrides */
    .textarea {
        display: flex !important;
        min-height: 5rem !important;
        width: 100% !important;
        border-radius: calc(var(--radius) - 2px) !important;
        border: 1px solid hsl(var(--input)) !important;
        background-color: hsl(var(--background)) !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem !important;
        transition: all 0.2s !important;
        outline: none !important;
        color: hsl(var(--foreground)) !important;
        resize: vertical !important;
        box-shadow: none !important;
    }

    .textarea:focus {
        outline: none !important;
        ring: 2px solid hsl(var(--ring)) !important;
        border-color: hsl(var(--ring)) !important;
        box-shadow: 0 0 0 2px hsl(var(--ring)) !important;
    }

    /* DaisyUI Badge Overrides */
    .badge {
        display: inline-flex !important;
        align-items: center !important;
        border-radius: 9999px !important;
        padding: 0.125rem 0.625rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        border: 1px solid transparent !important;
        transition: all 0.2s !important;
    }

    .badge-default {
        background-color: hsl(var(--secondary)) !important;
        color: hsl(var(--secondary-foreground)) !important;
    }

    .badge-primary {
        background-color: hsl(var(--primary)) !important;
        color: hsl(var(--primary-foreground)) !important;
    }

    .badge-secondary {
        background-color: hsl(var(--secondary)) !important;
        color: hsl(var(--secondary-foreground)) !important;
    }

    .badge-error {
        background-color: hsl(var(--destructive)) !important;
        color: hsl(var(--destructive-foreground)) !important;
    }

    .badge-outline {
        color: hsl(var(--foreground)) !important;
        border-color: hsl(var(--border)) !important;
        background-color: transparent !important;
    }

    .badge-sm {
        padding: 0.0625rem 0.375rem !important;
        font-size: 0.625rem !important;
    }

    .badge-lg {
        padding: 0.25rem 0.875rem !important;
        font-size: 0.875rem !important;
    }

    /* DaisyUI Card Overrides */
    .card {
        background-color: hsl(var(--card)) !important;
        color: hsl(var(--card-foreground)) !important;
        border: 1px solid hsl(var(--border)) !important;
        border-radius: calc(var(--radius) - 2px) !important;
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1) !important;
    }

    .card-body {
        padding: 1.5rem !important;
    }

    .card-title {
        font-size: 1.125rem !important;
        font-weight: 600 !important;
        color: hsl(var(--card-foreground)) !important;
    }

    .card-actions {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
    }

    /* DaisyUI Alert Overrides */
    .alert {
        position: relative !important;
        width: 100% !important;
        border-radius: calc(var(--radius) - 2px) !important;
        border: 1px solid hsl(var(--border)) !important;
        padding: 1rem !important;
        display: flex !important;
        align-items: flex-start !important;
        gap: 0.75rem !important;
    }

    .alert-info {
        border-color: hsl(var(--border)) !important;
        background-color: hsl(var(--background)) !important;
        color: hsl(var(--foreground)) !important;
    }

    .alert-success {
        border-color: hsl(142.1 76.2% 36.3%) !important;
        background-color: hsl(142.1 76.2% 36.3% / 0.1) !important;
        color: hsl(142.1 76.2% 36.3%) !important;
    }

    .alert-warning {
        border-color: hsl(32.5 94.6% 43.7%) !important;
        background-color: hsl(32.5 94.6% 43.7% / 0.1) !important;
        color: hsl(32.5 94.6% 43.7%) !important;
    }

    .alert-error {
        border-color: hsl(var(--destructive)) !important;
        background-color: hsl(var(--destructive) / 0.1) !important;
        color: hsl(var(--destructive)) !important;
    }

    /* DaisyUI Modal Overrides */
    .modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 50 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        background-color: hsl(var(--background) / 0.8) !important;
        backdrop-filter: blur(8px) !important;
    }

    .modal.modal-open {
        display: flex !important;
    }

    .modal-box {
        background-color: hsl(var(--card)) !important;
        color: hsl(var(--card-foreground)) !important;
        border: 1px solid hsl(var(--border)) !important;
        border-radius: calc(var(--radius) - 2px) !important;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1) !important;
        padding: 1.5rem !important;
        margin: 1rem !important;
        max-width: 32rem !important;
        width: 100% !important;
    }

    .modal-action {
        display: flex !important;
        justify-content: flex-end !important;
        gap: 0.5rem !important;
        margin-top: 1.5rem !important;
    }

    /* DaisyUI Menu Overrides */
    .menu {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.25rem !important;
        padding: 0.5rem !important;
    }

    .menu li>* {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 0.5rem 0.75rem !important;
        border-radius: calc(var(--radius) - 2px) !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        transition: all 0.15s ease-in-out !important;
        text-decoration: none !important;
        color: hsl(var(--foreground)) !important;
    }

    .menu li>*:hover {
        background-color: hsl(var(--accent)) !important;
        color: hsl(var(--accent-foreground)) !important;
    }

    .menu li>*.active {
        background-color: hsl(var(--primary)) !important;
        color: hsl(var(--primary-foreground)) !important;
    }

    /* DaisyUI Dropdown Overrides */
    .dropdown-content {
        background-color: hsl(var(--popover)) !important;
        border: 1px solid hsl(var(--border)) !important;
        border-radius: calc(var(--radius) - 2px) !important;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1) !important;
        padding: 0.25rem !important;
        color: hsl(var(--popover-foreground)) !important;
    }

    /* DaisyUI Navbar Overrides */
    .navbar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        padding: 0.5rem 1rem !important;
        background-color: hsl(var(--background)) !important;
        border-bottom: 1px solid hsl(var(--border)) !important;
    }

    /* DaisyUI Breadcrumbs Overrides */
    .breadcrumbs ul {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }

    .breadcrumbs ul li {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-size: 0.875rem !important;
        color: hsl(var(--muted-foreground)) !important;
    }

    .breadcrumbs ul li a {
        color: hsl(var(--primary)) !important;
        text-decoration: none !important;
        transition: color 0.15s ease-in-out !important;
    }

    .breadcrumbs ul li a:hover {
        color: hsl(var(--primary) / 0.8) !important;
    }

    /* DaisyUI Table Overrides */
    .table {
        width: 100% !important;
        caption-side: bottom !important;
        font-size: 0.875rem !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
    }

    .table th {
        height: 3rem !important;
        padding: 0.75rem !important;
        text-align: left !important;
        font-weight: 500 !important;
        color: hsl(var(--muted-foreground)) !important;
        border-bottom: 1px solid hsl(var(--border)) !important;
    }

    .table td {
        padding: 0.75rem !important;
        border-bottom: 1px solid hsl(var(--border)) !important;
        color: hsl(var(--foreground)) !important;
    }

    .table tr:hover {
        background-color: hsl(var(--muted) / 0.5) !important;
    }

    /* DaisyUI Progress Overrides */
    .progress {
        appearance: none !important;
        width: 100% !important;
        height: 0.5rem !important;
        border-radius: 9999px !important;
        background-color: hsl(var(--secondary)) !important;
        overflow: hidden !important;
    }

    .progress::-webkit-progress-bar {
        background-color: hsl(var(--secondary)) !important;
        border-radius: 9999px !important;
    }

    .progress::-webkit-progress-value {
        background-color: hsl(var(--primary)) !important;
        border-radius: 9999px !important;
        transition: all 0.3s ease !important;
    }

    .progress-primary::-webkit-progress-value {
        background-color: hsl(var(--primary)) !important;
    }

    .progress-secondary::-webkit-progress-value {
        background-color: hsl(var(--secondary)) !important;
    }

    .progress-error::-webkit-progress-value {
        background-color: hsl(var(--destructive)) !important;
    }

    /* DaisyUI Loading Overrides */
    .loading {
        width: 1.25rem !important;
        height: 1.25rem !important;
        border: 2px solid hsl(var(--primary)) !important;
        border-bottom-color: transparent !important;
        border-radius: 50% !important;
        animation: spin 1s linear infinite !important;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg) !important;
        }
    }

    /* DaisyUI Toggle/Checkbox Overrides */
    .toggle,
    .checkbox {
        appearance: none !important;
        height: 1.25rem !important;
        width: 1.25rem !important;
        border: 1px solid hsl(var(--input)) !important;
        border-radius: calc(var(--radius) - 4px) !important;
        background-color: hsl(var(--background)) !important;
        cursor: pointer !important;
        position: relative !important;
        transition: all 0.2s !important;
    }

    .toggle:checked,
    .checkbox:checked {
        background-color: hsl(var(--primary)) !important;
        border-color: hsl(var(--primary)) !important;
    }

    .checkbox:checked::after {
        content: "✓" !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        color: hsl(var(--primary-foreground)) !important;
        font-size: 0.75rem !important;
        font-weight: bold !important;
    }

    /* DaisyUI Radio Overrides */
    .radio {
        appearance: none !important;
        height: 1.25rem !important;
        width: 1.25rem !important;
        border: 1px solid hsl(var(--input)) !important;
        border-radius: 50% !important;
        background-color: hsl(var(--background)) !important;
        cursor: pointer !important;
        position: relative !important;
        transition: all 0.2s !important;
    }

    .radio:checked {
        background-color: hsl(var(--primary)) !important;
        border-color: hsl(var(--primary)) !important;
    }

    .radio:checked::after {
        content: "" !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        width: 0.375rem !important;
        height: 0.375rem !important;
        border-radius: 50% !important;
        background-color: hsl(var(--primary-foreground)) !important;
    }

    /* DaisyUI Theme Colors Override */
    [data-theme="light"],
    [data-theme="corporate"],
    :root {
        color-scheme: light !important;
    }

    [data-theme="dark"] {
        color-scheme: dark !important;
    }

    /* Utility Classes */
    .text-muted-foreground {
        color: hsl(var(--muted-foreground)) !important;
    }

    .text-primary {
        color: hsl(var(--primary)) !important;
    }

    .text-destructive {
        color: hsl(var(--destructive)) !important;
    }

    .bg-background {
        background-color: hsl(var(--background)) !important;
    }

    .bg-card {
        background-color: hsl(var(--card)) !important;
    }

    .bg-primary {
        background-color: hsl(var(--primary)) !important;
    }

    .bg-secondary {
        background-color: hsl(var(--secondary)) !important;
    }

    .bg-muted {
        background-color: hsl(var(--muted)) !important;
    }

    .bg-accent {
        background-color: hsl(var(--accent)) !important;
    }

    .bg-destructive {
        background-color: hsl(var(--destructive)) !important;
    }

    .border-border {
        border-color: hsl(var(--border)) !important;
    }

    .border-input {
        border-color: hsl(var(--input)) !important;
    }

    .border-primary {
        border-color: hsl(var(--primary)) !important;
    }

    .border-destructive {
        border-color: hsl(var(--destructive)) !important;
    }

    /* Focus Visible Enhancement */
    *:focus-visible {
        outline: 2px solid hsl(var(--ring)) !important;
        outline-offset: 2px !important;
    }

    /* Scrollbar Styling */
    * {
        scrollbar-width: thin !important;
        scrollbar-color: hsl(var(--border)) transparent !important;
    }

    *::-webkit-scrollbar {
        width: 6px !important;
        height: 6px !important;
    }

    *::-webkit-scrollbar-track {
        background: transparent !important;
    }

    *::-webkit-scrollbar-thumb {
        background-color: hsl(var(--border)) !important;
        border-radius: 3px !important;
    }

    *::-webkit-scrollbar-thumb:hover {
        background-color: hsl(var(--muted-foreground)) !important;
    }

    /* Animation Enhancements */
    @keyframes fadeIn {
        from {
            opacity: 0 !important;
            transform: translateY(-10px) !important;
        }

        to {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    }

    .animate-in {
        animation: fadeIn 0.2s ease-out !important;
    }

    /* High Contrast Mode Support */
    @media (prefers-contrast: high) {
        * {
            border-width: 2px !important;
        }
    }

    /* Reduced Motion Support */
    @media (prefers-reduced-motion: reduce) {

        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }
</style><!-- shadcn/ui Styling -->
<style>
    /* shadcn/ui CSS Variables */
    :root {
        --background: 0 0% 100%;
        --foreground: 222.2 84% 4.9%;
        --card: 0 0% 100%;
        --card-foreground: 222.2 84% 4.9%;
        --popover: 0 0% 100%;
        --popover-foreground: 222.2 84% 4.9%;
        --primary: 222.2 47.4% 11.2%;
        --primary-foreground: 210 40% 98%;
        --secondary: 210 40% 96%;
        --secondary-foreground: 222.2 84% 4.9%;
        --muted: 210 40% 96%;
        --muted-foreground: 215.4 16.3% 46.9%;
        --accent: 210 40% 96%;
        --accent-foreground: 222.2 84% 4.9%;
        --destructive: 0 84.2% 60.2%;
        --destructive-foreground: 210 40% 98%;
        --border: 214.3 31.8% 91.4%;
        --input: 214.3 31.8% 91.4%;
        --ring: 222.2 84% 4.9%;
        --radius: 0.5rem;
    }

    .dark {
        --background: 222.2 84% 4.9%;
        --foreground: 210 40% 98%;
        --card: 222.2 84% 4.9%;
        --card-foreground: 210 40% 98%;
        --popover: 222.2 84% 4.9%;
        --popover-foreground: 210 40% 98%;
        --primary: 210 40% 98%;
        --primary-foreground: 222.2 84% 4.9%;
        --secondary: 217.2 32.6% 17.5%;
        --secondary-foreground: 210 40% 98%;
        --muted: 217.2 32.6% 17.5%;
        --muted-foreground: 215 20.2% 65.1%;
        --accent: 217.2 32.6% 17.5%;
        --accent-foreground: 210 40% 98%;
        --destructive: 0 62.8% 30.6%;
        --destructive-foreground: 210 40% 98%;
        --border: 217.2 32.6% 17.5%;
        --input: 217.2 32.6% 17.5%;
        --ring: 212.7 26.8% 83.9%;
    }

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        border-color: hsl(var(--border));
    }

    body {
        background-color: hsl(var(--background));
        color: hsl(var(--foreground));
    }

    /* shadcn/ui Components */
    .card {
        background-color: hsl(var(--card));
        color: hsl(var(--card-foreground));
        border: 1px solid hsl(var(--border));
        border-radius: calc(var(--radius) - 2px);
    }

    .button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        border-radius: calc(var(--radius) - 2px);
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
        outline: none;
        border: none;
        cursor: pointer;
    }

    .button-primary {
        background-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }

    .button-primary:hover {
        background-color: hsl(var(--primary) / 0.9);
    }

    .button-secondary {
        background-color: hsl(var(--secondary));
        color: hsl(var(--secondary-foreground));
    }

    .button-secondary:hover {
        background-color: hsl(var(--secondary) / 0.8);
    }

    .button-ghost {
        background-color: transparent;
        color: hsl(var(--foreground));
    }

    .button-ghost:hover {
        background-color: hsl(var(--accent));
        color: hsl(var(--accent-foreground));
    }

    .input {
        display: flex;
        height: 2.5rem;
        width: 100%;
        border-radius: calc(var(--radius) - 2px);
        border: 1px solid hsl(var(--input));
        background-color: hsl(var(--background));
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .input:focus {
        outline: none;
        ring: 2px solid hsl(var(--ring));
        border-color: hsl(var(--ring));
    }

    .select {
        display: flex;
        height: 2.5rem;
        width: 100%;
        border-radius: calc(var(--radius) - 2px);
        border: 1px solid hsl(var(--input));
        background-color: hsl(var(--background));
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .select:focus {
        outline: none;
        ring: 2px solid hsl(var(--ring));
        border-color: hsl(var(--ring));
    }

    .badge {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.125rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        border: 1px solid transparent;
    }

    .badge-default {
        background-color: hsl(var(--secondary));
        color: hsl(var(--secondary-foreground));
    }

    .badge-destructive {
        background-color: hsl(var(--destructive));
        color: hsl(var(--destructive-foreground));
    }

    .badge-outline {
        color: hsl(var(--foreground));
        border-color: hsl(var(--border));
    }

    /* Custom animations */
    .animate-pulse-slow {
        animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .sidebar-item {
        transition: all 0.15s ease-in-out;
    }

    .sidebar-item:hover {
        background-color: hsl(var(--accent));
        color: hsl(var(--accent-foreground));
    }

    .sidebar-item.active {
        background-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }

    /* Scrollbar styling */
    .scrollbar-thin::-webkit-scrollbar {
        width: 4px;
    }

    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: hsl(var(--border));
        border-radius: 2px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: hsl(var(--muted-foreground));
    }

    /* Ensure sidebar content is scrollable */
    .sidebar-content {
        height: calc(100vh - 4rem);
        overflow-y: auto;
    }

    /* Mobile menu overlay */
    .mobile-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.8);
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease-in-out;
        z-index: 40;
    }

    .mobile-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    /* Sidebar slide animation */
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    @media (min-width: 1024px) {
        .sidebar {
            transform: translateX(0);
        }
    }
</style>
<script>
    // Tailwind config for shadcn/ui compatibility
        tailwind.config = {
            darkMode: ['class'],
            theme: {
                extend: {
                    colors: {
                        border: 'hsl(var(--border))',
                        input: 'hsl(var(--input))',
                        ring: 'hsl(var(--ring))',
                        background: 'hsl(var(--background))',
                        foreground: 'hsl(var(--foreground))',
                        primary: {
                            DEFAULT: 'hsl(var(--primary))',
                            foreground: 'hsl(var(--primary-foreground))'
                        },
                        secondary: {
                            DEFAULT: 'hsl(var(--secondary))',
                            foreground: 'hsl(var(--secondary-foreground))'
                        },
                        destructive: {
                            DEFAULT: 'hsl(var(--destructive))',
                            foreground: 'hsl(var(--destructive-foreground))'
                        },
                        muted: {
                            DEFAULT: 'hsl(var(--muted))',
                            foreground: 'hsl(var(--muted-foreground))'
                        },
                        accent: {
                            DEFAULT: 'hsl(var(--accent))',
                            foreground: 'hsl(var(--accent-foreground))'
                        },
                        popover: {
                            DEFAULT: 'hsl(var(--popover))',
                            foreground: 'hsl(var(--popover-foreground))'
                        },
                        card: {
                            DEFAULT: 'hsl(var(--card))',
                            foreground: 'hsl(var(--card-foreground))'
                        }
                    },
                    borderRadius: {
                        lg: 'var(--radius)',
                        md: 'calc(var(--radius) - 2px)',
                        sm: 'calc(var(--radius) - 4px)'
                    }
                }
            }
        }
</script>

<style>
    /* Custom scrollbar for sidebar */
    .sidebar-content {
        scrollbar-width: thin;
        scrollbar-color: rgba(155, 155, 155, 0.5) transparent;
        max-height: calc(100vh - 140px);
        overflow-y: auto;
    }

    .sidebar-content::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-content::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-content::-webkit-scrollbar-thumb {
        background-color: rgba(155, 155, 155, 0.5);
        border-radius: 2px;
    }

    .sidebar-content::-webkit-scrollbar-thumb:hover {
        background-color: rgba(155, 155, 155, 0.7);
    }

    /* Clean Menu Dropdowns */
    .menu-section {
        margin-bottom: 0.5rem;
        position: relative;
    }

    .menu-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        margin: 0.125rem 0;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .menu-section-header:hover {
        background: hsl(var(--muted));
    }

    .menu-section-header.active {
        background: hsl(var(--muted));
    }

    .menu-icon {
        width: 1.25rem;
        height: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        opacity: 0.7;
    }

    .menu-arrow {
        transition: transform 0.2s ease;
        font-size: 0.75rem;
        opacity: 0.5;
    }

    .menu-arrow.open {
        transform: rotate(90deg);
    }

    .menu-dropdown {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        margin-left: 1rem;
        border-left: 1px solid hsl(var(--border));
    }

    .menu-dropdown.open {
        max-height: 400px;
        padding: 0.25rem 0;
    }

    .menu-dropdown-item {
        display: flex;
        align-items: center;
        padding: 0.375rem 0.75rem;
        margin: 0.0625rem 0.25rem;
        border-radius: 0.25rem;
        text-decoration: none;
        transition: background-color 0.15s ease;
        font-size: 0.75rem;
        font-weight: 400;
        color: hsl(var(--muted-foreground));
    }

    .menu-dropdown-item:hover {
        background: hsl(var(--muted));
        color: hsl(var(--foreground));
    }

    /* Clean Badges */
    .badge-innovative {
        font-size: 0.6rem;
        font-weight: 500;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        background: hsl(var(--destructive));
        color: hsl(var(--destructive-foreground));
    }

    .badge-status {
        font-size: 0.6rem;
        font-weight: 500;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        background: hsl(var(--secondary));
        color: hsl(var(--secondary-foreground));
    }

    .badge-count {
        font-size: 0.6rem;
        font-weight: 500;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        background: hsl(var(--muted));
        color: hsl(var(--muted-foreground));
    }

    /* Top Bar Dropdowns */
    .top-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 320px;
        background: hsl(var(--background));
        border: 1px solid hsl(var(--border));
        border-radius: 0.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-5px);
        transition: all 0.2s ease;
        margin-top: 0.5rem;
    }

    .top-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notification-item {
        display: flex;
        align-items: flex-start;
        padding: 0.75rem;
        border-bottom: 1px solid hsl(var(--border));
        transition: background-color 0.15s ease;
        font-size: 0.8rem;
    }

    .notification-item:hover {
        background: hsl(var(--muted));
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
        flex-shrink: 0;
        font-size: 0.75rem;
    }

    .notification-critical {
        background: hsl(var(--destructive) / 0.1);
        color: hsl(var(--destructive));
    }

    .notification-warning {
        background: hsl(var(--muted));
        color: hsl(var(--muted-foreground));
    }

    .notification-info {
        background: hsl(var(--muted));
        color: hsl(var(--muted-foreground));
    }

    /* User Menu Dropdown */
    .user-dropdown {
        width: 280px;
    }

    .user-menu-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        color: inherit;
        text-decoration: none;
        transition: background-color 0.15s ease;
        border-bottom: 1px solid hsl(var(--border));
        font-size: 0.8rem;
    }

    .user-menu-item:hover {
        background: hsl(var(--muted));
    }

    .user-menu-item:last-child {
        border-bottom: none;
    }

    .user-menu-icon {
        width: 1rem;
        height: 1rem;
        margin-right: 0.75rem;
        opacity: 0.6;
        font-size: 0.75rem;
    }

    /* Phase badges */
    .phase-badge {
        font-size: 0.5rem;
        font-weight: 600;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }
</style>
