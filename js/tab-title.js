// tab-title.js

const originalTitle = document.title;

document.addEventListener('visibilitychange', function () {
  if (document.hidden) {
    document.title = 'Neodcházej! 🚀';
  } else {
    document.title = 'Vítejte zpět! 👋';
    setTimeout(() => {
      document.title = originalTitle;
    }, 2000);
  }
});