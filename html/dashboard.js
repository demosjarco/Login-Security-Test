$(function() {
    currentTime();
    setInterval(currentTime(), 1000);
});

function currentTime() {
    const currentTime = new Date();
    let hours = currentTime.getHours();
    let minutes = currentTime.getMinutes();
    const ampm = hours > 11 ? 'PM' : 'AM';
    
    hours -= hours > 12 ? 12 : 0;
    minutes = minutes < 10 ? '0' + minutes : '' + minutes;
    
    $('#currentTime').text(hours + ":" + minutes + " " + ampm);
}

/*function fetch_user()
 {
  $.ajax({
   url:"fetch_user.php",
   method:"POST",
   success:function(data){
    $('#user_details').html(data);
   }
  })
 }*/