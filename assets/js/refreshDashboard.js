import $ from "jquery";

function refreshDashboard() {
   var $div = $('<div>');
   var $url =refreshDashboardUrl +  ' #ex1-tabs-1';
   $div.load($url,function (data){
       var $openDropdown = $('.dropdown-menu.show');
       console.log($openDropdown);
       if($openDropdown.length === 0){
           $('#ex1-tabs-1').closest('div').html( $(this)[0].innerHTML);
       }
   });
}

export {refreshDashboard};
