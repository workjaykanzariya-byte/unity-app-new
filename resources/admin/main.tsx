import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminRoot from './AdminRoot';
import './styles/index.css';

const rootElement = document.getElementById('admin-root');

if (rootElement) {
    createRoot(rootElement).render(<AdminRoot />);
}
