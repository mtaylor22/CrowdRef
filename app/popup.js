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
    jQuery.post("http://crowdref.atwebpages.com/mobile_submitref.php", {"ref_text": (encodeURIComponent(tabs[0].url))}, function(data, textStatus) {
    if (data.status = "0"){
      window.close();
    } else {alert(data.status);}
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
    chrome.tabs.update(tabs[0].id, {url: 'http://crowdref.atwebpages.com/reference.php'});
  });
}
function goto_logout(){
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    chrome.tabs.update(tabs[0].id, {url: 'http://crowdref.atwebpages.com/logout.php'});
  });
}
function get_bloom(){
  alert('updating');
  $.get("http://crowdref.atwebpages.com/bloomfilter.php", function(data){
    chrome.storage.sync.get('bloomfilter', function(bloomfilter) {
      if (data != bloomfilter['bloomfilter'])
        chrome.storage.sync.set({'bloomfilter': data}, function() {
          var now = new Date(); 
          chrome.storage.sync.set({'lastupdated': now.toJSON()}, function(){return});
          return;
        });
    });
  });
}
function check_url_bloomfilter(){
  chrome.tabs.query({currentWindow: true, active: true}, function(tabs){
    chrome.storage.sync.get('bloomfilter', function(bloomfilter) {
      if (bloomfilter['bloomfilter'] != undefined){
        var bs = new BitArray(bloomfilter['bloomfilter'].length);
        bs.fromString(bloomfilter['bloomfilter']);
        for (var i = 0; i < 10; i++){
          if (bs.get(parseInt(MD5(encodeURIComponent(tabs[0].url)+i+"salty")) % 1000) != 1) return;
        }
        // This url was indicated by the bloom filter
        alert("URL GOOD");
      }
    });
  });
}


document.addEventListener('DOMContentLoaded', function () {
  // chrome.tabs.executeScript(null, { file: "jquery.js" });
  $('#logout_button').click(goto_logout);
  $('#account_button').click(goto_account);
  $('#submitref_button').click(submitRef);
  update_bloom_filter();
  jQuery.getJSON("http://crowdref.atwebpages.com/mobile_login.php", function(data) {
    if (data.status == "1"){
      $('#lf').css("display", "none");
      $('#sub_diag').css("display", "block");
      showOptions();
    } else {

    }
  });
});

function update_bloom_filter(){
    chrome.storage.sync.get('lastupdated', function(lastupdated) {
    if (lastupdated['lastupdated'] != undefined){
      var now = new Date();
      var then = new Date(lastupdated['lastupdated']); 
      if (((now.getTime() - then.getTime())/(1000 * 60 * 60 * 24)) >= 2) 
        get_bloom();
    } else {
      get_bloom();
    }
  });
}