// src/utils/images.js

// Lee desde src/assets para que Vite las optimice en el build
const imagesMap = import.meta.glob('/src/assets/images/intranet/*.{png,jpg,jpeg,svg,ico}', { eager: true });

export const intranetImages = Object.keys(imagesMap).reduce((acc, path) => {
  const fileName = path.split('/').pop().replace(/\.[^/.]+$/, '');
  acc[fileName] = imagesMap[path].default;
  return acc;
}, {});