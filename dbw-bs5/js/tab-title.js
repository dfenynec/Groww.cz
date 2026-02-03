// tab-title.js

const originalTitle = document.title;

document.addEventListener('visibilitychange', function () {
  if (document.hidden) {
    document.title = 'Tvůj web čeká! 🚀';
  } else {
    document.title = 'Vítej zpět! 👋';
    setTimeout(() => {
      document.title = originalTitle;
    }, 2000);
  }
});