const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('gstock', {
  getConfig: () => ipcRenderer.invoke('gstock:get-config'),
  getDefaults: () => ipcRenderer.invoke('gstock:defaults'),
  saveConfig: (config) => ipcRenderer.invoke('gstock:save-config', config),
  openSettings: () => ipcRenderer.invoke('gstock:open-settings'),
  print: () => ipcRenderer.invoke('gstock:print'),
});
