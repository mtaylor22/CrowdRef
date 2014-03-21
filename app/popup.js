function getCookies(domain, name, callback) {
    chrome.cookies.get({"url": domain, "name": name}, function(cookie) {
        if(callback) {
            callback(cookie.value);
        }
    });
}

function setCookie(domain, name, value){
  chrome.cookies.set({ "url": domain, "name": name, "value": value});
}

function getStatus(){
  jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
    alert('hi');
    if (data.status == 1)
      setCookie("http://crowdref.atwebpages.com/mobile_login.php", "user_logged", "true");
  });
}

function tryLogin(){
  $('#lf').css("display", "none");
  $('#sub_diag').css("display", "block");
  $('#sub_diag').html("Logging in...");
  jQuery.post("http://crowdref.atwebpages.com/mobile_login.php", $("login_form").serialize(), function(data, textStatus) {
    alert('hi');
    if (data.status = 1){
      setCookie("http://crowdref.atwebpages.com/mobile_login.php", "user_logged", "true");
      $('#lf').css("display", "none");
      $('#sub_diag').css("display", "block");
      window.close();
    } else {
      $('#lf').css("display", "block");
      $('#sub_diag').css("display", "none");
    }
}, "json");
}

window.refs = 0;
function submitRef(){
  $('#sub_diag').html("Submitting...");
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    jQuery.post("http://crowdref.atwebpages.com/mobile_submitref.php", {"ref_text": tabs[0].url}, function(data, textStatus) {
    if (data.status = "0"){
      window.refs++;
      chrome.browserAction.setBadgeText({text: window.refs.toString()});
      window.close();
    }
    }, "json");
  });
}
document.addEventListener('DOMContentLoaded', function () {
  chrome.tabs.executeScript(null, { file: "jquery.js" });
  document.getElementById('login_submit').addEventListener('click', tryLogin);
  getCookies("http://crowdref.atwebpages.com/mobile_login.php", "user_logged", function(id) {
    $('#lf').css("display", "none");
    $('#sub_diag').css("display", "block");
    submitRef();
  });
});

