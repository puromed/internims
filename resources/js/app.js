import { createIcons, icons } from 'lucide';

// Expose Lucide globally for icon rendering
window.lucide = { createIcons, icons };

// Render icons on first load and after Livewire navigations.
const renderIcons = () => createIcons({ icons });
document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('livewire:navigated', renderIcons);
