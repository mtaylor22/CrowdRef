function checkNotification(){
	jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
	    if (data.status == "1"){
	    	jQuery.getJSON("http://crowdref.atwebpages.com/mobile_get_notifications.php", function(notif) {
      			chrome.browserAction.setBadgeText({text: (notif.count > 0) ? notif.count: "0"});
	    	});
	    } else {
	    	//still not logged in 
			chrome.browserAction.setBadgeText({"text": "!"});
	    }
	});
}


setInterval(function(){
checkNotification();
}, 15000);

chrome.tabs.onUpdated.addListener(function(tabId, changeInfo) {
	check_url_bloomfilter();
});
chrome.tabs.onActivated.addListener(function(tabId, changeInfo) {
	check_url_bloomfilter();
});

function check_url_bloomfilter(){
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    chrome.storage.sync.get('bloomfilter', function(bloomfilter) {
      if (bloomfilter['bloomfilter'] != undefined){
        var bs = new BitArray(bloomfilter['bloomfilter'].length);
        bs.fromString(bloomfilter['bloomfilter']);
        for (var i = 0; i < 10; i++){
          if (bs.get(parseInt(MD5(encodeURIComponent(tabs[0].url)+i+"salty")) % 1000) != 1){
          	notify_unavailable();
          	return;
          }
        }
        // This url was indicated by the bloom filter
        notify_available();
      }
    });
  });
}

function notify_available(){	
	chrome.browserAction.setBadgeBackgroundColor({color: [0, 200, 0, 255] });
	chrome.browserAction.getBadgeText({}, function(txt) {
		if (txt == undefined || txt == "")
			chrome.browserAction.setBadgeText({text: "+"});
	});
	chrome.browserAction.setTitle({"title": "CrowdRef: A reference may be available for this page!"});
}

function notify_unavailable(){	
	chrome.browserAction.setBadgeBackgroundColor({color: [0, 0, 0, 255] });
	chrome.browserAction.getBadgeText({}, function(txt) {
		if (txt == undefined || txt == "")
			chrome.browserAction.setBadgeText({text: "X"});
	});
	chrome.browserAction.setTitle({"title": "CrowdRef: Create a new reference for this page!"});
}
