function checkNotification(){
	jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
	    if (data.status == "1"){
	    	jQuery.getJSON("http://crowdref.atwebpages.com/mobile_get_notifications.php", function(notif) {
      			chrome.browserAction.setBadgeText({text: notif.count});
	    	});
	    } else {
	    	//still not logged in 
	    }
	});
}


setInterval(function(){
checkNotification();
}, 15000);