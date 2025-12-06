import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;
window.lucide = { createIcons, icons };

Alpine.start();

// Render icons on first load and after Livewire navigations.
const renderIcons = () => createIcons({ icons });
document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('livewire:navigated', renderIcons);
