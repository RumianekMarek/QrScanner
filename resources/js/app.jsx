import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Create a singleton root instance
let root = null;

// Cache for resolved components to prevent double resolution
const resolvedComponents = new Map();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        
        if (resolvedComponents.has(name)) {
            return resolvedComponents.get(name);
        }

        const component = await resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );
        
        resolvedComponents.set(name, component);
        
        return component;
    },
    setup({ el, App, props }) {
        if (!root) {
            root = createRoot(el);
        }
        
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});