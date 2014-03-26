function checkNotification(){
	jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
	    if (data.status == "1"){
	    	jQuery.getJSON("http://crowdref.atwebpages.com/mobile_get_notifications.php", function(notif) {
	    		chrome.browserAction.setBadgeBackgroundColor({color: [80, 0, 0, 255] });
      			chrome.browserAction.setBadgeText({text: (notif.count > 0) ? notif.count: ""});
      			chrome.browserAction.setTitle({"title": "CroudRef: " + notif.count + " new references"});
	    	});
	    } else {
	    	//still not logged in 
	    		chrome.browserAction.setBadgeBackgroundColor({color: [255, 0, 0, 255] });
      			chrome.browserAction.setBadgeText({"text": "!"});
      			chrome.browserAction.setTitle({"title": "Please Login into CrowdRef"});
	    }
	});
}


setInterval(function(){
checkNotification();
}, 15000);