function setCookies(domain, name, value){
  chrome.cookies.set({ "url": domain, "name": name, "value": value});
  // chrome.cookies.set({ url: "http://crowdref.atwebpages.com/login.php", name: "user_logged", value: "true" });
  // setCookies( "http://crowdref.atwebpages.com/login.php", "user_logged", "true");
}
document.addEventListener('DOMContentLoaded', function () {
    alert('password accepted');
	setCookies( "http://crowdref.atwebpages.com/mobile_login.php", "user_logged", "true");
    alert('password accepted');
  getCookies("http://crowdref.atwebpages.com/mobile_login.php", "user_logged", function(id) {
    alert('password accepted');
  });
});
