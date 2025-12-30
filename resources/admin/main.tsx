import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminRoot from './AdminRoot';
import './styles/index.css';

const element = document.getElementById('admin-app');

if (element) {
    const root = createRoot(element);
    root.render(<AdminRoot />);
}
