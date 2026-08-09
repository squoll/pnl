let deferredPrompt;

// Register Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Try to register the service worker from the root
    navigator.serviceWorker.register('/sw.js').catch(err => {
      console.log('ServiceWorker registration failed: ', err);
    });
  });
}

// Create the install banner
const installBanner = document.createElement('div');
installBanner.style.cssText = `
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: #343a40;
  color: white;
  padding: 15px;
  display: none;
  justify-content: space-between;
  align-items: center;
  z-index: 10000;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
`;

const textDiv = document.createElement('div');
textDiv.innerText = 'Добавьте приложение на главный экран для быстрого доступа.';
installBanner.appendChild(textDiv);

const btnDiv = document.createElement('div');
const installBtn = document.createElement('button');
installBtn.innerText = 'Установить';
installBtn.className = 'btn btn-primary btn-sm me-2';
btnDiv.appendChild(installBtn);

const closeBtn = document.createElement('button');
closeBtn.innerText = '✕';
closeBtn.className = 'btn btn-sm btn-outline-light';
btnDiv.appendChild(closeBtn);

installBanner.appendChild(btnDiv);
document.body.appendChild(installBanner);

// Handle PWA installation
window.addEventListener('beforeinstallprompt', (e) => {
  // Prevent Chrome from showing the mini-infobar
  e.preventDefault();
  // Stash the event so it can be triggered later.
  deferredPrompt = e;
  // Update UI to notify the user they can install the PWA
  installBanner.style.display = 'flex';
});

installBtn.addEventListener('click', async () => {
  if (deferredPrompt) {
    installBanner.style.display = 'none';
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
  }
});

closeBtn.addEventListener('click', () => {
  installBanner.style.display = 'none';
});

// iOS standalone detection
const isIos = () => {
  const userAgent = window.navigator.userAgent.toLowerCase();
  return /iphone|ipad|ipod/.test(userAgent);
}
const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

if (isIos() && !isInStandaloneMode()) {
  // Show a manual prompt for iOS
  textDiv.innerText = 'Установите приложение: нажмите "Поделиться" и выберите "На экран «Домой»"';
  installBtn.style.display = 'none';
  installBanner.style.display = 'flex';
}
