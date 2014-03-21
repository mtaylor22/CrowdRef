chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab) {
        if(tab.url == "https://delicious.com/save") {
            chrome.tabs.remove(tabId);
        }
});
