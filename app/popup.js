function getCookies(domain, name, callback, elsecallback) {
    chrome.cookies.get({"url": domain, "name": name}, function(cookie) {
        if(callback) {
            callback(cookie.value);
        } else {
          elsecallback();
        }
    });
}

function setCookie(domain, name, value){
  chrome.cookies.set({ "url": domain, "name": name, "value": value});
}

function getStatus(){
  jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
    return (data.status == "1");
  });
}

function tryLogin(){
  $('#lf').css("display", "none");
  $('#sub_diag').css("display", "block");
  $('#sub_diag').html("Logging in...");
  jQuery.post("http://crowdref.atwebpages.com/mobile_login.php", $("login_form").serialize(), function(data, textStatus) {
    alert(data.status);
    if (data.status = 1){
      $('#lf').css("display", "none");
      $('#sub_diag').css("display", "block");
      window.close();
    } else {
      $('#lf').css("display", "block");
      $('#sub_diag').css("display", "none");
      return false;
    }
}, "json");
}

window.refs = 0;
function submitRef(){
  $('#options_form').css("display", "none");
  $('#lf').css("display", "none");
  $('#sub_diag').css("display", "block");
  $('#sub_diag').html("<h1>Submitting...</h1>");
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    jQuery.post("http://crowdref.atwebpages.com/mobile_submitref.php", {"ref_text": tabs[0].url}, function(data, textStatus) {
    if (data.status = "0"){
      window.close();
    }
    }, "json");
  });
}

function showOptions(){
  $('#options_form').slideToggle();
  $('#lf').css("display", "none");
  $('#sub_diag').css("display", "none");
}
function goto_account(){
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    chrome.tabs.update(tabs[0].id, {url: 'http://crowdref.atwebpages.com/get_notifications.php'});
  });
}
function goto_logout(){
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    chrome.tabs.update(tabs[0].id, {url: 'http://crowdref.atwebpages.com/logout.php'});
  });
}

document.addEventListener('DOMContentLoaded', function () {
  // chrome.tabs.executeScript(null, { file: "jquery.js" });
  $('#logout_button').click(goto_logout);
  $('#account_button').click(goto_account);
  $('#submitref_button').click(submitRef);

  jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
    if (data.status == "1"){
      $('#lf').css("display", "none");
      $('#sub_diag').css("display", "block");
      showOptions();
    } else {

    }
  });
});
