import { createIcons, icons } from 'lucide';
import 'driver.js/dist/driver.css';
import { startTour, hasTour } from './tours/index.js';

// Expose Lucide globally for icon rendering
window.lucide = { createIcons, icons };

// Expose tour functions globally for Alpine components
window.internimsTour = { startTour, hasTour };

// Render icons on first load and after Livewire navigations.
const renderIcons = () => createIcons({ icons });
document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('livewire:navigated', renderIcons);
