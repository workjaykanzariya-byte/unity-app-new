import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

const container = document.getElementById('admin-root');

if (container) {
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}
