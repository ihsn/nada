 function toggler(){
    $(".toggle_container").hide();
    $("h2.expand_heading").toggle(function(){
        $(this).addClass("active"); 
    }, function () {
        $(this).removeClass("active");
    });
    $("h2.expand_heading").click(function(){
        $(this).next(".toggle_container").slideToggle("slow");
    });
    $(".expand_all").toggle(function(){
        $(this).addClass("expanded"); 
    }, function () {
        $(this).removeClass("expanded");
    });
    $(".expand_all").click(function(){
        $(".toggle_container").slideToggle("slow");
    });
}

//------------- Java Script for opening share urls from pagetools -------------------//
var url=location.href; 

function twitter() {
    window.open('http://twitter.com/share?url='+encodeURIComponent(url)
            + '&text='+encodeURIComponent(document.title),'_new');
}

function facebook() {
    window.open('http://www.facebook.com/share.php?u='
            + encodeURIComponent(url) + '&t='
            + encodeURIComponent(document.title), '_new');
}

function googlebuzz() {
    window.open('http://www.google.com/buzz/post?url='
            + encodeURIComponent(url) + '&title='
            + encodeURIComponent(document.title), '_new');
}

function linkedin() {
    window.open('http://www.linkedin.com/shareArticle?mini=true&url='
            + encodeURIComponent(url) + '&title='
            + encodeURIComponent(document.title));
}

function digg() {
    window.open('http://digg.com/submit?url=' + encodeURIComponent(url)
            + '&title=' + encodeURIComponent(document.title), '_new');
}

function stumbleUpon() {
    window.open('http://www.stumbleupon.com/submit?url='
            + encodeURIComponent(url) + '&title='
            + encodeURIComponent(document.title), '_new');
}

function delicious() {
    window.open('http://delicious.com/save?url=' + encodeURIComponent(url)
            + '&title=' + encodeURIComponent(document.title), '_new');
}

function hi5() {
    window.open('http://hi5.com/save?url=' + encodeURIComponent(url)
            + '&title=' + encodeURIComponent(document.title), '_new'); 
}

function renren() {
    window.open('http://share.renren.com/share/buttonshare.do?link='+url
            +'&title='+document.title, '_new'); 
}


function sina() {
    window.open('http://v.t.sina.com.cn/share/share.php?title='+document.title
            +'&url='+url+'&source=bookmark&appkey=', '_new');  
}

$(document).ready(function() {
    toggler();
}); 