// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.alert-dismissible').forEach(function(el) {
    setTimeout(function() { 
      var bs = new bootstrap.Alert(el);
      bs.close();
    }, 3000);
  });
});
